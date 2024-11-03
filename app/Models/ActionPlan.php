<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActionPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'goal_id',
        'title',
        'description',
        'due_date',
        'status',
        'sequence'
    ];

    protected $casts = [
        'due_date' => 'date',
        'sequence' => 'integer'
    ];

    // Define possible statuses
    const STATUS_NOT_STARTED = 'not_started';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';

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
            'goal_id', // Local key on action_plans table
            'coaching_plan_id' // Local key on goals table
        );
    }

    // Scope for ordering by sequence
    public function scopeOrdered($query)
    {
        return $query->orderBy('sequence');
    }

    // Scopes for different statuses
    public function scopeNotStarted($query)
    {
        return $query->where('status', self::STATUS_NOT_STARTED);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    // Scope for overdue items
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->where('status', '!=', self::STATUS_COMPLETED);
    }
}
