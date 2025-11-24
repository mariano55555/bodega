<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use Sluggable;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'group',
        'metadata',
        'guard_name',
        'is_active',
        'active_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'is_active' => 'boolean',
            'active_at' => 'datetime',
        ];
    }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name',
            ],
        ];
    }

    /**
     * Get the user who created this permission.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this permission.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted this permission.
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Scope to filter active permissions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by group.
     */
    public function scopeInGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Scope to filter permissions created by a user.
     */
    public function scopeCreatedBy($query, int $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Get all available permission groups.
     */
    public static function getAvailableGroups(): array
    {
        return self::distinct('group')->pluck('group')->sort()->values()->toArray();
    }

    /**
     * Check if permission is active.
     */
    public function isActive(): bool
    {
        return $this->is_active && ($this->active_at === null || $this->active_at <= now());
    }

    /**
     * Activate this permission.
     */
    public function activate(): void
    {
        $this->update([
            'is_active' => true,
            'active_at' => now(),
            'updated_by' => auth()->id(),
        ]);
    }

    /**
     * Deactivate this permission.
     */
    public function deactivate(): void
    {
        $this->update([
            'is_active' => false,
            'updated_by' => auth()->id(),
        ]);
    }
}
