<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoachingRequest extends Model
{
    use HasFactory;

    protected $table = 'coaching_requests';

    protected $fillable = [
        'topic',
        'preferred_date',
        'preferred_time',
        'user_id',
        'coach_id',
        'status',
    ];

    // Define relationships if needed
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function coach()
    {
        return $this->belongsTo(User::class, 'coach_id');
    }
}
