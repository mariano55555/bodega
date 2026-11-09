<?php

namespace App\Services;

use App\Exceptions\InventoryReversalException;
use App\Models\Inventory;
use App\Models\InventoryClosure;
use App\Models\InventoryMovement;
use App\Models\MovementReason;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Collection;

/**
 * Keeps the stock effect of a document (transfer, dispatch, internal production) in line with what
 * the document says. The current effect is read from its kardex movements, so it already includes
 * earlier corrections, and only the net difference is moved. Cancelling is moving to an empty effect.
 */
class DocumentStockService
{
    public function __construct(
        protected KardexService $kardexService,
        protected InventoryValuationService $valuationService,
    ) {}

    /**
     * Net quantity and value per warehouse and product of the given movements (inbound positive).
     *
     * @param  iterable<InventoryMovement>  $movements
     * @return array<string, array{warehouse_id: int, product_id: int, quantity: float, value: float}>
     */
    public function netEffects(iterable $movements): array
    {
        $effects = [];

        foreach ($movements as $movement) {
            $key = static::key((int) $movement->warehouse_id, (int) $movement->product_id);
            $quantity = (float) $movement->quantity_in - (float) $movement->quantity_out;
            $value = $quantity * (float) $movement->unit_cost;

            $effects[$key] ??= [
                'warehouse_id' => (int) $movement->warehouse_id,
                'product_id' => (int) $movement->product_id,
                'quantity' => 0.0,
                'value' => 0.0,
            ];
            $effects[$key]['quantity'] += $quantity;
            $effects[$key]['value'] += $value;
        }

        return array_filter($effects, fn (array $effect) => abs($effect['quantity']) > 0.000005);
    }

    /**
     * Build a target effect from document lines.
     *
     * @param  iterable<array{product_id: int|string, quantity: float|int|string}>  $lines
     * @return array<string, array{warehouse_id: int, product_id: int, quantity: float}>
     */
    public function effectsFromLines(int $warehouseId, iterable $lines, int $sign): array
    {
        $effects = [];

        foreach ($lines as $line) {
            $productId = (int) $line['product_id'];
            $key = static::key($warehouseId, $productId);

            $effects[$key] ??= ['warehouse_id' => $warehouseId, 'product_id' => $productId, 'quantity' => 0.0];
            $effects[$key]['quantity'] += $sign * (float) $line['quantity'];
        }

        return $effects;
    }

    /**
     * Merge several effect maps adding the quantities of the same warehouse and product.
     *
     * @param  array<string, array{warehouse_id: int, product_id: int, quantity: float}>  ...$maps
     * @return array<string, array{warehouse_id: int, product_id: int, quantity: float}>
     */
    public function mergeEffects(array ...$maps): array
    {
        $merged = [];

        foreach ($maps as $map) {
            foreach ($map as $key => $effect) {
                $merged[$key] ??= ['warehouse_id' => $effect['warehouse_id'], 'product_id' => $effect['product_id'], 'quantity' => 0.0];
                $merged[$key]['quantity'] += (float) $effect['quantity'];
            }
        }

        return $merged;
    }

    /**
     * Fail when any warehouse touched by the change has a closed period: the months of the existing
     * movements of that warehouse and the month in which the new movements are recorded.
     *
     * @param  Collection<int, InventoryMovement>  $movements
     * @param  array<string, array{warehouse_id: int, product_id: int, quantity: float}>  $target
     */
    public function validatePeriods(int $companyId, Collection $movements, array $target, mixed $movementDate): void
    {
        $warehouseIds = $movements->pluck('warehouse_id')
            ->merge(collect($target)->pluck('warehouse_id'))
            ->map(fn ($id) => (int) $id)
            ->unique();

        foreach ($warehouseIds as $warehouseId) {
            collect($movements)
                ->where('warehouse_id', $warehouseId)
                ->pluck('movement_date')
                ->push($movementDate)
                ->unique(fn ($date) => Carbon::parse($date)->format('Y-m'))
                ->each(function ($date) use ($companyId, $warehouseId) {
                    try {
                        InventoryClosure::validatePeriodOpen($companyId, $warehouseId, $date);
                    } catch (InventoryReversalException $e) {
                        throw $e;
                    } catch (\Exception $e) {
                        throw new InventoryReversalException($e->getMessage(), 0, $e);
                    }
                });
        }
    }

    /**
     * Create the movements that take the stock from the current effect to the target effect.
     *
     * Quantities that return to a place keep the cost they had there; new quantities leave a warehouse
     * at its weighted average and enter at the document cost of the product.
     *
     * @param  array<string, array{warehouse_id: int, product_id: int, quantity: float, value?: float}>  $current
     * @param  array<string, array{warehouse_id: int, product_id: int, quantity: float}>  $target
     * @param  array{
     *     company_id: int,
     *     movement_date: mixed,
     *     links: array<string, mixed>,
     *     document_type: string,
     *     document_number?: string|null,
     *     inbound_type: string,
     *     outbound_type: string,
     *     inbound_reason: MovementReason,
     *     outbound_reason: MovementReason,
     *     inbound_notes: string,
     *     outbound_notes: string,
     *     unit_costs?: array<int, float>,
     *     metadata?: array<string, mixed>,
     *     user_id?: int|null,
     *     shortage_message: Closure(int, int, float, float): string,
     *     direction?: 'in'|'out'|null,
     * }  $context
     * @return Collection<int, InventoryMovement>
     */
    public function rebalance(array $current, array $target, array $context): Collection
    {
        $differences = [];

        foreach (array_unique(array_merge(array_keys($current), array_keys($target))) as $key) {
            $difference = (float) ($target[$key]['quantity'] ?? 0) - (float) ($current[$key]['quantity'] ?? 0);

            if (abs($difference) <= 0.000005) {
                continue;
            }

            $source = $target[$key] ?? $current[$key];
            $differences[] = [
                'warehouse_id' => (int) $source['warehouse_id'],
                'product_id' => (int) $source['product_id'],
                'quantity' => round($difference, 5),
                'unit_cost' => $this->costFor($key, $difference, $current, $source, $context),
            ];
        }

        // Only one direction when several documents are corrected together (all entries before any exit)
        $direction = $context['direction'] ?? null;
        if ($direction !== null) {
            $differences = array_values(array_filter($differences, fn (array $difference) => ($difference['quantity'] > 0) === ($direction === 'in')));
        }

        // Inbound first, so a warehouse never runs short because of the order of the lines
        usort($differences, fn (array $a, array $b) => $b['quantity'] <=> $a['quantity']);

        $movements = collect();

        foreach ($differences as $difference) {
            $isInbound = $difference['quantity'] > 0;
            $quantity = abs($difference['quantity']);

            if (! $isInbound) {
                $this->ensureAvailable($difference['warehouse_id'], $difference['product_id'], $quantity, $context['shortage_message']);
            }

            $movement = $this->kardexService->createMovement(array_merge($context['links'], [
                'company_id' => $context['company_id'],
                'warehouse_id' => $difference['warehouse_id'],
                'product_id' => $difference['product_id'],
                'movement_reason_id' => ($isInbound ? $context['inbound_reason'] : $context['outbound_reason'])->id,
                'movement_type' => $isInbound ? $context['inbound_type'] : $context['outbound_type'],
                'movement_date' => $context['movement_date'],
                'quantity' => $isInbound ? $quantity : -$quantity,
                'quantity_in' => $isInbound ? $quantity : 0,
                'quantity_out' => $isInbound ? 0 : $quantity,
                'unit_cost' => $difference['unit_cost'],
                'total_cost' => round($quantity * $difference['unit_cost'], 5),
                'document_type' => $context['document_type'],
                'document_number' => $context['document_number'] ?? null,
                'metadata' => $context['metadata'] ?? null,
                'notes' => $isInbound ? $context['inbound_notes'] : $context['outbound_notes'],
                'is_active' => true,
                'active_at' => now(),
                'created_by' => $context['user_id'] ?? null,
            ]));

            $this->valuationService->applyMovement($movement);
            $movements->push($movement);
        }

        return $movements;
    }

    /**
     * Resolve a movement reason by code, falling back to any reason of the same direction.
     */
    public function reason(string $code, string $movementType, ?string $category = null): MovementReason
    {
        $reason = MovementReason::query()->where('code', $code)->first()
            ?? ($category ? MovementReason::query()->where('movement_type', $movementType)->where('category', $category)->first() : null)
            ?? MovementReason::query()->where('movement_type', $movementType)->first();

        if (! $reason) {
            throw new InventoryReversalException("No existe un motivo de movimiento {$code} para registrar el ajuste de inventario.");
        }

        return $reason;
    }

    public static function key(int $warehouseId, int $productId): string
    {
        return $warehouseId.':'.$productId;
    }

    /**
     * @param  array<string, array{quantity: float, value?: float}>  $current
     * @param  array{warehouse_id: int, product_id: int}  $source
     * @param  array{unit_costs?: array<int, float>}  $context
     */
    protected function costFor(string $key, float $difference, array $current, array $source, array $context): float
    {
        $existing = $current[$key] ?? null;

        // Undoing what this document did in this place: use the cost it had here
        if ($existing && abs($existing['quantity']) > 0.000005 && ($existing['quantity'] > 0) !== ($difference > 0)) {
            return round(abs(($existing['value'] ?? 0) / $existing['quantity']), 5);
        }

        if ($difference < 0) {
            return round($this->valuationService->currentAverageCost($source['product_id'], $source['warehouse_id']), 5);
        }

        if ($existing && abs($existing['quantity']) > 0.000005) {
            return round(abs(($existing['value'] ?? 0) / $existing['quantity']), 5);
        }

        return round((float) ($context['unit_costs'][$source['product_id']]
            ?? $this->valuationService->currentAverageCost($source['product_id'], $source['warehouse_id'])), 5);
    }

    /**
     * @param  Closure(int, int, float, float): string  $message
     */
    protected function ensureAvailable(int $warehouseId, int $productId, float $quantity, Closure $message): void
    {
        $inventory = Inventory::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->lockForUpdate()
            ->first();

        $available = (float) ($inventory?->available_quantity ?? 0);

        if ($available + 0.000005 < $quantity) {
            throw new InventoryReversalException($message($warehouseId, $productId, $quantity, $available));
        }
    }
}
