<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class MovementReason extends Model
{
    /** @use HasFactory<\Database\Factories\MovementReasonFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'legacy_code',
        'legacy_name',
        'name',
        'slug',
        'description',
        'category',
        'movement_type',
        'requires_approval',
        'requires_documentation',
        'affects_cost',
        'approval_threshold',
        'required_fields',
        'validation_rules',
        'sort_order',
        'notes',
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
            'requires_approval' => 'boolean',
            'requires_documentation' => 'boolean',
            'affects_cost' => 'boolean',
            'approval_threshold' => 'decimal:2',
            'required_fields' => 'array',
            'validation_rules' => 'array',
            'sort_order' => 'integer',
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

        static::creating(function ($reason) {
            if (empty($reason->slug)) {
                $reason->slug = Str::slug($reason->name);
            }
            if (auth()->check()) {
                $reason->created_by = auth()->id();
            }
            if (is_null($reason->active_at) && $reason->is_active) {
                $reason->active_at = now();
            }
        });

        static::updating(function ($reason) {
            if ($reason->isDirty('name') && empty($reason->slug)) {
                $reason->slug = Str::slug($reason->name);
            }
            if (auth()->check()) {
                $reason->updated_by = auth()->id();
            }
            if ($reason->isDirty('is_active')) {
                $reason->active_at = $reason->is_active ? now() : null;
            }
        });

        static::deleting(function ($reason) {
            if (auth()->check()) {
                $reason->deleted_by = auth()->id();
                $reason->save();
            }
        });
    }

    /**
     * Get the user who created this reason.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this reason.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted this reason.
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Get the inventory movements using this reason.
     */
    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'movement_reason_id');
    }

    /**
     * Scope a query to only include active reasons.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereNotNull('active_at');
    }

    /**
     * Scope a query by category.
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query by movement type.
     */
    public function scopeByMovementType(Builder $query, string $movementType): Builder
    {
        return $query->where('movement_type', $movementType);
    }

    /**
     * Scope a query for reasons that require approval.
     */
    public function scopeRequiresApproval(Builder $query): Builder
    {
        return $query->where('requires_approval', true);
    }

    /**
     * Scope a query for reasons that require documentation.
     */
    public function scopeRequiresDocumentation(Builder $query): Builder
    {
        return $query->where('requires_documentation', true);
    }

    /**
     * Scope a query ordered by sort order.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
    }

    /**
     * Scope a query for inbound movements.
     */
    public function scopeInbound(Builder $query): Builder
    {
        return $query->where('category', 'inbound');
    }

    /**
     * Scope a query for outbound movements.
     */
    public function scopeOutbound(Builder $query): Builder
    {
        return $query->where('category', 'outbound');
    }

    /**
     * Scope a query for transfer movements.
     */
    public function scopeTransfer(Builder $query): Builder
    {
        return $query->where('category', 'transfer');
    }

    /**
     * Scope a query for adjustment movements.
     */
    public function scopeAdjustment(Builder $query): Builder
    {
        return $query->where('category', 'adjustment');
    }

    /**
     * Scope a query for disposal movements.
     */
    public function scopeDisposal(Builder $query): Builder
    {
        return $query->where('category', 'disposal');
    }

    /**
     * Check if this reason requires approval for the given value.
     */
    public function requiresApprovalForValue(float $value): bool
    {
        if (! $this->requires_approval) {
            return false;
        }

        if ($this->approval_threshold === null) {
            return true;
        }

        return $value >= $this->approval_threshold;
    }

    /**
     * Get the validation rules for this movement reason.
     */
    public function getValidationRules(): array
    {
        return $this->validation_rules ?? [];
    }

    /**
     * Get the required fields for this movement reason.
     */
    public function getRequiredFields(): array
    {
        return $this->required_fields ?? [];
    }

    /**
     * Check if a specific field is required.
     */
    public function isFieldRequired(string $field): bool
    {
        return in_array($field, $this->getRequiredFields());
    }

    /**
     * Get the display name with category.
     */
    public function getDisplayNameAttribute(): string
    {
        $categoryLabels = [
            'inbound' => 'Entrada',
            'outbound' => 'Salida',
            'transfer' => 'Transferencia',
            'adjustment' => 'Ajuste',
            'disposal' => 'Disposición',
        ];

        $categoryLabel = $categoryLabels[$this->category] ?? $this->category;

        return "{$this->name} ({$categoryLabel})";
    }

    /**
     * Get the movement type in Spanish.
     */
    public function getMovementTypeSpanishAttribute(): string
    {
        $types = [
            'in' => 'Entrada',
            'out' => 'Salida',
            'transfer' => 'Transferencia',
        ];

        return $types[$this->movement_type] ?? $this->movement_type;
    }

    /**
     * Get the category in Spanish.
     */
    public function getCategorySpanishAttribute(): string
    {
        $categories = [
            'inbound' => 'Entrada',
            'outbound' => 'Salida',
            'transfer' => 'Transferencia',
            'adjustment' => 'Ajuste',
            'disposal' => 'Disposición',
        ];

        return $categories[$this->category] ?? $this->category;
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
