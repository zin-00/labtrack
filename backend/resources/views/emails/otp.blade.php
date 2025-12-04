<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset OTP</title>
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
        .otp-container {
            background-color: #f8f9fa;
            border: 2px dashed #e0e0e0;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
        }
        .otp-code {
            font-size: 36px;
            font-weight: bold;
            letter-spacing: 8px;
            color: #1a1a1a;
            font-family: 'Courier New', monospace;
        }
        .message {
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 6px;
            padding: 15px;
            font-size: 13px;
            color: #856404;
            margin-top: 20px;
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

        <h1>Password Reset Request</h1>

        <p class="message">
            Hello {{ $userName }},<br><br>
            We received a request to reset your password. Use the OTP code below to verify your identity and reset your password.
        </p>

        <div class="otp-container">
            <div class="otp-code">{{ $otp }}</div>
        </div>

        <p class="message">
            This code will expire in <strong>10 minutes</strong>. Please do not share this code with anyone.
        </p>

        <div class="warning">
            <strong>⚠️ Security Notice:</strong> If you did not request a password reset, please ignore this email. Your account is safe and no changes have been made.
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} LabTrack. All rights reserved.</p>
            <p>Computer Laboratory Management System</p>
        </div>
    </div>
</body>
</html>
