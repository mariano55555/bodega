<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DteImport extends Model
{
    /** @use HasFactory<\Database\Factories\DteImportFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'slug',
        'codigo_generacion',
        'numero_control',
        'tipo_dte',
        'fecha_emision',
        'hora_emision',
        'emisor_nit',
        'emisor_nrc',
        'emisor_nombre',
        'total_gravado',
        'total_iva',
        'total_pagar',
        'json_original',
        'supplier_id',
        'purchase_id',
        'status',
        'processing_notes',
        'mapping_data',
        'processed_at',
        'is_active',
        'active_at',
        'created_by',
        'updated_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_emision' => 'date',
            'hora_emision' => 'datetime:H:i:s',
            'total_gravado' => 'decimal:2',
            'total_iva' => 'decimal:2',
            'total_pagar' => 'decimal:2',
            'json_original' => 'array',
            'mapping_data' => 'array',
            'processed_at' => 'datetime',
            'is_active' => 'boolean',
            'active_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->codigo_generacion);
            }
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
            if (is_null($model->active_at) && $model->is_active) {
                $model->active_at = now();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
            if ($model->isDirty('is_active')) {
                $model->active_at = $model->is_active ? now() : null;
            }
        });

        static::deleting(function ($model) {
            if (auth()->check()) {
                $model->deleted_by = auth()->id();
                $model->save();
            }
        });
    }

    /**
     * Configure activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'supplier_id', 'purchase_id', 'processed_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "DTE Import '{$this->codigo_generacion}' {$eventName}");
    }

    /**
     * Get the company that owns this record.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the supplier associated with this DTE.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the purchase created from this DTE.
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Get the user who created this record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this record.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted this record.
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter pending imports.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to filter by company.
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Get items from the cuerpoDocumento of the JSON.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getItemsAttribute(): array
    {
        return $this->json_original['cuerpoDocumento'] ?? [];
    }

    /**
     * Get emisor data from the JSON.
     *
     * @return array<string, mixed>
     */
    public function getEmisorAttribute(): array
    {
        return $this->json_original['emisor'] ?? [];
    }

    /**
     * Get receptor data from the JSON.
     *
     * @return array<string, mixed>
     */
    public function getReceptorAttribute(): array
    {
        return $this->json_original['receptor'] ?? [];
    }

    /**
     * Get resumen data from the JSON.
     *
     * @return array<string, mixed>
     */
    public function getResumenAttribute(): array
    {
        return $this->json_original['resumen'] ?? [];
    }

    /**
     * Check if DTE is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if DTE is processed.
     */
    public function isProcessed(): bool
    {
        return $this->status === 'processed';
    }

    /**
     * Check if DTE can be edited/mapped.
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, ['pending', 'reviewing', 'ready']);
    }

    /**
     * Mark as reviewing.
     */
    public function markAsReviewing(): void
    {
        $this->update(['status' => 'reviewing']);
    }

    /**
     * Mark as ready.
     */
    public function markAsReady(): void
    {
        $this->update(['status' => 'ready']);
    }

    /**
     * Mark as processed.
     */
    public function markAsProcessed(int $purchaseId): void
    {
        $this->update([
            'status' => 'processed',
            'purchase_id' => $purchaseId,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark as failed.
     */
    public function markAsFailed(string $notes): void
    {
        $this->update([
            'status' => 'failed',
            'processing_notes' => $notes,
        ]);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get status label in Spanish.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pendiente',
            'reviewing' => 'En revisión',
            'ready' => 'Listo para procesar',
            'processed' => 'Procesado',
            'failed' => 'Error',
            'cancelled' => 'Cancelado',
            default => 'Desconocido',
        };
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'amber',
            'reviewing' => 'blue',
            'ready' => 'green',
            'processed' => 'emerald',
            'failed' => 'red',
            'cancelled' => 'zinc',
            default => 'gray',
        };
    }
}
