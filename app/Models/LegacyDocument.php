<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class LegacyDocument extends Model
{
    protected $fillable = [
        'source_path',
        'document_type',
        'title',
        'body',
        'sha256',
        'imported_at',
    ];

    protected function casts(): array
    {
        return ['imported_at' => 'datetime'];
    }

    public function projectRequest(): HasOne
    {
        return $this->hasOne(ProjectRequest::class);
    }
}
