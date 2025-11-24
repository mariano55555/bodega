<?php

namespace App\Services;

use App\Models\ProductLot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class LotRotationService
{
    /**
     * Select lots for a product movement using specified rotation method.
     *
     * @param  int  $productId  Product ID
     * @param  float  $requiredQuantity  Required quantity
     * @param  string  $method  Rotation method (FIFO, FEFO)
     * @return array Array of selected lots with quantities
     */
    public function selectLots(int $productId, float $requiredQuantity, string $method = 'FIFO'): array
    {
        Log::info('Seleccionando lotes para movimiento', [
            'product_id' => $productId,
            'required_quantity' => $requiredQuantity,
            'method' => $method,
        ]);

        $availableLots = $this->getAvailableLots($productId, $method);

        if ($availableLots->isEmpty()) {
            Log::warning('No hay lotes disponibles para el producto', [
                'product_id' => $productId,
            ]);

            return [];
        }

        return $this->allocateQuantityToLots($availableLots, $requiredQuantity);
    }

    /**
     * Get available lots for a product sorted by rotation method.
     *
     * @param  int  $productId  Product ID
     * @param  string  $method  Rotation method
     * @return Collection Collection of available lots
     */
    private function getAvailableLots(int $productId, string $method): Collection
    {
        $query = ProductLot::forProduct($productId)
            ->available()
            ->where('quantity_remaining', '>', 0);

        return match (strtoupper($method)) {
            'FIFO' => $query->fifo()->get(),
            'FEFO' => $query->fefo()->get(),
            default => $query->fifo()->get(), // Default to FIFO
        };
    }

    /**
     * Allocate required quantity to available lots.
     *
     * @param  Collection  $lots  Available lots
     * @param  float  $requiredQuantity  Required quantity
     * @return array Array of lot allocations
     */
    private function allocateQuantityToLots(Collection $lots, float $requiredQuantity): array
    {
        $allocations = [];
        $remainingQuantity = $requiredQuantity;

        foreach ($lots as $lot) {
            if ($remainingQuantity <= 0) {
                break;
            }

            $availableInLot = $lot->quantity_remaining;
            $quantityToAllocate = min($remainingQuantity, $availableInLot);

            if ($quantityToAllocate > 0) {
                $allocations[] = [
                    'lot_id' => $lot->id,
                    'lot_number' => $lot->lot_number,
                    'available_quantity' => $availableInLot,
                    'allocated_quantity' => $quantityToAllocate,
                    'expiration_date' => $lot->expiration_date?->format('Y-m-d'),
                    'manufactured_date' => $lot->manufactured_date?->format('Y-m-d'),
                    'unit_cost' => $lot->unit_cost,
                ];

                $remainingQuantity -= $quantityToAllocate;
            }
        }

        Log::info('Asignación de lotes completada', [
            'required_quantity' => $requiredQuantity,
            'remaining_quantity' => $remainingQuantity,
            'allocated_lots' => count($allocations),
            'allocations' => $allocations,
        ]);

        return $allocations;
    }

    /**
     * Apply FIFO (First In, First Out) lot selection.
     *
     * @param  int  $productId  Product ID
     * @param  float  $requiredQuantity  Required quantity
     * @return array Array of selected lots
     */
    public function selectFifoLots(int $productId, float $requiredQuantity): array
    {
        return $this->selectLots($productId, $requiredQuantity, 'FIFO');
    }

    /**
     * Apply FEFO (First Expired, First Out) lot selection.
     *
     * @param  int  $productId  Product ID
     * @param  float  $requiredQuantity  Required quantity
     * @return array Array of selected lots
     */
    public function selectFefoLots(int $productId, float $requiredQuantity): array
    {
        return $this->selectLots($productId, $requiredQuantity, 'FEFO');
    }

    /**
     * Get lot rotation recommendations for a product.
     *
     * @param  int  $productId  Product ID
     * @return array Rotation recommendations
     */
    public function getRotationRecommendations(int $productId): array
    {
        $lots = ProductLot::forProduct($productId)
            ->available()
            ->where('quantity_remaining', '>', 0)
            ->fefo() // Order by expiration
            ->get();

        $recommendations = [];
        $now = now();

        foreach ($lots as $lot) {
            $daysUntilExpiration = $lot->daysUntilExpiration();
            $urgency = $this->calculateUrgency($daysUntilExpiration);

            $recommendations[] = [
                'lot_id' => $lot->id,
                'lot_number' => $lot->lot_number,
                'quantity_remaining' => $lot->quantity_remaining,
                'expiration_date' => $lot->expiration_date?->format('Y-m-d'),
                'days_until_expiration' => $daysUntilExpiration,
                'urgency' => $urgency,
                'recommendation' => $this->getRecommendationText($urgency, $daysUntilExpiration),
            ];
        }

        return [
            'product_id' => $productId,
            'total_lots' => $lots->count(),
            'total_quantity' => $lots->sum('quantity_remaining'),
            'lots' => $recommendations,
            'generated_at' => $now->toISOString(),
        ];
    }

    /**
     * Calculate urgency level based on days until expiration.
     *
     * @param  int|null  $daysUntilExpiration  Days until expiration
     * @return string Urgency level
     */
    private function calculateUrgency(?int $daysUntilExpiration): string
    {
        if ($daysUntilExpiration === null) {
            return 'none'; // No expiration date
        }

        if ($daysUntilExpiration < 0) {
            return 'expired';
        }

        if ($daysUntilExpiration <= 7) {
            return 'critical';
        }

        if ($daysUntilExpiration <= 30) {
            return 'high';
        }

        if ($daysUntilExpiration <= 90) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Get recommendation text based on urgency.
     *
     * @param  string  $urgency  Urgency level
     * @param  int|null  $daysUntilExpiration  Days until expiration
     * @return string Recommendation text in Spanish
     */
    private function getRecommendationText(string $urgency, ?int $daysUntilExpiration): string
    {
        return match ($urgency) {
            'expired' => 'VENCIDO - Remover del inventario inmediatamente',
            'critical' => "CRÍTICO - Usar en los próximos {$daysUntilExpiration} días",
            'high' => "ALTA - Priorizar uso en los próximos {$daysUntilExpiration} días",
            'medium' => 'MEDIA - Programar uso en las próximas semanas',
            'low' => 'BAJA - Uso normal según demanda',
            'none' => 'Sin fecha de vencimiento - Uso según FIFO',
            default => 'Revisar estado del lote',
        };
    }

    /**
     * Optimize lot allocation to minimize waste.
     *
     * @param  int  $productId  Product ID
     * @param  float  $requiredQuantity  Required quantity
     * @return array Optimized allocation
     */
    public function optimizeAllocation(int $productId, float $requiredQuantity): array
    {
        // Get lots sorted by expiration date (FEFO)
        $lots = $this->getAvailableLots($productId, 'FEFO');

        if ($lots->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No hay lotes disponibles',
                'allocations' => [],
            ];
        }

        // Try to find the most efficient allocation
        $allocation = $this->findOptimalAllocation($lots, $requiredQuantity);

        if ($allocation['total_allocated'] < $requiredQuantity) {
            Log::warning('No se pudo asignar la cantidad completa', [
                'product_id' => $productId,
                'required' => $requiredQuantity,
                'allocated' => $allocation['total_allocated'],
            ]);
        }

        return [
            'success' => $allocation['total_allocated'] >= $requiredQuantity,
            'message' => $allocation['total_allocated'] >= $requiredQuantity
                ? 'Asignación completada exitosamente'
                : 'Asignación parcial - Stock insuficiente',
            'required_quantity' => $requiredQuantity,
            'allocated_quantity' => $allocation['total_allocated'],
            'allocations' => $allocation['lots'],
            'waste_minimized' => $allocation['waste_minimized'],
        ];
    }

    /**
     * Find optimal allocation to minimize waste.
     *
     * @param  Collection  $lots  Available lots
     * @param  float  $requiredQuantity  Required quantity
     * @return array Optimal allocation
     */
    private function findOptimalAllocation(Collection $lots, float $requiredQuantity): array
    {
        $allocations = [];
        $remainingQuantity = $requiredQuantity;
        $wasteMinimized = false;

        // First, try to find exact matches or minimal waste
        foreach ($lots as $lot) {
            if ($remainingQuantity <= 0) {
                break;
            }

            $availableInLot = $lot->quantity_remaining;

            // If this lot exactly matches remaining quantity, use it
            if ($availableInLot == $remainingQuantity) {
                $allocations[] = [
                    'lot_id' => $lot->id,
                    'lot_number' => $lot->lot_number,
                    'allocated_quantity' => $remainingQuantity,
                    'available_quantity' => $availableInLot,
                    'expiration_date' => $lot->expiration_date?->format('Y-m-d'),
                    'waste_prevention' => true,
                ];
                $remainingQuantity = 0;
                $wasteMinimized = true;
                break;
            }

            // If lot has more than needed, check if using it minimizes overall waste
            if ($availableInLot > $remainingQuantity) {
                $daysUntilExpiration = $lot->daysUntilExpiration();

                // Use lot if it's expiring soon (within 30 days) to minimize waste
                if ($daysUntilExpiration !== null && $daysUntilExpiration <= 30) {
                    $allocations[] = [
                        'lot_id' => $lot->id,
                        'lot_number' => $lot->lot_number,
                        'allocated_quantity' => $remainingQuantity,
                        'available_quantity' => $availableInLot,
                        'expiration_date' => $lot->expiration_date?->format('Y-m-d'),
                        'waste_prevention' => true,
                        'days_until_expiration' => $daysUntilExpiration,
                    ];
                    $remainingQuantity = 0;
                    $wasteMinimized = true;
                    break;
                }
            }

            // Otherwise, use what's available in this lot
            if ($availableInLot <= $remainingQuantity) {
                $allocations[] = [
                    'lot_id' => $lot->id,
                    'lot_number' => $lot->lot_number,
                    'allocated_quantity' => $availableInLot,
                    'available_quantity' => $availableInLot,
                    'expiration_date' => $lot->expiration_date?->format('Y-m-d'),
                    'waste_prevention' => false,
                ];
                $remainingQuantity -= $availableInLot;
            }
        }

        return [
            'lots' => $allocations,
            'total_allocated' => $requiredQuantity - $remainingQuantity,
            'waste_minimized' => $wasteMinimized,
        ];
    }

    /**
     * Get expiration summary for all lots of a product.
     *
     * @param  int  $productId  Product ID
     * @return array Expiration summary
     */
    public function getExpirationSummary(int $productId): array
    {
        $lots = ProductLot::forProduct($productId)
            ->available()
            ->where('quantity_remaining', '>', 0)
            ->get();

        $summary = [
            'expired' => 0,
            'expiring_in_7_days' => 0,
            'expiring_in_30_days' => 0,
            'expiring_in_90_days' => 0,
            'no_expiration' => 0,
            'total_lots' => $lots->count(),
            'total_quantity' => $lots->sum('quantity_remaining'),
        ];

        foreach ($lots as $lot) {
            $daysUntilExpiration = $lot->daysUntilExpiration();

            if ($daysUntilExpiration === null) {
                $summary['no_expiration']++;
            } elseif ($daysUntilExpiration < 0) {
                $summary['expired']++;
            } elseif ($daysUntilExpiration <= 7) {
                $summary['expiring_in_7_days']++;
            } elseif ($daysUntilExpiration <= 30) {
                $summary['expiring_in_30_days']++;
            } elseif ($daysUntilExpiration <= 90) {
                $summary['expiring_in_90_days']++;
            }
        }

        return $summary;
    }
}
