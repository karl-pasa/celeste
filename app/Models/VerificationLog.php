<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationLog extends Model
{
    protected $fillable = [
        'certificate_id', 'submitted_reference', 'method', 'result',
        'document_type', 'ip_address', 'country', 'city',
        'user_agent', 'referrer', 'user_id',
    ];

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }

    public static function methods(): array
    {
        return [
            'qr_scan' => 'QR scan',
            'serial'  => 'Serial number',
            'hash'    => 'Hash lookup',
            'link'    => 'Direct link',
        ];
    }

    public function resultBadge(): string
    {
        return match ($this->result) {
            'authentic' => 'badge-authentic',
            'revoked'   => 'badge-revoked',
            'tampered'  => 'badge-tampered',
            default     => 'badge-missing',
        };
    }
}
