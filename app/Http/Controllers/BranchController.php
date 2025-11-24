<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Models\Branch;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BranchController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Branch::class, 'branch');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Branch::query()
            ->with(['company', 'creator', 'updater'])
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

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('is_main_branch')) {
            $query->where('is_main_branch', $request->boolean('is_main_branch'));
        }

        // Apply sorting
        $sortBy = $request->input('sort_by', 'name');
        $sortDirection = $request->input('sort_direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);

        // Paginate or get all
        if ($request->filled('per_page')) {
            $branches = $query->paginate($request->input('per_page', 15));
        } else {
            $branches = $query->get();
        }

        return response()->json([
            'success' => true,
            'data' => $branches,
            'message' => 'Sucursales obtenidas exitosamente.',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBranchRequest $request): JsonResponse
    {
        try {
            // Ensure the branch belongs to the user's company
            $data = $request->validated();
            $data['company_id'] = auth()->user()->company_id;

            $branch = Branch::create($data);

            $branch->load(['company', 'creator']);

            return response()->json([
                'success' => true,
                'data' => $branch,
                'message' => 'Sucursal creada exitosamente.',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la sucursal: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Branch $branch): JsonResponse
    {
        $branch->load([
            'company',
            'creator',
            'updater',
            'users',
            'warehouses',
            'storageLocations',
        ]);

        return response()->json([
            'success' => true,
            'data' => $branch,
            'message' => 'Sucursal obtenida exitosamente.',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBranchRequest $request, Branch $branch): JsonResponse
    {
        try {
            $data = $request->validated();

            // Ensure the branch still belongs to the user's company
            $data['company_id'] = auth()->user()->company_id;

            $branch->update($data);

            $branch->load(['company', 'creator', 'updater']);

            return response()->json([
                'success' => true,
                'data' => $branch,
                'message' => 'Sucursal actualizada exitosamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la sucursal: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Branch $branch): JsonResponse
    {
        try {
            // Check if branch has warehouses or other dependencies
            if ($branch->warehouses()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar la sucursal porque tiene almacenes asociados.',
                ], 422);
            }

            if ($branch->users()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar la sucursal porque tiene usuarios asociados.',
                ], 422);
            }

            $branch->delete();

            return response()->json([
                'success' => true,
                'message' => 'Sucursal eliminada exitosamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la sucursal: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get branches for a specific company (for filters/dropdowns).
     */
    public function getByCompany(Request $request): JsonResponse
    {
        $companyId = $request->input('company_id', auth()->user()->company_id);

        // Authorization check
        if (! Gate::allows('filterByCompany', [Branch::class, $companyId])) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado para acceder a las sucursales de esta empresa.',
            ], 403);
        }

        $branches = Branch::where('company_id', $companyId)
            ->active()
            ->select('id', 'name', 'code', 'type', 'is_main_branch')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $branches,
            'message' => 'Sucursales obtenidas exitosamente.',
        ]);
    }

    /**
     * Toggle the active status of a branch.
     */
    public function toggleStatus(Branch $branch): JsonResponse
    {
        try {
            $branch->update([
                'is_active' => ! $branch->is_active,
            ]);

            return response()->json([
                'success' => true,
                'data' => $branch,
                'message' => $branch->is_active
                    ? 'Sucursal activada exitosamente.'
                    : 'Sucursal desactivada exitosamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado de la sucursal: '.$e->getMessage(),
            ], 500);
        }
    }
}
