<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Note extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'coaching_plan_id',
        'user_id',  // who created the note
        'content',
        'private', // if true, only visible to coach
    ];

    protected $casts = [
        'private' => 'boolean',
    ];

    // Relationship with CoachingPlan
    public function coachingPlan()
    {
        return $this->belongsTo(CoachingPlan::class);
    }

    // Relationship with User (who created the note)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
