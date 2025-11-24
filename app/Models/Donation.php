<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Donation extends Model
{
    /** @use HasFactory<\Database\Factories\DonationFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'donor_id',
        'donation_number',
        'slug',
        'donor_name',
        'donor_type',
        'donor_contact',
        'donor_email',
        'donor_phone',
        'donor_address',
        'document_type',
        'document_number',
        'document_date',
        'reception_date',
        'purpose',
        'intended_use',
        'project_name',
        'estimated_value',
        'tax_deduction_value',
        'status',
        'approved_at',
        'approved_by',
        'received_at',
        'received_by',
        'notes',
        'admin_notes',
        'conditions',
        'attachments',
        'tax_receipt_required',
        'tax_receipt_number',
        'tax_receipt_date',
        'is_active',
        'active_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'reception_date' => 'date',
            'estimated_value' => 'decimal:2',
            'tax_deduction_value' => 'decimal:2',
            'approved_at' => 'datetime',
            'received_at' => 'datetime',
            'tax_receipt_date' => 'date',
            'attachments' => 'array',
            'is_active' => 'boolean',
            'active_at' => 'datetime',
            'tax_receipt_required' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($donation) {
            if (empty($donation->donation_number)) {
                $donation->donation_number = 'DON-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
            }
            if (empty($donation->slug)) {
                $donation->slug = Str::slug($donation->donation_number);
            }
            if (auth()->check()) {
                $donation->created_by = auth()->id();
            }
            if (is_null($donation->active_at) && $donation->is_active) {
                $donation->active_at = now();
            }
        });

        static::updating(function ($donation) {
            if (auth()->check()) {
                $donation->updated_by = auth()->id();
            }
            if ($donation->isDirty('is_active')) {
                $donation->active_at = $donation->is_active ? now() : null;
            }
        });

        static::deleting(function ($donation) {
            if (auth()->check()) {
                $donation->deleted_by = auth()->id();
                $donation->save();
            }
        });
    }

    /**
     * Configure activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['donation_number', 'status', 'donor_name', 'estimated_value', 'warehouse_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Donación '{$this->donation_number}' {$eventName}");
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(DonationDetail::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
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

    public function inventoryMovement(): HasOne
    {
        return $this->hasOne(InventoryMovement::class);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDonorType($query, string $type)
    {
        return $query->where('donor_type', $type);
    }

    public function scopeByWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pendiente');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'aprobado');
    }

    public function scopeReceived($query)
    {
        return $query->where('status', 'recibido');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'borrador');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function calculateTotals(): void
    {
        $this->estimated_value = $this->details->sum('estimated_total_value');
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

    public function approve(int $userId): bool
    {
        if ($this->status !== 'pendiente') {
            return false;
        }

        $this->status = 'aprobado';
        $this->approved_by = $userId;
        $this->approved_at = now();

        return $this->save();
    }

    public function receive(int $userId): bool
    {
        if ($this->status !== 'aprobado') {
            return false;
        }

        // Use database transaction to ensure consistency
        \DB::beginTransaction();
        try {
            // Update donation status
            $this->status = 'recibido';
            $this->received_by = $userId;
            $this->received_at = now();
            $this->save();

            // Get the donation movement reason
            $movementReason = MovementReason::where('code', 'DONATION_IN')->firstOrFail();

            // Create inventory movements for each donation detail
            foreach ($this->details as $detail) {
                // Get current stock for this product in this warehouse
                $currentStock = InventoryMovement::where('warehouse_id', $this->warehouse_id)
                    ->where('product_id', $detail->product_id)
                    ->orderBy('movement_date', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();

                $previousBalance = $currentStock ? $currentStock->balance_quantity : 0;
                $newBalance = $previousBalance + $detail->quantity;

                // Create the inventory movement
                InventoryMovement::create([
                    'company_id' => $this->company_id,
                    'warehouse_id' => $this->warehouse_id,
                    'product_id' => $detail->product_id,
                    'movement_reason_id' => $movementReason->id,
                    'donation_id' => $this->id,
                    'movement_type' => 'purchase', // Using 'purchase' as it's an incoming inventory movement
                    'movement_date' => $this->received_at ?? now(),
                    'quantity' => $detail->quantity,
                    'quantity_in' => $detail->quantity,
                    'quantity_out' => 0,
                    'balance_quantity' => $newBalance,
                    'unit_cost' => $detail->estimated_unit_value,
                    'total_cost' => $detail->estimated_total_value,
                    'lot_number' => $detail->lot_number,
                    'expiration_date' => $detail->expiration_date,
                    'document_type' => $this->document_type,
                    'document_number' => $this->document_number,
                    'notes' => "Recepción de donación {$this->donation_number} de {$this->donor_name}",
                    'is_active' => true,
                    'active_at' => now(),
                    'created_by' => $userId,
                    'status' => 'completed',
                ]);

                // Update or create inventory record for stock tracking
                $inventory = Inventory::firstOrNew([
                    'product_id' => $detail->product_id,
                    'warehouse_id' => $this->warehouse_id,
                ]);

                $inventory->quantity = ($inventory->quantity ?? 0) + $detail->quantity;
                $inventory->unit_cost = $detail->estimated_unit_value;
                $inventory->lot_number = $detail->lot_number;
                $inventory->expiration_date = $detail->expiration_date;
                $inventory->is_active = true;
                $inventory->active_at = $inventory->active_at ?? now();
                $inventory->save();
            }

            \DB::commit();

            return true;
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error receiving donation: '.$e->getMessage());

            return false;
        }
    }

    public function cancel(): bool
    {
        if (in_array($this->status, ['recibido', 'cancelado'])) {
            return false;
        }

        $this->status = 'cancelado';

        return $this->save();
    }

    public function canBeSubmitted(): bool
    {
        return $this->status === 'borrador';
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'pendiente';
    }

    public function canBeReceived(): bool
    {
        return $this->status === 'aprobado';
    }

    public function canBeCancelled(): bool
    {
        return ! in_array($this->status, ['recibido', 'cancelado']);
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, ['borrador', 'pendiente']);
    }
}
