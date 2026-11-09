<?php

namespace App\Services;

use App\Exceptions\InventoryReversalException;
use App\Models\Dispatch;
use App\Models\DispatchDetail;
use App\Models\DocumentCorrection;
use App\Models\InternalProduction;
use App\Models\InternalProductionDetail;
use App\Models\InventoryTransfer;
use App\Models\InventoryTransferDetail;
use App\Models\Product;
use App\Models\Warehouse;
use Closure;
use Illuminate\Support\Facades\DB;

/**
 * Corrects transfers, dispatches and internal productions that already moved stock.
 *
 * The document is changed and only the net difference of stock is moved, with movements dated on
 * the document date, so nothing is taken from stock twice. Every correction is audited.
 */
class DocumentCorrectionService
{
    public function __construct(protected DocumentStockService $stock) {}

    /**
     * @param  array{from_warehouse_id: int|string, to_warehouse_id: int|string, lines: array<int, array{product_id: int|string, quantity: float|int|string, notes?: string|null}>}  $data
     */
    public function correctTransfer(InventoryTransfer $transfer, array $data, string $reason, int $userId): DocumentCorrection
    {
        if (! in_array($transfer->status, ['in_transit', 'received'])) {
            throw new InventoryReversalException('Solo se pueden corregir traslados enviados o recibidos.');
        }

        return DB::transaction(function () use ($transfer, $data, $reason, $userId) {
            $transfer->load('details.product', 'fromWarehouse', 'toWarehouse');
            $companyId = (int) $transfer->fromWarehouse->company_id;
            $fromWarehouse = $this->warehouseOfCompany($data['from_warehouse_id'], $companyId);
            $toWarehouse = $this->warehouseOfCompany($data['to_warehouse_id'], $companyId);

            if ($fromWarehouse->id === $toWarehouse->id) {
                throw new InventoryReversalException('La bodega de origen y la de destino deben ser distintas.');
            }

            $lines = $this->normalizeLines($data['lines'], $companyId);
            $movements = $transfer->inventoryMovements()->get();
            $current = $this->stock->netEffects($movements);
            $movementDate = $transfer->document_date ?? $movements->min('movement_date') ?? now();

            $target = $this->stock->mergeEffects(
                $this->stock->effectsFromLines($fromWarehouse->id, $lines, -1),
                $transfer->status === 'received' ? $this->stock->effectsFromLines($toWarehouse->id, $lines, 1) : [],
            );

            $this->stock->validatePeriods($companyId, $movements, $target, $movementDate);

            // Goods enter the destination at the cost they left the origin with
            $unitCosts = [];
            foreach ($lines as $line) {
                $originKey = DocumentStockService::key((int) $transfer->from_warehouse_id, $line['product_id']);
                $unitCosts[$line['product_id']] = isset($current[$originKey])
                    ? round(abs($current[$originKey]['value'] / $current[$originKey]['quantity']), 5)
                    : app(InventoryValuationService::class)->currentAverageCost($line['product_id'], $fromWarehouse->id);
            }

            $before = $this->transferSnapshot($transfer);

            $movedNow = $this->stock->rebalance($current, $target, [
                'company_id' => $companyId,
                'movement_date' => $movementDate,
                'links' => ['transfer_id' => $transfer->id],
                'document_type' => 'transfer_correction',
                'inbound_type' => 'transfer_in',
                'outbound_type' => 'transfer_out',
                'inbound_reason' => $this->stock->reason('TRANSFER_IN', 'in', 'transfer'),
                'outbound_reason' => $this->stock->reason('TRANSFER_OUT', 'out', 'transfer'),
                'inbound_notes' => "Corrección de traslado {$transfer->transfer_number}: entrada",
                'outbound_notes' => "Corrección de traslado {$transfer->transfer_number}: salida",
                'unit_costs' => $unitCosts,
                'metadata' => ['correction' => true, 'reason' => $reason],
                'user_id' => $userId,
                'shortage_message' => $this->shortageMessage('traslado'),
            ]);

            $transfer->update([
                'from_warehouse_id' => $fromWarehouse->id,
                'to_warehouse_id' => $toWarehouse->id,
            ]);

            $transfer->details()->delete();
            foreach ($lines as $line) {
                InventoryTransferDetail::create([
                    'transfer_id' => $transfer->id,
                    'product_id' => $line['product_id'],
                    'quantity' => $line['quantity'],
                    'unit_cost' => $unitCosts[$line['product_id']],
                    'notes' => $line['notes'],
                ]);
            }

            return $this->audit($transfer, $companyId, $reason, $before, $this->transferSnapshot($transfer->fresh(['details.product', 'fromWarehouse', 'toWarehouse'])), $movedNow->pluck('id')->all());
        });
    }

    /**
     * @param  array{warehouse_id: int|string, lines: array<int, array{product_id: int|string, quantity: float|int|string, notes?: string|null}>}  $data
     */
    public function correctDispatch(Dispatch $dispatch, array $data, string $reason, int $userId): DocumentCorrection
    {
        if (! in_array($dispatch->status, ['despachado', 'entregado'])) {
            throw new InventoryReversalException('Solo se pueden corregir despachos despachados o entregados.');
        }

        return DB::transaction(function () use ($dispatch, $data, $reason, $userId) {
            $companyId = (int) $dispatch->company_id;
            $warehouse = $this->warehouseOfCompany($data['warehouse_id'], $companyId);
            $lines = $this->normalizeLines($data['lines'], $companyId);
            $before = $this->dispatchSnapshot($dispatch->load('details.product', 'warehouse'));

            $movedNow = $this->moveDispatchStock($dispatch, $warehouse, $lines, $reason, $userId);
            $this->rewriteDispatchDetails($dispatch, $warehouse, $lines);

            return $this->audit($dispatch, $companyId, $reason, $before, $this->dispatchSnapshot($dispatch->fresh(['details.product', 'warehouse'])), $movedNow);
        });
    }

    /**
     * Corrects the production and, together, the automatic dispatch generated from it.
     *
     * @param  array{warehouse_id: int|string, lines: array<int, array{product_id: int|string, quantity: float|int|string, unit_price: float|int|string, notes?: string|null}>}  $data
     */
    public function correctProduction(InternalProduction $production, array $data, string $reason, int $userId): DocumentCorrection
    {
        if ($production->status !== 'completado') {
            throw new InventoryReversalException('Solo se pueden corregir producciones completadas.');
        }

        return DB::transaction(function () use ($production, $data, $reason, $userId) {
            $companyId = (int) $production->company_id;
            $warehouse = $this->warehouseOfCompany($data['warehouse_id'], $companyId);
            $lines = $this->normalizeLines($data['lines'], $companyId, withPrice: true);
            $production->load('details.product', 'warehouse');

            $movements = $production->stockMovements()->get();
            $movementDate = $production->document_date ?? $movements->min('movement_date') ?? now();
            $target = $this->stock->effectsFromLines($warehouse->id, $lines, 1);

            $this->stock->validatePeriods($companyId, $movements, $target, $movementDate);

            $before = $this->productionSnapshot($production);

            $productionContext = [
                'company_id' => $companyId,
                'movement_date' => $movementDate,
                'links' => [],
                'document_type' => 'production',
                'document_number' => $production->production_number,
                'inbound_type' => 'production',
                'outbound_type' => 'adjustment',
                'inbound_reason' => $this->stock->reason('PROD_IN', 'in'),
                'outbound_reason' => $this->stock->reason('ADJ_NEG', 'out'),
                'inbound_notes' => "Corrección de producción interna {$production->production_number}: entrada",
                'outbound_notes' => "Corrección de producción interna {$production->production_number}: salida",
                'unit_costs' => collect($lines)->mapWithKeys(fn (array $line) => [$line['product_id'] => $line['unit_price']])->all(),
                'metadata' => ['correction' => true, 'reason' => $reason],
                'user_id' => $userId,
                'shortage_message' => $this->shortageMessage('producción'),
            ];

            $moveProduction = fn (string $direction) => $this->stock->rebalance(
                $this->stock->netEffects($production->stockMovements()->get()),
                $target,
                $productionContext + ['direction' => $direction],
            )->pluck('id')->all();

            // The automatic dispatch carries the same products out of the same warehouse
            $autoDispatches = $production->autoDispatches()->where('status', '!=', 'cancelado')->get();
            $movedDispatches = $autoDispatches->filter(fn (Dispatch $dispatch) => in_array($dispatch->status, ['despachado', 'entregado']));
            $dispatchesBefore = $autoDispatches->mapWithKeys(fn (Dispatch $dispatch) => [
                $dispatch->id => $this->dispatchSnapshot($dispatch->load('details.product', 'warehouse')),
            ]);

            // Every entry of both documents first, then every exit, so no warehouse runs short in between
            $movedNow = $moveProduction('in');
            foreach ($movedDispatches as $dispatch) {
                $movedNow = array_merge($movedNow, $this->moveDispatchStock($dispatch, $warehouse, $lines, $reason, $userId, 'in'));
            }
            $movedNow = array_merge($movedNow, $moveProduction('out'));
            foreach ($movedDispatches as $dispatch) {
                $movedNow = array_merge($movedNow, $this->moveDispatchStock($dispatch, $warehouse, $lines, $reason, $userId, 'out'));
            }

            $production->update(['warehouse_id' => $warehouse->id]);
            $production->details()->delete();
            foreach ($lines as $line) {
                InternalProductionDetail::create([
                    'internal_production_id' => $production->id,
                    'product_id' => $line['product_id'],
                    'quantity' => $line['quantity'],
                    'unit_of_measure_id' => $line['unit_of_measure_id'],
                    'unit_price' => $line['unit_price'],
                    'notes' => $line['notes'],
                ]);
            }
            $production->load('details');
            $production->calculateTotals();

            $correctedDispatches = [];
            foreach ($autoDispatches as $dispatch) {
                $this->rewriteDispatchDetails($dispatch, $warehouse, $lines);
                $correctedDispatches[] = [
                    'dispatch_number' => $dispatch->dispatch_number,
                    'before' => $dispatchesBefore[$dispatch->id],
                    'after' => $this->dispatchSnapshot($dispatch->fresh(['details.product', 'warehouse'])),
                ];
            }

            $after = $this->productionSnapshot($production->fresh(['details.product', 'warehouse']));
            $after['auto_dispatches'] = $correctedDispatches;

            return $this->audit($production, $companyId, $reason, $before, $after, $movedNow);
        });
    }

    /**
     * @param  array<int, array{product_id: int, quantity: float, notes: string|null}>  $lines
     * @return array<int, int> ids of the movements created
     */
    protected function moveDispatchStock(Dispatch $dispatch, Warehouse $warehouse, array $lines, string $reason, int $userId, ?string $direction = null): array
    {
        $companyId = (int) $dispatch->company_id;
        $movements = $dispatch->inventoryMovements()->get();
        $movementDate = $dispatch->document_date ?? $movements->min('movement_date') ?? now();
        $target = $this->stock->effectsFromLines($warehouse->id, $lines, -1);

        $this->stock->validatePeriods($companyId, $movements, $target, $movementDate);

        return $this->stock->rebalance($this->stock->netEffects($movements), $target, [
            'company_id' => $companyId,
            'movement_date' => $movementDate,
            'links' => ['dispatch_id' => $dispatch->id],
            'document_type' => 'dispatch_correction',
            'inbound_type' => 'return',
            'outbound_type' => $dispatch->outboundMovementType(),
            'inbound_reason' => $this->stock->reason('REENTRY', 'in'),
            'outbound_reason' => $this->stock->reason('DISPATCH', 'out'),
            'inbound_notes' => "Corrección de despacho {$dispatch->dispatch_number}: devolución a bodega",
            'outbound_notes' => "Corrección de despacho {$dispatch->dispatch_number}: salida de bodega",
            'metadata' => ['correction' => true, 'reason' => $reason],
            'user_id' => $userId,
            'shortage_message' => $this->shortageMessage('despacho'),
            'direction' => $direction,
        ])->pluck('id')->all();
    }

    /**
     * Rewrite the dispatch lines; the price of each line is the cost at which the product left the warehouse.
     *
     * @param  array<int, array{product_id: int, quantity: float, unit_of_measure_id: int, notes: string|null}>  $lines
     */
    protected function rewriteDispatchDetails(Dispatch $dispatch, Warehouse $warehouse, array $lines): void
    {
        $effects = $this->stock->netEffects($dispatch->inventoryMovements()->get());
        $hasLeft = in_array($dispatch->status, ['despachado', 'entregado']);

        $dispatch->update(['warehouse_id' => $warehouse->id]);
        $dispatch->details()->delete();

        foreach ($lines as $line) {
            $effect = $effects[DocumentStockService::key($warehouse->id, $line['product_id'])] ?? null;
            $unitPrice = $effect
                ? round(abs($effect['value'] / $effect['quantity']), 5)
                : ($line['unit_price'] ?? app(InventoryValuationService::class)->currentAverageCost($line['product_id'], $warehouse->id));

            DispatchDetail::create([
                'dispatch_id' => $dispatch->id,
                'product_id' => $line['product_id'],
                'quantity' => $line['quantity'],
                'quantity_dispatched' => $hasLeft ? $line['quantity'] : 0,
                'quantity_delivered' => $dispatch->status === 'entregado' ? $line['quantity'] : 0,
                'unit_of_measure_id' => $line['unit_of_measure_id'],
                'unit_price' => $unitPrice,
                'notes' => $line['notes'],
                'is_reserved' => $hasLeft,
            ]);
        }

        $dispatch->load('details');
        $dispatch->calculateTotals();
    }

    /**
     * Aggregate the lines by product and validate that every product belongs to the company.
     *
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<int, array{product_id: int, quantity: float, unit_price: float|null, unit_of_measure_id: int, notes: string|null}>
     */
    protected function normalizeLines(array $lines, int $companyId, bool $withPrice = false): array
    {
        $normalized = [];

        foreach ($lines as $line) {
            $productId = (int) ($line['product_id'] ?? 0);
            $quantity = round((float) ($line['quantity'] ?? 0), 5);

            if ($productId === 0) {
                continue;
            }

            if ($quantity <= 0) {
                throw new InventoryReversalException('Cada producto debe tener una cantidad mayor a cero.');
            }

            if (isset($normalized[$productId])) {
                $normalized[$productId]['quantity'] += $quantity;

                continue;
            }

            $product = Product::query()->where('company_id', $companyId)->find($productId);

            if (! $product) {
                throw new InventoryReversalException("El producto #{$productId} no pertenece a la empresa del documento.");
            }

            if (! $product->unit_of_measure_id) {
                throw new InventoryReversalException("El producto {$product->name} no tiene unidad de medida.");
            }

            $normalized[$productId] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_price' => $withPrice ? round(max(0, (float) ($line['unit_price'] ?? 0)), 5) : null,
                'unit_of_measure_id' => (int) $product->unit_of_measure_id,
                'notes' => filled($line['notes'] ?? null) ? (string) $line['notes'] : null,
            ];
        }

        if ($normalized === []) {
            throw new InventoryReversalException('El documento debe conservar al menos un producto. Para quitarlos todos, anúlelo.');
        }

        return array_values($normalized);
    }

    protected function warehouseOfCompany(int|string|null $warehouseId, int $companyId): Warehouse
    {
        $warehouse = Warehouse::query()->where('company_id', $companyId)->find($warehouseId);

        if (! $warehouse) {
            throw new InventoryReversalException('La bodega seleccionada no pertenece a la empresa del documento.');
        }

        return $warehouse;
    }

    /**
     * @return Closure(int, int, float, float): string
     */
    protected function shortageMessage(string $documentLabel): Closure
    {
        return fn (int $warehouseId, int $productId, float $needed, float $available) => "No se puede corregir el {$documentLabel}: la bodega "
            .(Warehouse::find($warehouseId)?->name ?? "#{$warehouseId}").' necesita entregar '.number_format($needed, 2)
            .' unidades de '.(Product::find($productId)?->name ?? "Producto #{$productId}").' y solo tiene '.number_format($available, 2).' disponibles.';
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  array<int, int>  $movementIds
     */
    protected function audit(InventoryTransfer|Dispatch|InternalProduction $document, int $companyId, string $reason, array $before, array $after, array $movementIds): DocumentCorrection
    {
        return DocumentCorrection::create([
            'company_id' => $companyId,
            'correctable_type' => $document->getMorphClass(),
            'correctable_id' => $document->id,
            'reason' => $reason,
            'before' => $before,
            'after' => $after,
            'movement_ids' => $movementIds,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function transferSnapshot(InventoryTransfer $transfer): array
    {
        return [
            'from_warehouse' => $transfer->fromWarehouse?->name,
            'to_warehouse' => $transfer->toWarehouse?->name,
            'lines' => $transfer->details->map(fn ($detail) => [
                'product' => $detail->product?->name,
                'quantity' => (float) $detail->quantity,
                'unit_cost' => (float) $detail->unit_cost,
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function dispatchSnapshot(Dispatch $dispatch): array
    {
        return [
            'warehouse' => $dispatch->warehouse?->name,
            'lines' => $dispatch->details->map(fn ($detail) => [
                'product' => $detail->product?->name,
                'quantity' => (float) $detail->quantity,
                'unit_price' => (float) $detail->unit_price,
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function productionSnapshot(InternalProduction $production): array
    {
        return [
            'warehouse' => $production->warehouse?->name,
            'lines' => $production->details->map(fn ($detail) => [
                'product' => $detail->product?->name,
                'quantity' => (float) $detail->quantity,
                'unit_price' => (float) $detail->unit_price,
            ])->values()->all(),
        ];
    }
}
