<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DubaiEventScan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'headers',
        'ip_address',
        'additional_data',
        'pulse_rate',
        'mean_rri',
        'spo2',
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
        'wellness_level'
    ];

    protected $casts = [
        'headers' => 'array',
        'additional_data' => 'array',
        'blood_pressure' => 'array'
    ];
}
