<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoachingPlanTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'coaching_plan_id',
        'focus_area_id',
        'goal_id',
        'strategy_id',
        'action_plan_id',
    ];

    public function coachingPlan()
    {
        return $this->belongsTo(CoachingPlan::class);
    }

    public function focusArea()
    {
        return $this->belongsTo(FocusArea::class);
    }

    public function goal()
    {
        return $this->belongsTo(Goal::class);
    }

    public function strategy()
    {
        return $this->belongsTo(Strategy::class);
    }

    public function actionPlan()
    {
        return $this->belongsTo(ActionPlan::class);
    }
}
