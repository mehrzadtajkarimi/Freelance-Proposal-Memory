<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ConversationMessage extends Model
{
    protected $fillable = [
        'project_request_id',
        'direction',
        'body',
        'occurred_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function projectRequest(): BelongsTo
    {
        return $this->belongsTo(ProjectRequest::class);
    }
}
