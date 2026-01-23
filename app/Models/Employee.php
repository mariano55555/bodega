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

class Employee extends Model
{
    /** @use HasFactory<\Database\Factories\EmployeeFactory> */
    use HasFactory, LogsActivity, Sluggable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'area_id',
        'employee_code',
        'name',
        'position',
        'phone',
        'mobile',
        'email',
        'notes',
        'slug',
        'is_active',
        'active_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'active_at' => 'datetime',
        ];
    }

    /**
     * Return the sluggable configuration array for this model.
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name',
                'includeTrashed' => true,
            ],
        ];
    }

    /**
     * Boot the model.
     */
    // protected static function boot(): void
    // {
    //     parent::boot();

    //     static::creating(function ($employee) {
    //         if (empty($employee->slug)) {
    //             $employee->slug = Str::slug($employee->name);
    //         }
    //         if (auth()->check()) {
    //             $employee->created_by = auth()->id();
    //         }
    //         if (is_null($employee->active_at) && $employee->is_active) {
    //             $employee->active_at = now();
    //         }
    //     });

    //     static::updating(function ($employee) {
    //         if ($employee->isDirty('name') && empty($employee->slug)) {
    //             $employee->slug = Str::slug($employee->name);
    //         }
    //         if (auth()->check()) {
    //             $employee->updated_by = auth()->id();
    //         }
    //         if ($employee->isDirty('is_active')) {
    //             $employee->active_at = $employee->is_active ? now() : null;
    //         }
    //     });

    //     static::deleting(function ($employee) {
    //         if (auth()->check()) {
    //             $employee->deleted_by = auth()->id();
    //             $employee->save();
    //         }
    //     });
    // }

    /**
     * Configure activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'employee_code', 'email', 'is_active', 'area_id', 'position'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Empleado '{$this->name}' {$eventName}");
    }

    /**
     * Get the company that owns this employee.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the area that this employee belongs to.
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * Get the dispatches for this employee.
     */
    public function dispatches(): HasMany
    {
        return $this->hasMany(Dispatch::class);
    }

    /**
     * Get the user who created this employee.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this employee.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted this employee.
     */
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Scope a query to only include active employees.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNotNull('active_at');
    }

    /**
     * Scope a query to filter by company.
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope a query to filter by area.
     */
    public function scopeForArea($query, int $areaId)
    {
        return $query->where('area_id', $areaId);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Generate the next employee code for a company.
     */
    public static function generateEmployeeCode(int $companyId): string
    {
        $company = Company::find($companyId);

        if (! $company) {
            return 'EMP-001';
        }

        $prefix = $company->settings['employee_code_prefix'] ?? 'EMP';
        $padding = $company->settings['employee_code_padding'] ?? 3;

        // Get the last employee code for this company
        $lastEmployee = static::where('company_id', $companyId)
            ->whereNotNull('employee_code')
            ->where('employee_code', 'like', $prefix.'-%')
            ->orderByRaw('CAST(SUBSTRING(employee_code, '.(strlen($prefix) + 2).') AS UNSIGNED) DESC')
            ->first();

        if (! $lastEmployee) {
            return $prefix.'-'.str_pad('1', $padding, '0', STR_PAD_LEFT);
        }

        // Extract the numeric part from the last code
        $lastCode = $lastEmployee->employee_code;
        $numericPart = (int) substr($lastCode, strlen($prefix) + 1);
        $nextNumber = $numericPart + 1;

        return $prefix.'-'.str_pad((string) $nextNumber, $padding, '0', STR_PAD_LEFT);
    }

    /**
     * Preview the next employee code for a company.
     */
    public static function previewEmployeeCode(int $companyId): string
    {
        return static::generateEmployeeCode($companyId);
    }
}
