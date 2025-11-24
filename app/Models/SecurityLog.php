<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SecurityLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'company_id',
        'event_type',
        'severity',
        'description',
        'metadata',
        'ip_address',
        'user_agent',
        'method',
        'url',
        'status_code',
        'country',
        'city',
        'affected_model_type',
        'affected_model_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user that owns the security log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the company that owns the security log.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the affected model.
     */
    public function affectedModel(): MorphTo
    {
        return $this->morphTo('affected_model');
    }

    /**
     * Scope for a specific company.
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for a specific event type.
     */
    public function scopeByEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope for a specific severity.
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope for failed login attempts.
     */
    public function scopeFailedLogins($query)
    {
        return $query->where('event_type', 'failed_login');
    }

    /**
     * Scope for permission denied events.
     */
    public function scopePermissionDenied($query)
    {
        return $query->where('event_type', 'permission_denied');
    }

    /**
     * Scope for critical events.
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    /**
     * Create a security log entry.
     */
    public static function logEvent(
        string $eventType,
        string $description,
        string $severity = 'info',
        ?int $userId = null,
        ?int $companyId = null,
        ?array $metadata = null,
        ?Model $affectedModel = null
    ): self {
        $request = request();

        return static::create([
            'user_id' => $userId ?? auth()->id(),
            'company_id' => $companyId ?? auth()->user()?->company_id,
            'event_type' => $eventType,
            'severity' => $severity,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'method' => $request?->method(),
            'url' => $request?->fullUrl(),
            'status_code' => null,
            'affected_model_type' => $affectedModel ? get_class($affectedModel) : null,
            'affected_model_id' => $affectedModel?->id,
        ]);
    }

    /**
     * Log a login event.
     */
    public static function logLogin(User $user): self
    {
        return static::logEvent(
            'login',
            "Usuario {$user->name} inició sesión",
            'info',
            $user->id,
            $user->company_id
        );
    }

    /**
     * Log a failed login attempt.
     */
    public static function logFailedLogin(string $email, ?string $reason = null): self
    {
        return static::logEvent(
            'failed_login',
            "Intento de inicio de sesión fallido para: {$email}".($reason ? " - {$reason}" : ''),
            'warning',
            null,
            null,
            ['email' => $email, 'reason' => $reason]
        );
    }

    /**
     * Log a logout event.
     */
    public static function logLogout(User $user): self
    {
        return static::logEvent(
            'logout',
            "Usuario {$user->name} cerró sesión",
            'info',
            $user->id,
            $user->company_id
        );
    }

    /**
     * Log a permission denied event.
     */
    public static function logPermissionDenied(User $user, string $resource, ?string $action = null): self
    {
        return static::logEvent(
            'permission_denied',
            "Acceso denegado a {$resource}".($action ? " ({$action})" : ''),
            'warning',
            $user->id,
            $user->company_id,
            ['resource' => $resource, 'action' => $action]
        );
    }

    /**
     * Log a password change event.
     */
    public static function logPasswordChange(User $user): self
    {
        return static::logEvent(
            'password_change',
            "Usuario {$user->name} cambió su contraseña",
            'info',
            $user->id,
            $user->company_id
        );
    }
}
