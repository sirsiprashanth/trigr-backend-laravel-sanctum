<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Strategy extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'goal_id',
        'title',
        'description',
        'sequence'
    ];

    protected $casts = [
        'sequence' => 'integer'
    ];

    // Relationship with Goal
    public function goal()
    {
        return $this->belongsTo(Goal::class);
    }

    // Get the coaching plan through goal
    public function coachingPlan()
    {
        return $this->hasOneThrough(
            CoachingPlan::class,
            Goal::class,
            'id', // Foreign key on goals table
            'id', // Foreign key on coaching_plans table
            'goal_id', // Local key on strategies table
            'coaching_plan_id' // Local key on goals table
        );
    }

    // Scope for ordering by sequence
    public function scopeOrdered($query)
    {
        return $query->orderBy('sequence');
    }
}
