<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UserActivityLog extends Model
{
    public $timestamps = false; // We only use created_at

    protected $fillable = [
        'user_id',
        'company_id',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'properties',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'session_id',
        'is_sensitive',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'old_values' => 'array',
            'new_values' => 'array',
            'is_sensitive' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the user who performed this action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the company this activity belongs to.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the subject model.
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to filter by action.
     */
    public function scopeWithAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by company.
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope to filter by subject.
     */
    public function scopeForSubject($query, string $subjectType, int $subjectId)
    {
        return $query->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId);
    }

    /**
     * Scope to filter sensitive activities.
     */
    public function scopeSensitive($query, bool $sensitive = true)
    {
        return $query->where('is_sensitive', $sensitive);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeBetweenDates($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get recent activities.
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Get user's display name for the activity.
     */
    public function getUserDisplayNameAttribute(): string
    {
        return $this->user ? $this->user->display_name : 'Usuario Desconocido';
    }

    /**
     * Get formatted activity description.
     */
    public function getFormattedDescriptionAttribute(): string
    {
        $description = $this->description;

        if ($this->subject) {
            $subjectName = method_exists($this->subject, 'getDisplayName')
                ? $this->subject->getDisplayName()
                : ($this->subject->name ?? "ID {$this->subject_id}");

            $description = str_replace('{subject}', $subjectName, $description);
        }

        if ($this->user) {
            $description = str_replace('{user}', $this->user->display_name, $description);
        }

        return $description;
    }

    /**
     * Get activity type in Spanish.
     */
    public function getActionInSpanishAttribute(): string
    {
        return match ($this->action) {
            'login' => 'Inicio de Sesión',
            'logout' => 'Cierre de Sesión',
            'created' => 'Creado',
            'updated' => 'Actualizado',
            'deleted' => 'Eliminado',
            'restored' => 'Restaurado',
            'role_assigned' => 'Rol Asignado',
            'role_removed' => 'Rol Removido',
            'permission_granted' => 'Permiso Otorgado',
            'permission_revoked' => 'Permiso Revocado',
            'warehouse_access_granted' => 'Acceso a Almacén Otorgado',
            'warehouse_access_revoked' => 'Acceso a Almacén Revocado',
            'password_changed' => 'Contraseña Cambiada',
            'profile_updated' => 'Perfil Actualizado',
            'failed_login' => 'Intento de Inicio de Sesión Fallido',
            'export' => 'Exportación',
            'import' => 'Importación',
            default => ucfirst($this->action),
        };
    }

    /**
     * Log a user activity.
     */
    public static function log(array $data): self
    {
        $request = request();

        return self::create([
            'user_id' => $data['user_id'] ?? auth()->id(),
            'company_id' => $data['company_id'] ?? auth()->user()?->company_id,
            'action' => $data['action'],
            'subject_type' => $data['subject_type'] ?? null,
            'subject_id' => $data['subject_id'] ?? null,
            'description' => $data['description'],
            'properties' => $data['properties'] ?? null,
            'old_values' => $data['old_values'] ?? null,
            'new_values' => $data['new_values'] ?? null,
            'ip_address' => $data['ip_address'] ?? $request?->ip(),
            'user_agent' => $data['user_agent'] ?? $request?->userAgent(),
            'session_id' => $data['session_id'] ?? session()->getId(),
            'is_sensitive' => $data['is_sensitive'] ?? false,
            'created_at' => now(),
        ]);
    }

    /**
     * Log login activity.
     */
    public static function logLogin(User $user, bool $successful = true): self
    {
        return self::log([
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'action' => $successful ? 'login' : 'failed_login',
            'description' => $successful
                ? 'Usuario inició sesión exitosamente'
                : 'Intento de inicio de sesión fallido',
            'is_sensitive' => ! $successful,
        ]);
    }

    /**
     * Log logout activity.
     */
    public static function logLogout(User $user): self
    {
        return self::log([
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'action' => 'logout',
            'description' => 'Usuario cerró sesión',
        ]);
    }

    /**
     * Log role assignment.
     */
    public static function logRoleAssignment(User $user, Role $role, bool $assigned = true): self
    {
        return self::log([
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'action' => $assigned ? 'role_assigned' : 'role_removed',
            'subject_type' => Role::class,
            'subject_id' => $role->id,
            'description' => $assigned
                ? "Rol '{$role->name}' asignado al usuario"
                : "Rol '{$role->name}' removido del usuario",
            'properties' => [
                'role_name' => $role->name,
                'role_id' => $role->id,
            ],
            'is_sensitive' => true,
        ]);
    }

    /**
     * Log warehouse access change.
     */
    public static function logWarehouseAccess(User $user, UserWarehouseAccess $access, bool $granted = true): self
    {
        return self::log([
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'action' => $granted ? 'warehouse_access_granted' : 'warehouse_access_revoked',
            'subject_type' => UserWarehouseAccess::class,
            'subject_id' => $access->id,
            'description' => $granted
                ? "Acceso otorgado al almacén '{$access->warehouse->name}'"
                : "Acceso revocado del almacén '{$access->warehouse->name}'",
            'properties' => [
                'warehouse_id' => $access->warehouse_id,
                'warehouse_name' => $access->warehouse->name,
                'access_type' => $access->access_type,
            ],
            'is_sensitive' => true,
        ]);
    }
}
