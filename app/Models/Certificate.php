<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Certificate extends Model
{
    use HasFactory;

    public const TYPE_DIPLOMA    = 'diploma';
    public const TYPE_DISMISSAL  = 'honorable_dismissal';
    public const TYPE_ENROLMENT  = 'certificate_of_enrolment';
    public const TYPE_TOR        = 'transcript_of_records';

    protected $fillable = [
        'serial_number', 'verification_token', 'document_type', 'student_record_id',
        'issued_by', 'batch_id', 'payload', 'content_hash', 'file_hash',
        'file_path', 'qr_path', 'status', 'issued_on', 'supersedes_id',
    ];

    protected function casts(): array
    {
        return [
            'payload'          => 'array',
            'issued_on'        => 'date',
            'revoked_at'       => 'datetime',
            'last_verified_at' => 'datetime',
        ];
    }

    public static function types(): array
    {
        return [
            self::TYPE_DIPLOMA   => 'University Diploma',
            self::TYPE_DISMISSAL => 'Honorable Dismissal',
            self::TYPE_ENROLMENT => 'Certificate of Enrolment',
            self::TYPE_TOR       => 'Transcript of Records',
        ];
    }

    public static function typeCode(string $type): string
    {
        return match ($type) {
            self::TYPE_DIPLOMA   => 'DIP',
            self::TYPE_DISMISSAL => 'HDL',
            self::TYPE_ENROLMENT => 'COE',
            self::TYPE_TOR       => 'TOR',
            default              => 'DOC',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return self::types()[$this->document_type] ?? 'Document';
    }

    public function studentRecord(): BelongsTo
    {
        return $this->belongsTo(StudentRecord::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(CertificateBatch::class, 'batch_id');
    }

    public function verificationLogs(): HasMany
    {
        return $this->hasMany(VerificationLog::class);
    }

    public function scopeIssued(Builder $query): Builder
    {
        return $query->where('status', 'issued');
    }

    public function scopeOfType(Builder $query, ?string $type): Builder
    {
        return $type ? $query->where('document_type', $type) : $query;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('serial_number', 'ilike', "%{$term}%")
              ->orWhere('content_hash', 'ilike', "%{$term}%")
              ->orWhereHas('studentRecord', function (Builder $s) use ($term) {
                  $s->where('last_name', 'ilike', "%{$term}%")
                    ->orWhere('first_name', 'ilike', "%{$term}%")
                    ->orWhere('student_number', 'ilike', "%{$term}%");
              });
        });
    }

    public function isRevoked(): bool
    {
        return $this->status === 'revoked';
    }

    public function verificationUrl(): string
    {
        return route('verify.token', $this->verification_token);
    }

    public function shortHash(): string
    {
        return mb_strtoupper(mb_substr($this->content_hash, 0, 8)) . '…' . mb_strtoupper(mb_substr($this->content_hash, -8));
    }
}
