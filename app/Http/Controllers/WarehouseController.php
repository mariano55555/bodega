<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Models\Branch;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WarehouseController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Warehouse::class, 'warehouse');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Warehouse::query()
            ->with(['company', 'branch', 'manager', 'creator', 'updater'])
            ->where('company_id', auth()->user()->company_id);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('capacity_unit')) {
            $query->where('capacity_unit', $request->input('capacity_unit'));
        }

        if ($request->filled('manager_id')) {
            $query->where('manager_id', $request->input('manager_id'));
        }

        // Apply sorting
        $sortBy = $request->input('sort_by', 'name');
        $sortDirection = $request->input('sort_direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);

        // Paginate or get all
        if ($request->filled('per_page')) {
            $warehouses = $query->paginate($request->input('per_page', 15));
        } else {
            $warehouses = $query->get();
        }

        return response()->json([
            'success' => true,
            'data' => $warehouses,
            'message' => 'Almacenes obtenidos exitosamente.',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        try {
            // Ensure the warehouse belongs to the user's company
            $data = $request->validated();
            $data['company_id'] = auth()->user()->company_id;

            // Validate branch belongs to the same company if provided
            if (isset($data['branch_id'])) {
                $branch = Branch::where('id', $data['branch_id'])
                    ->where('company_id', auth()->user()->company_id)
                    ->first();

                if (! $branch) {
                    return response()->json([
                        'success' => false,
                        'message' => 'La sucursal seleccionada no pertenece a su empresa.',
                    ], 422);
                }
            }

            $warehouse = Warehouse::create($data);

            $warehouse->load(['company', 'branch', 'manager', 'creator']);

            return response()->json([
                'success' => true,
                'data' => $warehouse,
                'message' => 'Almacén creado exitosamente.',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el almacén: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Warehouse $warehouse): JsonResponse
    {
        $warehouse->load([
            'company',
            'branch',
            'manager',
            'creator',
            'updater',
            'storageLocations',
            'inventory',
            'outgoingTransfers',
            'incomingTransfers',
        ]);

        return response()->json([
            'success' => true,
            'data' => $warehouse,
            'message' => 'Almacén obtenido exitosamente.',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): JsonResponse
    {
        try {
            $data = $request->validated();

            // Ensure the warehouse still belongs to the user's company
            $data['company_id'] = auth()->user()->company_id;

            // Validate branch belongs to the same company if provided
            if (isset($data['branch_id'])) {
                $branch = Branch::where('id', $data['branch_id'])
                    ->where('company_id', auth()->user()->company_id)
                    ->first();

                if (! $branch) {
                    return response()->json([
                        'success' => false,
                        'message' => 'La sucursal seleccionada no pertenece a su empresa.',
                    ], 422);
                }
            }

            $warehouse->update($data);

            $warehouse->load(['company', 'branch', 'manager', 'creator', 'updater']);

            return response()->json([
                'success' => true,
                'data' => $warehouse,
                'message' => 'Almacén actualizado exitosamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el almacén: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Warehouse $warehouse): JsonResponse
    {
        try {
            // Check if warehouse has inventory or other dependencies
            if ($warehouse->inventory()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el almacén porque tiene inventario activo.',
                ], 422);
            }

            if ($warehouse->storageLocations()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el almacén porque tiene ubicaciones de almacenamiento asociadas.',
                ], 422);
            }

            // Check for pending transfers
            $pendingTransfers = $warehouse->outgoingTransfers()
                ->whereIn('status', ['pending', 'in_transit'])
                ->count() + $warehouse->incomingTransfers()
                ->whereIn('status', ['pending', 'in_transit'])
                ->count();

            if ($pendingTransfers > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el almacén porque tiene transferencias pendientes.',
                ], 422);
            }

            $warehouse->delete();

            return response()->json([
                'success' => true,
                'message' => 'Almacén eliminado exitosamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el almacén: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get warehouses for a specific company (for filters/dropdowns).
     */
    public function getByCompany(Request $request): JsonResponse
    {
        $companyId = $request->input('company_id', auth()->user()->company_id);

        // Authorization check
        if (! Gate::allows('filterByCompany', [Warehouse::class, $companyId])) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado para acceder a los almacenes de esta empresa.',
            ], 403);
        }

        $warehouses = Warehouse::where('company_id', $companyId)
            ->active()
            ->with('branch:id,name')
            ->select('id', 'name', 'code', 'branch_id', 'total_capacity', 'capacity_unit')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $warehouses,
            'message' => 'Almacenes obtenidos exitosamente.',
        ]);
    }

    /**
     * Get warehouses for a specific branch.
     */
    public function getByBranch(Request $request, int $branchId): JsonResponse
    {
        // Validate branch belongs to user's company
        $branch = Branch::where('id', $branchId)
            ->where('company_id', auth()->user()->company_id)
            ->first();

        if (! $branch) {
            return response()->json([
                'success' => false,
                'message' => 'La sucursal no existe o no pertenece a su empresa.',
            ], 404);
        }

        $warehouses = Warehouse::where('branch_id', $branchId)
            ->active()
            ->select('id', 'name', 'code', 'total_capacity', 'capacity_unit')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $warehouses,
            'message' => 'Almacenes de la sucursal obtenidos exitosamente.',
        ]);
    }

    /**
     * Toggle the active status of a warehouse.
     */
    public function toggleStatus(Warehouse $warehouse): JsonResponse
    {
        try {
            $warehouse->update([
                'is_active' => ! $warehouse->is_active,
            ]);

            return response()->json([
                'success' => true,
                'data' => $warehouse,
                'message' => $warehouse->is_active
                    ? 'Almacén activado exitosamente.'
                    : 'Almacén desactivado exitosamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado del almacén: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get warehouse capacity summary.
     */
    public function getCapacitySummary(Warehouse $warehouse): JsonResponse
    {
        // This would require inventory calculations
        // For now, return basic capacity info
        $summary = [
            'total_capacity' => $warehouse->total_capacity,
            'capacity_unit' => $warehouse->capacity_unit,
            'used_capacity' => 0, // Calculate from inventory
            'available_capacity' => $warehouse->total_capacity,
            'utilization_percentage' => 0,
        ];

        return response()->json([
            'success' => true,
            'data' => $summary,
            'message' => 'Resumen de capacidad obtenido exitosamente.',
        ]);
    }

    /**
     * Get capacity summary for all warehouses in user's company.
     */
    public function capacitySummary(Request $request): JsonResponse
    {
        $query = Warehouse::where('company_id', auth()->user()->company_id)
            ->active()
            ->with('branch:id,name');

        // Filter by branch if provided
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        $warehouses = $query->get(['id', 'name', 'code', 'branch_id', 'total_capacity', 'capacity_unit']);

        $summary = $warehouses->map(function ($warehouse) {
            return [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'code' => $warehouse->code,
                'branch' => $warehouse->branch?->name,
                'total_capacity' => $warehouse->total_capacity,
                'capacity_unit' => $warehouse->capacity_unit,
                'used_capacity' => 0, // Calculate from inventory
                'available_capacity' => $warehouse->total_capacity,
                'utilization_percentage' => 0,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $summary,
            'message' => 'Resumen de capacidad de almacenes obtenido exitosamente.',
        ]);
    }
}
