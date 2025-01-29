<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class VitalScanResults extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    public $timestamp;
    public $healthScore;
    public $healthScoreText;

    /**
     * Create a new message instance.
     */
    public function __construct($data)
    {
        Log::info('VitalScanResults: Constructing email with data', ['data' => $data]);
        
        $this->data = $data;
        $this->timestamp = now()->format('F j, Y \a\t g:i A');
        $this->healthScore = $this->calculateHealthScore();
        $this->healthScoreText = $this->getHealthScoreText();
        
        Log::info('VitalScanResults: Email data prepared', [
            'timestamp' => $this->timestamp,
            'healthScore' => $this->healthScore,
            'healthScoreText' => $this->healthScoreText
        ]);
    }

    /**
     * Build the message.
     */
    public function build()
    {
        Log::info('VitalScanResults: Starting to build email');
        
        $emailData = [
            'pulseRate' => $this->data['pulse_rate'],
            'pulseRateStatus' => $this->getMetricStatus('pulse_rate', $this->data['pulse_rate']),
            'spo2' => $this->data['spo2'],
            'spo2Status' => $this->getMetricStatus('spo2', $this->data['spo2']),
            'bloodPressure' => $this->data['blood_pressure'],
            'bloodPressureStatus' => $this->getBloodPressureStatus($this->data['blood_pressure']),
            'respirationRate' => $this->data['respiration_rate'],
            'respirationRateStatus' => $this->getMetricStatus('respiration_rate', $this->data['respiration_rate']),
            'stressLevel' => $this->data['stress_level'],
            'stressLevelStatus' => $this->getMetricStatus('stress_level', $this->data['stress_level']),
            'sdnn' => $this->data['sdnn'],
            'sdnnStatus' => $this->getMetricStatus('sdnn', $this->data['sdnn']),
            'lfhf' => $this->data['lfhf'],
            'lfhfStatus' => $this->getMetricStatus('lfhf', $this->data['lfhf']),
            'wellnessIndex' => $this->calculateWellnessIndex(),
            'wellnessIndexStatus' => $this->getMetricStatus('wellness_index', $this->calculateWellnessIndex())
        ];

        Log::info('VitalScanResults: Email data prepared for view', $emailData);

        return $this->subject('Your Vital Scan Results')
                    ->view('emails.vital-scan-results')
                    ->with($emailData);
    }

    private function calculateHealthScore()
    {
        $score = 100;
        $validMetrics = 0;

        // Pulse Rate scoring
        if (isset($this->data['pulse_rate'])) {
            $validMetrics++;
            $pr = $this->data['pulse_rate'];
            if ($pr < 60) $score -= 10 * (1 - $pr/60);
            else if ($pr > 100) $score -= 10 * (($pr-100)/20);
        }

        // Blood Oxygen scoring
        if (isset($this->data['spo2'])) {
            $validMetrics++;
            $spo2 = $this->data['spo2'];
            if ($spo2 < 95) $score -= 25 * (1 - $spo2/95);
        }

        // Blood Pressure scoring
        if (isset($this->data['blood_pressure'])) {
            $validMetrics++;
            $systolic = $this->data['blood_pressure']['systolic'];
            $diastolic = $this->data['blood_pressure']['diastolic'];
            $systolicDev = abs(120 - $systolic) / 30;
            $diastolicDev = abs(80 - $diastolic) / 20;
            $score -= 10 * ($systolicDev + $diastolicDev);
        }

        // Stress Level scoring
        if (isset($this->data['stress_level'])) {
            $validMetrics++;
            $stress = $this->data['stress_level'];
            $score -= 15 * ($stress/100);
        }

        return $validMetrics >= 3 ? max(0, min(100, round($score))) : null;
    }

    private function getHealthScoreText()
    {
        if (!$this->healthScore) return 'Insufficient data';
        if ($this->healthScore >= 90) return 'Excellent';
        if ($this->healthScore >= 70) return 'Good';
        if ($this->healthScore >= 50) return 'Fair';
        return 'Poor';
    }

    private function getMetricStatus($metric, $value)
    {
        if (!isset($value)) return 'unknown';

        $ranges = [
            'pulse_rate' => ['low' => 60, 'high' => 100, 'danger' => 120],
            'spo2' => ['low' => 95, 'high' => 100],
            'respiration_rate' => ['low' => 12, 'high' => 20, 'danger' => 24],
            'stress_level' => ['normal' => 50, 'high' => 75],
            'sdnn' => ['low' => 30, 'high' => 60, 'danger' => 80],
            'lfhf' => ['low' => 1, 'high' => 2, 'danger' => 3],
            'wellness_index' => ['low' => 70, 'high' => 100]
        ];

        if (!isset($ranges[$metric])) return 'unknown';

        $range = $ranges[$metric];
        
        if (isset($range['low']) && $value < $range['low']) return 'low';
        if (isset($range['normal']) && $value <= $range['normal']) return 'normal';
        if (isset($range['high']) && $value <= $range['high']) return 'normal';
        if (isset($range['danger']) && $value >= $range['danger']) return 'high';
        return 'high';
    }

    private function getBloodPressureStatus($bp)
    {
        if (!isset($bp['systolic']) || !isset($bp['diastolic'])) return 'unknown';
        
        $systolic = $bp['systolic'];
        $diastolic = $bp['diastolic'];

        if ($systolic < 90 || $diastolic < 60) return 'low';
        if ($systolic < 120 && $diastolic < 80) return 'normal';
        if ($systolic < 140 || $diastolic < 90) return 'high';
        return 'high';
    }

    private function calculateWellnessIndex()
    {
        $score = 0;
        
        // Pulse Rate (5 points)
        if (isset($this->data['pulse_rate'])) {
            $pr = $this->data['pulse_rate'];
            if ($pr >= 60 && $pr <= 100) $score += 5;
        }
        
        // Blood Oxygen (10 points)
        if (isset($this->data['spo2'])) {
            $spo2 = $this->data['spo2'];
            if ($spo2 >= 95 && $spo2 <= 100) $score += 10;
        }
        
        // Blood Pressure (15 points)
        if (isset($this->data['blood_pressure'])) {
            $systolic = $this->data['blood_pressure']['systolic'];
            $diastolic = $this->data['blood_pressure']['diastolic'];
            if ($systolic >= 90 && $systolic <= 120 && $diastolic >= 60 && $diastolic <= 80) $score += 15;
        }
        
        // Add more metrics as needed...
        
        return $score;
    }
} 