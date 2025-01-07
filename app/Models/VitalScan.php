<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VitalScan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pulse_rate',
        'mean_rri',
        'oxygen_saturation',
        'respiration_rate',
        'stress_level',
        'sdnn',
        'rmssd',
        'stress_index',
        'blood_pressure',
        'lfhf',
        'pns_index',
        'pns_zone',
        'prq',
        'sd1',
        'sd2',
        'sns_index',
        'sns_zone',
        'wellness_index',
        'wellness_level',
    ];
}
