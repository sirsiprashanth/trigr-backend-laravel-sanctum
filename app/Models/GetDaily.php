<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GetDaily extends Model
{
    use HasFactory;

    // Define the table name if it doesn't follow Laravel's naming convention
    protected $table = 'get_daily';

    // Define the fillable attributes
    protected $fillable = [
        'reference_id',
        'user_id',
        'distance_in_meters',
        'swimming_strokes',
        'steps',
        'burned_calories',
        'net_activity_calories',
        'BMR_calories',
        'max_hr_bpm',
        'min_hr_bpm',
        'avg_hr_bpm',
        'active_duration_in_sec',
        'avg_saturation_percentage',
        'avg_stress_level',
        'scores',
    ];

    // Define casts for JSON fields
    protected $casts = [
        'scores' => 'array',
    ];
}
