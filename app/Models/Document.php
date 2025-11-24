<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'slug',
        'company_id',
        'uploaded_by',
        'documentable_type',
        'documentable_id',
        'document_type',
        'title',
        'description',
        'document_number',
        'file_name',
        'file_path',
        'file_type',
        'mime_type',
        'file_size',
        'disk',
        'document_date',
        'document_amount',
        'issuer',
        'recipient',
        'metadata',
        'status',
        'version',
        'previous_version_id',
        'is_public',
        'requires_approval',
        'approved_by',
        'approved_at',
        'active_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'document_date' => 'date',
        'document_amount' => 'decimal:2',
        'file_size' => 'integer',
        'version' => 'integer',
        'is_public' => 'boolean',
        'requires_approval' => 'boolean',
        'approved_at' => 'datetime',
        'active_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($document) {
            if (empty($document->slug)) {
                $document->slug = Str::slug($document->title).'-'.Str::random(8);
            }
            if (empty($document->active_at)) {
                $document->active_at = now();
            }
        });

        static::deleting(function ($document) {
            // Delete file from storage when document is deleted
            if ($document->file_path && Storage::disk($document->disk)->exists($document->file_path)) {
                Storage::disk($document->disk)->delete($document->file_path);
            }
        });
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function previousVersion(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'previous_version_id');
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

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('document_type', $type);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_at');
    }

    public function scopePendingApproval($query)
    {
        return $query->where('requires_approval', true)
            ->whereNull('approved_at');
    }

    // Helper Methods
    public function getFileSizeForHumansAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    public function getFileUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->file_path);
    }

    public function canBeDownloaded(): bool
    {
        return $this->status === 'active' && Storage::disk($this->disk)->exists($this->file_path);
    }

    public function canBeDeleted(User $user): bool
    {
        return $user->id === $this->uploaded_by || $user->hasRole('super-admin');
    }

    public function approve(User $user): void
    {
        $this->update([
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);
    }

    public function isApproved(): bool
    {
        return ! is_null($this->approved_at);
    }

    public function isPdf(): bool
    {
        return $this->file_type === 'pdf';
    }

    public function isImage(): bool
    {
        return in_array($this->file_type, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    }

    public function isOfficeDocument(): bool
    {
        return in_array($this->file_type, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']);
    }

    public function getIconClassAttribute(): string
    {
        return match ($this->file_type) {
            'pdf' => 'document-text',
            'jpg', 'jpeg', 'png', 'gif', 'webp' => 'photo',
            'xls', 'xlsx' => 'table-cells',
            'doc', 'docx' => 'document',
            default => 'document',
        };
    }

    public function getDocumentTypeSpanishAttribute(): string
    {
        return match ($this->document_type) {
            'invoice' => 'Factura',
            'receipt' => 'Recibo',
            'ccf' => 'CCF',
            'delivery_note' => 'Nota de Entrega',
            'photo' => 'Fotografía',
            'contract' => 'Contrato',
            'certificate' => 'Certificado',
            'report' => 'Reporte',
            default => 'Otro',
        };
    }
}
