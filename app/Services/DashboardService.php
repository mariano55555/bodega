<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryAlert;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    // Cache TTL in seconds (5 minutes for dashboard data)
    protected const CACHE_TTL = 300;

    public function __construct(protected User $user) {}

    /**
     * Get comprehensive dashboard metrics based on user role
     */
    public function getMetrics(int $days = 30): array
    {
        $companyId = $this->user->isSuperAdmin() ? null : $this->user->company_id;
        $cacheKey = "dashboard_metrics_{$this->user->id}_{$days}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($companyId, $days) {
            return [
                'overview' => $this->getOverviewMetrics($companyId),
                'inventory_value' => $this->getInventoryValueMetrics($companyId),
                'movements' => $this->getMovementMetrics($companyId, $days),
                'alerts' => $this->getAlertMetrics($companyId),
                'top_products' => $this->getTopProducts($companyId, $days),
                'low_stock_count' => $this->getLowStockCount($companyId),
                'warehouse_utilization' => $this->getWarehouseUtilization($companyId),
            ];
        });
    }

    /**
     * Clear cached metrics for a user
     */
    public static function clearCache(?int $userId = null): void
    {
        if ($userId) {
            Cache::forget("dashboard_metrics_{$userId}_30");
            Cache::forget("dashboard_chart_movements_{$userId}_30");
            Cache::forget("dashboard_chart_value_{$userId}_30");
        } else {
            // Clear all dashboard caches (for admin operations)
            Cache::flush();
        }
    }

    /**
     * Get overview metrics (products, warehouses, value)
     */
    protected function getOverviewMetrics(?int $companyId): array
    {
        $productQuery = Product::active();
        $warehouseQuery = Warehouse::active();
        $inventoryQuery = Inventory::query();

        if ($companyId) {
            $productQuery->where('company_id', $companyId);
            $warehouseQuery->where('company_id', $companyId);
            $inventoryQuery->whereHas('warehouse', fn ($q) => $q->where('company_id', $companyId));
        }

        $totalValue = $inventoryQuery->sum('total_value') ?? 0;
        $totalQuantity = $inventoryQuery->sum('quantity') ?? 0;

        return [
            'total_products' => $productQuery->count(),
            'total_warehouses' => $warehouseQuery->count(),
            'total_value' => $totalValue,
            'total_quantity' => $totalQuantity,
            'unique_skus' => $productQuery->distinct('sku')->count('sku'),
        ];
    }

    /**
     * Get inventory value breakdown by category
     */
    protected function getInventoryValueMetrics(?int $companyId): array
    {
        $query = Inventory::query()
            ->join('products', 'inventory.product_id', '=', 'products.id')
            ->join('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->select('product_categories.name as category', DB::raw('SUM(inventory.total_value) as value'))
            ->groupBy('product_categories.id', 'product_categories.name')
            ->orderByDesc('value')
            ->limit(10);

        if ($companyId) {
            $query->where('products.company_id', $companyId);
        }

        return $query->get()->toArray();
    }

    /**
     * Get movement metrics and trends
     */
    protected function getMovementMetrics(?int $companyId, int $days): array
    {
        $dateFrom = now()->subDays($days);

        $query = InventoryMovement::where('movement_date', '>=', $dateFrom);

        if ($companyId) {
            $query->whereHas('warehouse', fn ($q) => $q->where('company_id', $companyId));
        }

        $movements = $query->get();

        $totalMovements = $movements->count();
        $entries = $movements->whereIn('movement_type', ['entry', 'purchase', 'transfer_in', 'adjustment_in'])->count();
        $exits = $movements->whereIn('movement_type', ['exit', 'dispatch', 'transfer_out', 'adjustment_out'])->count();
        $avgDaily = $totalMovements > 0 ? round($totalMovements / $days, 1) : 0;

        return [
            'total' => $totalMovements,
            'entries' => $entries,
            'exits' => $exits,
            'avg_daily' => $avgDaily,
            'trend' => $this->calculateTrend($companyId, $days),
        ];
    }

    /**
     * Calculate movement trend (comparing to previous period)
     */
    protected function calculateTrend(?int $companyId, int $days): float
    {
        $currentPeriodStart = now()->subDays($days);
        $previousPeriodStart = now()->subDays($days * 2);
        $previousPeriodEnd = $currentPeriodStart->copy();

        $currentQuery = InventoryMovement::where('movement_date', '>=', $currentPeriodStart);
        $previousQuery = InventoryMovement::whereBetween('movement_date', [$previousPeriodStart, $previousPeriodEnd]);

        if ($companyId) {
            $currentQuery->whereHas('warehouse', fn ($q) => $q->where('company_id', $companyId));
            $previousQuery->whereHas('warehouse', fn ($q) => $q->where('company_id', $companyId));
        }

        $currentCount = $currentQuery->count();
        $previousCount = $previousQuery->count();

        if ($previousCount === 0) {
            return $currentCount > 0 ? 100.0 : 0.0;
        }

        return round((($currentCount - $previousCount) / $previousCount) * 100, 1);
    }

    /**
     * Get alert metrics
     */
    protected function getAlertMetrics(?int $companyId): array
    {
        $query = InventoryAlert::where('is_resolved', false);

        if ($companyId) {
            $query->whereHas('warehouse', fn ($q) => $q->where('company_id', $companyId));
        }

        $alerts = $query->get();

        return [
            'total' => $alerts->count(),
            'critical' => $alerts->where('priority', 'critical')->count(),
            'high' => $alerts->where('priority', 'high')->count(),
            'medium' => $alerts->where('priority', 'medium')->count(),
            'low' => $alerts->where('priority', 'low')->count(),
            'by_type' => [
                'low_stock' => $alerts->where('alert_type', 'low_stock')->count(),
                'out_of_stock' => $alerts->where('alert_type', 'out_of_stock')->count(),
                'expiring_soon' => $alerts->where('alert_type', 'expiring_soon')->count(),
                'expired' => $alerts->where('alert_type', 'expired')->count(),
                'stock_overflow' => $alerts->where('alert_type', 'stock_overflow')->count(),
                'closed_period' => $alerts->where('alert_type', 'closed_period')->count(),
            ],
        ];
    }

    /**
     * Get top products by movement frequency
     */
    protected function getTopProducts(?int $companyId, int $days): array
    {
        $dateFrom = now()->subDays($days);

        $query = InventoryMovement::select('product_id', DB::raw('COUNT(*) as movement_count'))
            ->where('movement_date', '>=', $dateFrom)
            ->groupBy('product_id')
            ->orderByDesc('movement_count')
            ->limit(10)
            ->with('product:id,name,sku');

        if ($companyId) {
            $query->whereHas('warehouse', fn ($q) => $q->where('company_id', $companyId));
        }

        return $query->get()->map(function ($item) {
            return [
                'product_name' => $item->product?->name ?? 'Desconocido',
                'product_sku' => $item->product?->sku ?? 'N/A',
                'movement_count' => $item->movement_count,
            ];
        })->toArray();
    }

    /**
     * Get low stock count
     */
    protected function getLowStockCount(?int $companyId): int
    {
        $query = Inventory::join('products', 'inventory.product_id', '=', 'products.id')
            ->whereColumn('inventory.quantity', '<=', DB::raw('COALESCE(products.minimum_stock, 0)'))
            ->where('inventory.quantity', '>', 0);

        if ($companyId) {
            $query->where('products.company_id', $companyId);
        }

        return $query->count();
    }

    /**
     * Get warehouse utilization data
     */
    protected function getWarehouseUtilization(?int $companyId): array
    {
        $query = Warehouse::active()
            ->select('id', 'name', 'total_capacity', 'capacity_unit')
            ->orderBy('name');

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->get()->map(function ($warehouse) {
            // Calculate current usage from inventory quantities
            $currentUsage = Inventory::where('warehouse_id', $warehouse->id)
                ->sum('quantity') ?? 0;

            $utilization = $warehouse->total_capacity > 0
                ? round(($currentUsage / $warehouse->total_capacity) * 100, 1)
                : 0;

            return [
                'name' => $warehouse->name,
                'capacity' => $warehouse->total_capacity,
                'current_usage' => $currentUsage,
                'utilization_percent' => $utilization,
                'capacity_unit' => $warehouse->capacity_unit ?? 'm³',
            ];
        })->toArray();
    }

    /**
     * Get chart data for movements over time (last N days)
     */
    public function getMovementChartData(int $days = 30): array
    {
        $companyId = $this->user->isSuperAdmin() ? null : $this->user->company_id;
        $cacheKey = "dashboard_chart_movements_{$this->user->id}_{$days}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($companyId, $days) {
            return $this->buildMovementChartData($companyId, $days);
        });
    }

    /**
     * Build movement chart data (uncached)
     */
    protected function buildMovementChartData(?int $companyId, int $days): array
    {
        $dateFrom = now()->subDays($days - 1)->startOfDay();

        $query = InventoryMovement::select(
            DB::raw('DATE(movement_date) as date'),
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(CASE WHEN movement_type IN ("entry", "purchase", "transfer_in", "adjustment_in") THEN 1 ELSE 0 END) as entries'),
            DB::raw('SUM(CASE WHEN movement_type IN ("exit", "dispatch", "transfer_out", "adjustment_out") THEN 1 ELSE 0 END) as exits')
        )
            ->where('movement_date', '>=', $dateFrom)
            ->groupBy('date')
            ->orderBy('date');

        if ($companyId) {
            $query->whereHas('warehouse', fn ($q) => $q->where('company_id', $companyId));
        }

        $data = $query->get()->keyBy('date');

        // Fill in missing dates with zero values
        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $result[] = [
                'date' => $date,
                'total' => $data->get($date)?->total ?? 0,
                'entries' => $data->get($date)?->entries ?? 0,
                'exits' => $data->get($date)?->exits ?? 0,
            ];
        }

        return $result;
    }

    /**
     * Get inventory value chart data over time (last N days)
     */
    public function getInventoryValueChartData(int $days = 30): array
    {
        $companyId = $this->user->isSuperAdmin() ? null : $this->user->company_id;
        $cacheKey = "dashboard_chart_value_{$this->user->id}_{$days}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($companyId, $days) {
            return $this->buildInventoryValueChartData($companyId, $days);
        });
    }

    /**
     * Build inventory value chart data (uncached)
     */
    protected function buildInventoryValueChartData(?int $companyId, int $days): array
    {
        // For simplicity, we'll show current value with some historical simulation
        // In a real scenario, you'd track value changes in a separate table
        $query = Inventory::query();

        if ($companyId) {
            $query->whereHas('warehouse', fn ($q) => $q->where('company_id', $companyId));
        }

        $currentValue = $query->sum('total_value') ?? 0;

        // Generate a trend for visualization (in production, use historical data)
        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            // Simulate slight variations (replace with real data in production)
            $variance = rand(-3, 5) / 100;
            $value = $currentValue * (1 + ($variance * $i / $days));

            $result[] = [
                'date' => $date,
                'value' => round($value, 2),
            ];
        }

        return $result;
    }

    /**
     * Get recent activities for timeline
     */
    public function getRecentActivities(int $limit = 10): array
    {
        $companyId = $this->user->isSuperAdmin() ? null : $this->user->company_id;

        $query = InventoryMovement::with(['product:id,name,sku', 'warehouse:id,name', 'creator:id,name'])
            ->latest()
            ->limit($limit);

        if ($companyId) {
            $query->whereHas('warehouse', fn ($q) => $q->where('company_id', $companyId));
        }

        return $query->get()->map(function ($movement) {
            return [
                'id' => $movement->id,
                'type' => $movement->movement_type,
                'type_label' => $movement->movement_type_spanish ?? ucfirst(str_replace('_', ' ', $movement->movement_type)),
                'product' => $movement->product?->name ?? 'Desconocido',
                'warehouse' => $movement->warehouse?->name ?? 'Desconocida',
                'quantity' => $movement->quantity,
                'user' => $movement->creator?->name ?? 'Sistema',
                'date' => $movement->movement_date,
                'created_at' => $movement->created_at,
            ];
        })->toArray();
    }
}
