<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Approved</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #1a1a1a;
        }
        .logo span {
            font-weight: 300;
            color: #666;
        }
        h1 {
            color: #1a1a1a;
            font-size: 24px;
            margin-bottom: 20px;
            text-align: center;
        }
        .success-icon {
            text-align: center;
            margin: 30px 0;
        }
        .success-icon .circle {
            display: inline-block;
            width: 80px;
            height: 80px;
            background-color: #d4edda;
            border-radius: 50%;
            line-height: 80px;
            font-size: 40px;
        }
        .message {
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .info-box {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .info-box p {
            margin: 8px 0;
            font-size: 14px;
        }
        .info-box strong {
            color: #1a1a1a;
        }
        .cta-button {
            display: block;
            width: 200px;
            margin: 30px auto;
            padding: 15px 30px;
            background-color: #28a745;
            color: #ffffff;
            text-decoration: none;
            text-align: center;
            border-radius: 6px;
            font-weight: bold;
        }
        .tips {
            background-color: #e7f3ff;
            border: 1px solid #b3d7ff;
            border-radius: 6px;
            padding: 15px;
            font-size: 13px;
            color: #004085;
            margin-top: 20px;
        }
        .tips h4 {
            margin: 0 0 10px 0;
            font-size: 14px;
        }
        .tips ul {
            margin: 0;
            padding-left: 20px;
        }
        .tips li {
            margin: 5px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            color: #999;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Lab<span>Track</span></div>
        </div>

        <div class="success-icon">
            <div class="circle">✓</div>
        </div>

        <h1>Account Approved!</h1>

        <p class="message">
            Hello {{ $userName }},<br><br>
            Great news! Your account request has been approved. You can now log in to LabTrack and start using all the features available for your role.
        </p>

        <div class="info-box">
            <p><strong>Email:</strong> {{ $userEmail }}</p>
            <p><strong>Role:</strong> {{ ucfirst($userRole) }}</p>
        </div>

        <a href="{{ config('app.frontend_url', 'http://localhost:5173') }}/login" class="cta-button">Login Now</a>

        <div class="tips">
            <h4>Getting Started:</h4>
            <ul>
                <li>Use your registered email and password to log in</li>
                <li>Complete your profile information</li>
                <li>Explore the dashboard and available features</li>
                <li>Contact support if you need any assistance</li>
            </ul>
        </div>

        <div class="footer">
            <p>This is an automated message from LabTrack.</p>
            <p>© {{ date('Y') }} LabTrack. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
