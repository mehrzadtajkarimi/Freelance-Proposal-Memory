<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RequestEmphasis extends Model
{
    protected $fillable = [
        'project_request_id',
        'position',
        'title',
        'source_excerpt',
        'interpretation',
    ];

    public function projectRequest(): BelongsTo
    {
        return $this->belongsTo(ProjectRequest::class);
    }
}
