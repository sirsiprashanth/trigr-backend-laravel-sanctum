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
        'focus_area_id',
        'goal_id',
        'strategy_id',
        'action_plan_id',
    ];

    public function coach()
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function focusArea()
    {
        return $this->belongsTo(FocusArea::class, 'focus_area_id');
    }

    public function goal()
    {
        return $this->belongsTo(Goal::class, 'goal_id');
    }

    public function strategy()
    {
        return $this->belongsTo(Strategy::class, 'strategy_id');
    }

    public function actionPlan()
    {
        return $this->belongsTo(ActionPlan::class, 'action_plan_id');
    }
}
