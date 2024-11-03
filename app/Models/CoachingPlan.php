<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CoachingPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'coach_id',
        'title',
        'description',
        'contract_terms',
        'price',
        'start_date',
        'end_date',
        'status'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'price' => 'decimal:2'
    ];

    // Relationship with User (the client)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship with Coach
    public function coach()
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    // Relationship with Goals
    public function goals()
    {
        return $this->hasMany(Goal::class);
    }
}
