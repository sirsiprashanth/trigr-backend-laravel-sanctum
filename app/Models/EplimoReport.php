<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EplimoReport extends Model
{
    protected $fillable = [
        'user_id',
        'report_data',
        'pdf_path',
        'recommendations_pdf_path'
    ];

    protected $casts = [
        'report_data' => 'array'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
