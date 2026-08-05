<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CertificateBatch extends Model
{
    protected $fillable = [
        'reference', 'label', 'document_type', 'total',
        'generated', 'failed', 'status', 'errors', 'created_by', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'errors'       => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'batch_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function progress(): int
    {
        return $this->total > 0 ? (int) round(($this->generated / $this->total) * 100) : 0;
    }
}
