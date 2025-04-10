<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Welcome to Trigr</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2d3748;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 0.9em;
            color: #718096;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome to Trigr!</h1>
        
        <p>Hello {{ $user->name }},</p>
        
        <p>Thank you for creating an account with us. We're excited to have you join our community!</p>
        
        <p>With your new account, you can:</p>
        <ul>
            <li>Track your health and wellness</li>
            <li>Connect with coaches</li>
            <li>Set and monitor your personal goals</li>
            <li>Access personalized action plans</li>
        </ul>
        
        <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>
        
        <p>Best regards,<br>The Trigr Team</p>
        
        <div class="footer">
            <p>© {{ date('Y') }} Trigr. All rights reserved.</p>
        </div>
    </div>
</body>
</html>