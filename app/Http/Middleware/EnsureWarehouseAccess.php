<?php

namespace App\Http\Middleware;

use App\Models\Warehouse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWarehouseAccess
{
    /**
     * Handle an incoming request.
     *
     * Ensures the authenticated user has access to the requested warehouse.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $parameterName = 'warehouse'): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'No autenticado.');
        }

        // Get warehouse from route parameter
        $warehouseId = $request->route($parameterName);

        if (! $warehouseId) {
            // If no warehouse parameter, allow (might be index route)
            return $next($request);
        }

        // If warehouse is a model binding, get the ID
        if ($warehouseId instanceof Warehouse) {
            $warehouseId = $warehouseId->id;
        }

        // Check if user has access to this warehouse
        $hasAccess = $user->warehouseAccess()
            ->where('warehouse_id', $warehouseId)
            ->where('is_active', true)
            ->exists();

        if (! $hasAccess && ! $user->hasRole('Super Admin')) {
            abort(403, 'No tienes acceso a esta bodega.');
        }

        return $next($request);
    }
}
