<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryAlert extends Model
{
    /** @use HasFactory<\Database\Factories\InventoryAlertFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'warehouse_id',
        'alert_type',
        'threshold_value',
        'current_value',
        'priority',
        'message',
        'metadata',
        'is_active',
        'active_at',
        'is_acknowledged',
        'acknowledged_at',
        'acknowledged_by',
        'acknowledgment_notes',
        'is_resolved',
        'resolved_at',
        'resolved_by',
        'resolution_notes',
        'auto_resolve',
        'expires_at',
        'email_sent',
        'email_sent_at',
        'sms_sent',
        'sms_sent_at',
        'notification_log',
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
            'threshold_value' => 'decimal:4',
            'current_value' => 'decimal:4',
            'metadata' => 'array',
            'notification_log' => 'array',
            'is_active' => 'boolean',
            'active_at' => 'datetime',
            'is_acknowledged' => 'boolean',
            'acknowledged_at' => 'datetime',
            'is_resolved' => 'boolean',
            'resolved_at' => 'datetime',
            'auto_resolve' => 'boolean',
            'expires_at' => 'datetime',
            'email_sent' => 'boolean',
            'email_sent_at' => 'datetime',
            'sms_sent' => 'boolean',
            'sms_sent_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($alert) {
            if (auth()->check()) {
                $alert->created_by = auth()->id();
            }
            if (is_null($alert->active_at) && $alert->is_active) {
                $alert->active_at = now();
            }
        });

        static::updating(function ($alert) {
            if (auth()->check()) {
                $alert->updated_by = auth()->id();
            }
            if ($alert->isDirty('is_active')) {
                $alert->active_at = $alert->is_active ? now() : null;
            }
        });

        static::deleting(function ($alert) {
            if (auth()->check()) {
                $alert->deleted_by = auth()->id();
                $alert->save();
            }
        });
    }

    /**
     * Get the product associated with this alert.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the warehouse associated with this alert.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the user who created this alert.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this alert.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted this alert.
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Get the user who acknowledged this alert.
     */
    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    /**
     * Get the user who resolved this alert.
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Scope a query to only include active alerts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNotNull('active_at');
    }

    /**
     * Scope a query to only include unresolved alerts.
     */
    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    /**
     * Scope a query to only include unacknowledged alerts.
     */
    public function scopeUnacknowledged($query)
    {
        return $query->where('is_acknowledged', false);
    }

    /**
     * Scope a query by priority.
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope a query by alert type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('alert_type', $type);
    }

    /**
     * Get the priority in Spanish.
     */
    public function getPrioritySpanishAttribute(): string
    {
        $priorities = [
            'low' => 'Baja',
            'medium' => 'Media',
            'high' => 'Alta',
            'critical' => 'Crítica',
        ];

        return $priorities[$this->priority] ?? $this->priority;
    }

    /**
     * Get the alert type in Spanish.
     */
    public function getAlertTypeSpanishAttribute(): string
    {
        $types = [
            'low_stock' => 'Stock Bajo',
            'out_of_stock' => 'Sin Stock',
            'expiring_soon' => 'Próximo a Vencer',
            'expired' => 'Vencido',
            'overstocked' => 'Sobrestock',
            'quality_issue' => 'Problema de Calidad',
            'temperature_alert' => 'Alerta de Temperatura',
            'movement_required' => 'Movimiento Requerido',
        ];

        return $types[$this->alert_type] ?? $this->alert_type;
    }

    /**
     * Check if the alert is overdue for acknowledgment.
     */
    public function isOverdueForAcknowledgment(): bool
    {
        if ($this->is_acknowledged) {
            return false;
        }

        // Consider alerts older than 2 hours as overdue
        return $this->created_at->diffInHours() > 2;
    }

    /**
     * Check if the alert has expired.
     */
    public function hasExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Acknowledge the alert.
     */
    public function acknowledge(int $userId, ?string $notes = null): bool
    {
        if ($this->is_acknowledged) {
            return false;
        }

        return $this->update([
            'is_acknowledged' => true,
            'acknowledged_by' => $userId,
            'acknowledged_at' => now(),
            'acknowledgment_notes' => $notes,
        ]);
    }

    /**
     * Resolve the alert.
     */
    public function resolve(int $userId, ?string $notes = null): bool
    {
        if ($this->is_resolved) {
            return false;
        }

        return $this->update([
            'is_resolved' => true,
            'resolved_by' => $userId,
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);
    }
}
