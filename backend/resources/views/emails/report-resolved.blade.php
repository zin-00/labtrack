<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Resolved</title>
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
            color: #166534;
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
        }
        .success-icon .checkmark {
            font-size: 40px;
            color: #166534;
        }
        .content {
            margin: 20px 0;
        }
        .report-box {
            background-color: #f8f9fa;
            border-left: 4px solid #166534;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
        }
        .report-box .label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        .report-box .description {
            font-size: 14px;
            color: #333;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            color: #666;
            font-size: 14px;
        }
        .info-value {
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        .status-badge {
            display: inline-block;
            background-color: #d4edda;
            color: #166534;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        .footer a {
            color: #166534;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Lab<span>Track</span></div>
            <p style="color: #666; font-size: 14px; margin-top: 5px;">Laboratory Management System</p>
        </div>

        <div class="success-icon">
            <div class="circle">
                <span class="checkmark">✓</span>
            </div>
        </div>

        <h1>Report Resolved</h1>

        <div class="content">
            <p>Hello <strong>{{ $studentName }}</strong>,</p>

            <p>We're pleased to inform you that your report has been reviewed and resolved by our team.</p>

            <div class="report-box">
                <div class="label">Your Report</div>
                <div class="description">{{ $reportDescription }}</div>
            </div>

            <div style="background-color: #f8f9fa; border-radius: 8px; padding: 15px; margin: 20px 0;">
                <div class="info-row">
                    <span class="info-label">Status</span>
                    <span class="status-badge">Resolved</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Resolved On</span>
                    <span class="info-value">{{ $resolvedAt }}</span>
                </div>
            </div>

            <p>If you have any further questions or concerns regarding this report, please don't hesitate to contact the laboratory administrator.</p>

            <p>Thank you for helping us maintain and improve our laboratory facilities.</p>
        </div>

        <div class="footer">
            <p>This is an automated message from LabTrack.</p>
            <p>St. Francis Xavier College - Laboratory Management System</p>
            <p style="margin-top: 10px;">
                &copy; {{ date('Y') }} LabTrack. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
