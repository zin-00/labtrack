<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Request Update</title>
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
        .status-icon {
            text-align: center;
            margin: 30px 0;
        }
        .status-icon .circle {
            display: inline-block;
            width: 80px;
            height: 80px;
            background-color: #f8d7da;
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
        .reason-box {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 6px;
            padding: 15px;
            font-size: 13px;
            color: #856404;
            margin: 20px 0;
        }
        .reason-box h4 {
            margin: 0 0 10px 0;
            font-size: 14px;
        }
        .help-section {
            background-color: #e7f3ff;
            border: 1px solid #b3d7ff;
            border-radius: 6px;
            padding: 15px;
            font-size: 13px;
            color: #004085;
            margin-top: 20px;
        }
        .help-section h4 {
            margin: 0 0 10px 0;
            font-size: 14px;
        }
        .help-section ul {
            margin: 0;
            padding-left: 20px;
        }
        .help-section li {
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

        <div class="status-icon">
            <div class="circle">✕</div>
        </div>

        <h1>Account Request Not Approved</h1>

        <p class="message">
            Hello {{ $userName }},<br><br>
            We regret to inform you that your account request for LabTrack has not been approved at this time.
        </p>

        <div class="info-box">
            <p><strong>Email:</strong> {{ $userEmail }}</p>
        </div>

        @if($reason)
        <div class="reason-box">
            <h4>Reason:</h4>
            <p>{{ $reason }}</p>
        </div>
        @endif

        <div class="help-section">
            <h4>What you can do:</h4>
            <ul>
                <li>Review the information you provided in your request</li>
                <li>Ensure all details are accurate and complete</li>
                <li>Contact your administrator for more information</li>
                <li>Submit a new request if you believe this was an error</li>
            </ul>
        </div>

        <p class="message" style="margin-top: 20px;">
            If you have any questions or believe this decision was made in error, please contact your system administrator for assistance.
        </p>

        <div class="footer">
            <p>This is an automated message from LabTrack.</p>
            <p>© {{ date('Y') }} LabTrack. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
