<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ciudad extends Model
{
    use SoftDeletes;

    protected $table = 'ciudades';

    protected $fillable = [
        'departamento_id',
        'code',
        'name',
        'slug',
        'is_active',
        'active_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'active_at' => 'datetime',
        ];
    }

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
