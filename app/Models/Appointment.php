<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $table = 'appointments';

    protected $fillable = [
        'topic',
        'description',
        'meeting_link',
        'preferred_date',
        'preferred_time',
        'created_by_user_id',
        'created_for_user_id',
        'status',
        'coaching_plan_id',
    ];

    // Define relationships if needed
    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function createdForUser()
    {
        return $this->belongsTo(User::class, 'created_for_user_id');
    }

    public function coachingPlan()
    {
        return $this->belongsTo(CoachingPlan::class);
    }
}
