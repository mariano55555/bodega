<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Audit record of a correction applied to a document that had already moved stock.
 */
class DocumentCorrection extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'correctable_type',
        'correctable_id',
        'reason',
        'before',
        'after',
        'movement_ids',
        'is_active',
        'active_at',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
            'movement_ids' => 'array',
            'is_active' => 'boolean',
            'active_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (DocumentCorrection $correction) {
            $correction->active_at ??= now();
            if (auth()->check()) {
                $correction->created_by ??= auth()->id();
            }
        });
    }

    public function correctable(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
