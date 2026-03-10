<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DispatchFuelDetail extends Model
{
    /** @use HasFactory<\Database\Factories\DispatchFuelDetailFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'dispatch_id',
        'vehicle_class',
        'vehicle_brand',
        'vehicle_model',
        'vehicle_plate',
        'odometer_reading',
        'horometer_reading',
        'place_to_visit',
        'mission_description',
        'kilometers_to_travel',
        'is_active',
        'active_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'odometer_reading' => 'decimal:2',
            'horometer_reading' => 'decimal:2',
            'kilometers_to_travel' => 'decimal:2',
            'is_active' => 'boolean',
            'active_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($detail) {
            if (auth()->check()) {
                $detail->created_by = auth()->id();
            }
            if (is_null($detail->active_at) && $detail->is_active) {
                $detail->active_at = now();
            }
        });

        static::updating(function ($detail) {
            if (auth()->check()) {
                $detail->updated_by = auth()->id();
            }
        });

        static::deleting(function ($detail) {
            if (auth()->check()) {
                $detail->deleted_by = auth()->id();
                $detail->save();
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['vehicle_class', 'vehicle_brand', 'vehicle_plate', 'odometer_reading', 'horometer_reading'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function dispatch(): BelongsTo
    {
        return $this->belongsTo(Dispatch::class);
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
}
