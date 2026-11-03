<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PaymentStageRule extends Model
{
    protected $fillable = [
        'name_fa',
        'name_en',
        'minimum_amount_toman',
        'maximum_amount_toman',
        'stages',
    ];

    protected function casts(): array
    {
        return [
            'minimum_amount_toman' => 'integer',
            'maximum_amount_toman' => 'integer',
            'stages' => 'array',
        ];
    }
}
