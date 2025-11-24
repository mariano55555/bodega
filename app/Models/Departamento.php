<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Departamento extends Model
{
    use SoftDeletes;

    protected $fillable = [
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

    public function ciudades(): HasMany
    {
        return $this->hasMany(Ciudad::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
