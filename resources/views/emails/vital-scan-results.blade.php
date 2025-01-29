<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Your Vital Scan Results</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #6200EE, #3700B3);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .content {
            padding: 20px;
        }
        .health-score {
            text-align: center;
            margin: 20px 0;
            padding: 20px;
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            border-radius: 8px;
        }
        .score-circle {
            width: 120px;
            height: 120px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            font-weight: bold;
            border: 8px solid rgba(255,255,255,0.2);
        }
        .metric-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border: 1px solid #eee;
        }
        .metric-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .metric-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        .metric-value {
            font-size: 24px;
            font-weight: bold;
            color: #6200EE;
        }
        .metric-unit {
            font-size: 14px;
            color: #666;
        }
        .status-indicator {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: 500;
        }
        .status-normal { background: #E8F5E9; color: #4CAF50; }
        .status-low { background: #FFF3E0; color: #FF9800; }
        .status-high { background: #FBE9E7; color: #F44336; }
        .section-title {
            font-size: 20px;
            font-weight: bold;
            margin: 30px 0 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #6200EE;
            color: #333;
        }
        .footer {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Your Vital Scan Results</h1>
            <p>{{ $timestamp }}</p>
        </div>
        
        <div class="content">
            @if(isset($healthScore))
            <div class="health-score">
                <h2>Overall Health Score</h2>
                <div class="score-circle">{{ $healthScore }}</div>
                <p>{{ $healthScoreText }}</p>
            </div>
            @endif

            <div class="section-title">Basic Vital Signs</div>
            
            <div class="metric-card">
                <div class="metric-header">
                    <span class="metric-title">Pulse Rate</span>
                    <span class="status-indicator status-{{ $pulseRateStatus }}">{{ ucfirst($pulseRateStatus) }}</span>
                </div>
                <div class="metric-value">
                    {{ $pulseRate }} <span class="metric-unit">bpm</span>
                </div>
                <p>Healthy Range: 60-100 bpm</p>
            </div>

            <div class="metric-card">
                <div class="metric-header">
                    <span class="metric-title">Blood Oxygen</span>
                    <span class="status-indicator status-{{ $spo2Status }}">{{ ucfirst($spo2Status) }}</span>
                </div>
                <div class="metric-value">
                    {{ $spo2 }} <span class="metric-unit">%</span>
                </div>
                <p>Healthy Range: 95-100%</p>
            </div>

            <div class="metric-card">
                <div class="metric-header">
                    <span class="metric-title">Blood Pressure</span>
                    <span class="status-indicator status-{{ $bloodPressureStatus }}">{{ ucfirst($bloodPressureStatus) }}</span>
                </div>
                <div class="metric-value">
                    {{ $bloodPressure['systolic'] }}/{{ $bloodPressure['diastolic'] }} <span class="metric-unit">mmHg</span>
                </div>
                <p>Healthy Range: 120/80 mmHg</p>
            </div>

            <div class="section-title">Heart Rate Variability</div>
            
            <div class="metric-card">
                <div class="metric-header">
                    <span class="metric-title">Stress Level</span>
                    <span class="status-indicator status-{{ $stressLevelStatus }}">{{ ucfirst($stressLevelStatus) }}</span>
                </div>
                <div class="metric-value">
                    {{ $stressLevel }} <span class="metric-unit">%</span>
                </div>
                <p>Healthy Range: Below 50%</p>
            </div>

            <div class="metric-card">
                <div class="metric-header">
                    <span class="metric-title">SDNN</span>
                    <span class="status-indicator status-{{ $sdnnStatus }}">{{ ucfirst($sdnnStatus) }}</span>
                </div>
                <div class="metric-value">
                    {{ $sdnn }} <span class="metric-unit">ms</span>
                </div>
                <p>Healthy Range: 30-60 ms</p>
            </div>

            <div class="section-title">Autonomic Balance</div>

            <div class="metric-card">
                <div class="metric-header">
                    <span class="metric-title">LF/HF Ratio</span>
                    <span class="status-indicator status-{{ $lfhfStatus }}">{{ ucfirst($lfhfStatus) }}</span>
                </div>
                <div class="metric-value">
                    {{ $lfhf }}
                </div>
                <p>Healthy Range: 1.0-2.0</p>
            </div>

            <div class="metric-card">
                <div class="metric-header">
                    <span class="metric-title">Wellness Index</span>
                    <span class="status-indicator status-{{ $wellnessIndexStatus }}">{{ ucfirst($wellnessIndexStatus) }}</span>
                </div>
                <div class="metric-value">
                    {{ $wellnessIndex }}
                </div>
                <p>Healthy Range: 70-100</p>
            </div>
        </div>

        <div class="footer">
            <p>This report was generated on {{ $timestamp }}</p>
            <p>For more detailed analysis and historical data, please visit our app.</p>
        </div>
    </div>
</body>
</html> 