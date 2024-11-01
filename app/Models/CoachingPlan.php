<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoachingPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'contract_terms',
        'price',
        'status',
        'coach_id',
        'user_id',
    ];

    public function coach()
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function targets()
    {
        return $this->hasMany(CoachingPlanTarget::class);
    }
}
