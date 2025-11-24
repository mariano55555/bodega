<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    // Cache TTL constants (in seconds)
    public const TTL_SHORT = 60;          // 1 minute

    public const TTL_MEDIUM = 300;        // 5 minutes

    public const TTL_LONG = 3600;         // 1 hour

    public const TTL_VERY_LONG = 86400;   // 24 hours

    // Cache key prefixes
    public const PREFIX_DASHBOARD = 'dashboard_';

    public const PREFIX_REPORTS = 'reports_';

    public const PREFIX_INVENTORY = 'inventory_';

    public const PREFIX_PRODUCTS = 'products_';

    /**
     * Clear all dashboard-related caches for a specific user
     */
    public static function clearDashboardCache(?int $userId = null): void
    {
        if ($userId) {
            Cache::forget(self::PREFIX_DASHBOARD."metrics_{$userId}_30");
            Cache::forget(self::PREFIX_DASHBOARD."chart_movements_{$userId}_30");
            Cache::forget(self::PREFIX_DASHBOARD."chart_value_{$userId}_30");
        }
    }

    /**
     * Clear all inventory-related caches for a company
     */
    public static function clearInventoryCache(?int $companyId = null): void
    {
        // Clear inventory summary caches
        Cache::tags([self::PREFIX_INVENTORY.($companyId ?? 'all')])->flush();
    }

    /**
     * Clear report caches
     */
    public static function clearReportCache(string $reportType, ?int $companyId = null): void
    {
        $key = self::PREFIX_REPORTS.$reportType;
        if ($companyId) {
            $key .= "_{$companyId}";
        }
        Cache::forget($key);
    }

    /**
     * Clear product listing caches
     */
    public static function clearProductCache(?int $companyId = null): void
    {
        if ($companyId) {
            Cache::forget(self::PREFIX_PRODUCTS."list_{$companyId}");
            Cache::forget(self::PREFIX_PRODUCTS."categories_{$companyId}");
        }
    }

    /**
     * Clear all caches (use sparingly, only for major operations)
     */
    public static function clearAllCaches(): void
    {
        Cache::flush();
    }

    /**
     * Remember a value in cache with standard error handling
     */
    public static function remember(string $key, int $ttl, callable $callback): mixed
    {
        try {
            return Cache::remember($key, $ttl, $callback);
        } catch (\Exception $e) {
            // If cache fails, just execute the callback directly
            report($e);

            return $callback();
        }
    }

    /**
     * Build a cache key with optional parameters
     */
    public static function buildKey(string $prefix, array $params = []): string
    {
        $key = $prefix;
        foreach ($params as $param) {
            $key .= '_'.($param ?? 'null');
        }

        return $key;
    }

    /**
     * Get cached report data or generate it
     */
    public static function getReport(string $reportType, array $filters, callable $generator, int $ttl = self::TTL_MEDIUM): mixed
    {
        $key = self::buildKey(self::PREFIX_REPORTS.$reportType, [
            $filters['company_id'] ?? null,
            $filters['date_from'] ?? null,
            $filters['date_to'] ?? null,
            md5(json_encode($filters)),
        ]);

        return self::remember($key, $ttl, $generator);
    }
}
