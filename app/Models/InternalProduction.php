<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InternalProduction extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'company_id',
        'area_id',
        'warehouse_id',
        'employee_id',
        'production_number',
        'slug',
        'physical_document_number',
        'document_date',
        'subtotal',
        'total',
        'status',
        'approved_at',
        'approved_by',
        'approval_notes',
        'completed_at',
        'completed_by',
        'notes',
        'admin_notes',
        'attachments',
        'is_active',
        'active_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'subtotal' => 'decimal:5',
            'total' => 'decimal:5',
            'approved_at' => 'datetime',
            'completed_at' => 'datetime',
            'attachments' => 'array',
            'is_active' => 'boolean',
            'active_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($production) {
            if (empty($production->production_number)) {
                $production->production_number = self::generateProductionNumber($production->warehouse_id);
            }
            if (empty($production->slug)) {
                $production->slug = Str::slug($production->production_number);
            }
            if (auth()->check()) {
                $production->created_by = auth()->id();
            }
            if (is_null($production->active_at) && $production->is_active) {
                $production->active_at = now();
            }
        });

        static::updating(function ($production) {
            if (auth()->check()) {
                $production->updated_by = auth()->id();
            }
            if ($production->isDirty('is_active')) {
                $production->active_at = $production->is_active ? now() : null;
            }
        });

        static::deleting(function ($production) {
            if (auth()->check()) {
                $production->deleted_by = auth()->id();
                $production->save();
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['production_number', 'status', 'total', 'warehouse_id', 'area_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Produccion Interna '{$this->production_number}' {$eventName}");
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(InternalProductionDetail::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeByArea($query, int $areaId)
    {
        return $query->where('area_id', $areaId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pendiente');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'aprobado');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completado');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Generate the production number in format: PI-XX-BOD-YYY
     * Where XX is sequential and YYY is the warehouse code suffix.
     */
    public static function generateProductionNumber(int $warehouseId): string
    {
        $warehouse = Warehouse::find($warehouseId);

        if (! $warehouse || empty($warehouse->code)) {
            return 'PI-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        }

        $warehouseCodeSuffix = substr($warehouse->code, -3);

        $lastProduction = self::where('warehouse_id', $warehouseId)
            ->where('production_number', 'like', "PI-%-BOD-{$warehouseCodeSuffix}")
            ->orderByRaw('CAST(SUBSTRING(production_number, 4, 2) AS UNSIGNED) DESC')
            ->first();

        if ($lastProduction) {
            $lastNumber = (int) substr($lastProduction->production_number, 3, 2);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('PI-%02d-BOD-%s', $nextNumber, $warehouseCodeSuffix);
    }

    public function calculateTotals(): void
    {
        $this->subtotal = $this->details->sum(fn ($detail) => $detail->subtotal);
        $this->total = $this->details->sum(fn ($detail) => $detail->total);
        $this->save();
    }

    public function submit(): bool
    {
        if ($this->status !== 'borrador') {
            return false;
        }

        $this->status = 'pendiente';

        return $this->save();
    }

    public function approve(int $userId, ?string $notes = null): bool
    {
        if ($this->status !== 'pendiente') {
            return false;
        }

        $this->status = 'aprobado';
        $this->approved_by = $userId;
        $this->approved_at = now();
        $this->approval_notes = $notes;

        return $this->save();
    }

    public function complete(int $userId): bool
    {
        if ($this->status !== 'aprobado') {
            return false;
        }

        \DB::beginTransaction();
        try {
            $movementReason = MovementReason::where('code', 'PRODUCTION_IN')->first();

            if (! $movementReason) {
                $movementReason = MovementReason::where('movement_type', 'in')->first();
            }

            if (! $movementReason) {
                throw new \Exception('Movement reason for production not found');
            }

            foreach ($this->details as $detail) {
                $currentStock = InventoryMovement::where('warehouse_id', $this->warehouse_id)
                    ->where('product_id', $detail->product_id)
                    ->whereNotNull('balance_quantity')
                    ->orderBy('movement_date', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();

                $previousBalance = $currentStock ? $currentStock->balance_quantity : 0;
                $newBalance = $previousBalance + $detail->quantity;

                InventoryMovement::create([
                    'company_id' => $this->company_id,
                    'warehouse_id' => $this->warehouse_id,
                    'product_id' => $detail->product_id,
                    'movement_reason_id' => $movementReason->id,
                    'internal_production_id' => $this->id,
                    'movement_type' => 'production',
                    'movement_date' => now(),
                    'quantity' => $detail->quantity,
                    'quantity_in' => $detail->quantity,
                    'quantity_out' => 0,
                    'balance_quantity' => $newBalance,
                    'previous_quantity' => $previousBalance,
                    'new_quantity' => $newBalance,
                    'unit_cost' => $detail->unit_price,
                    'total_cost' => $detail->quantity * $detail->unit_price,
                    'document_type' => 'production',
                    'document_number' => $this->production_number,
                    'notes' => $detail->notes ?? "Produccion Interna {$this->production_number}",
                    'is_active' => true,
                    'active_at' => now(),
                    'created_by' => $userId,
                ]);

                // Update or create inventory record for stock tracking
                $inventory = Inventory::firstOrNew([
                    'product_id' => $detail->product_id,
                    'warehouse_id' => $this->warehouse_id,
                ]);

                $inventory->quantity = ($inventory->quantity ?? 0) + $detail->quantity;
                $inventory->available_quantity = ($inventory->available_quantity ?? 0) + $detail->quantity;
                $inventory->unit_cost = $detail->unit_price;
                $inventory->is_active = true;
                $inventory->active_at = $inventory->active_at ?? now();
                $inventory->save();
            }

            $this->status = 'completado';
            $this->completed_by = $userId;
            $this->completed_at = now();
            $this->save();

            \DB::commit();

            return true;
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error completing internal production: '.$e->getMessage());

            return false;
        }
    }

    public function cancel(): bool
    {
        if (in_array($this->status, ['completado', 'cancelado'])) {
            return false;
        }

        $this->status = 'cancelado';

        return $this->save();
    }

    public function getStatusSpanishAttribute(): string
    {
        $statuses = [
            'borrador' => 'Borrador',
            'pendiente' => 'Pendiente',
            'aprobado' => 'Aprobado',
            'completado' => 'Completado',
            'cancelado' => 'Anulado',
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, ['borrador', 'pendiente']);
    }

    public function canBeSubmitted(): bool
    {
        return $this->status === 'borrador';
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'pendiente';
    }

    public function canBeCompleted(): bool
    {
        return $this->status === 'aprobado';
    }

    public function canBeCancelled(): bool
    {
        return ! in_array($this->status, ['completado', 'cancelado']);
    }

    /**
     * Create an automatic dispatch from this internal production.
     * Uses predefined values: Tienda Ena (area_id: 154), Kevin Barrera (employee_id: 91)
     */
    public function createAutoDispatch(int $userId): ?Dispatch
    {
        // Predefined values for automatic dispatch
        $defaultAreaId = 154; // Tienda Ena
        $defaultEmployeeId = 91; // Kevin Ernesto Barrera Medina

        \DB::beginTransaction();
        try {
            // Create the dispatch header
            $dispatch = Dispatch::create([
                'company_id' => $this->company_id,
                'area_id' => $defaultAreaId,
                'warehouse_id' => $this->warehouse_id,
                'employee_id' => $defaultEmployeeId,
                'dispatch_type' => 'interno',
                'physical_document_number' => $this->physical_document_number,
                'document_date' => now(),
                'status' => 'aprobado',
                'approved_at' => now(),
                'approved_by' => $userId,
                'notes' => "Despacho automático desde Producción Interna {$this->production_number}",
                'is_active' => true,
                'active_at' => now(),
            ]);

            // Create dispatch details from internal production details
            foreach ($this->details as $detail) {
                DispatchDetail::create([
                    'dispatch_id' => $dispatch->id,
                    'product_id' => $detail->product_id,
                    'quantity' => $detail->quantity,
                    'unit_of_measure_id' => $detail->unit_of_measure_id,
                    'unit_price' => $detail->unit_price,
                    'notes' => $detail->notes,
                    'batch_number' => $detail->batch_number,
                    'expiration_date' => $detail->expiration_date,
                ]);
            }

            // Calculate totals
            $dispatch->calculateTotals();

            \DB::commit();

            return $dispatch;
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error creating auto dispatch from internal production: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Relationship to dispatches created from this production.
     */
    public function dispatches(): HasMany
    {
        return $this->hasMany(Dispatch::class, 'notes', 'production_number')
            ->where('notes', 'like', '%Producción Interna '.$this->production_number.'%');
    }
}
