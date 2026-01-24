<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Area extends Model
{
    use HasFactory, LogsActivity, Sluggable, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'slug',
        'description',
        'is_active',
        'active_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'active_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'code', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name',
                'includeTrashed' => true,
            ],
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($area) {
            // Convert empty code to null to avoid unique constraint issues
            if (empty($area->code)) {
                $area->code = null;
            }

            if (auth()->check()) {
                $area->created_by = auth()->id();
            }

            if ($area->is_active && is_null($area->active_at)) {
                $area->active_at = now();
            }
        });

        static::updating(function ($area) {
            // Convert empty code to null to avoid unique constraint issues
            if (empty($area->code)) {
                $area->code = null;
            }

            if (auth()->check()) {
                $area->updated_by = auth()->id();
            }

            if ($area->isDirty('is_active')) {
                $area->active_at = $area->is_active ? now() : null;
            }
        });

        static::deleting(function ($area) {
            if (auth()->check()) {
                $area->deleted_by = auth()->id();
                $area->save();
            }
        });
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
