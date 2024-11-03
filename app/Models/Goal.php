<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Goal extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'coaching_plan_id',
        'focus_area_id',
        'title',
        'description',
        'sequence'
    ];

    protected $casts = [
        'sequence' => 'integer'
    ];

    // Relationship with Coaching Plan
    public function coachingPlan()
    {
        return $this->belongsTo(CoachingPlan::class);
    }

    // Relationship with Focus Area
    public function focusArea()
    {
        return $this->belongsTo(FocusArea::class);
    }

    // Relationship with Strategies
    public function strategies()
    {
        return $this->hasMany(Strategy::class);
    }

    // Relationship with Action Plans
    public function actionPlans()
    {
        return $this->hasMany(ActionPlan::class);
    }

    // Scope for ordering by sequence
    public function scopeOrdered($query)
    {
        return $query->orderBy('sequence');
    }
}
