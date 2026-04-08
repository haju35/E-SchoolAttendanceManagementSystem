<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Account Credentials</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background: #f9f9f9;
            border-radius: 10px;
            padding: 30px;
            border: 1px solid #e0e0e0;
        }
        .header {
            background: #4F46E5;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 10px 10px 0 0;
            margin: -30px -30px 20px -30px;
        }
        .credentials {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #4F46E5;
        }
        .credential-item {
            margin: 10px 0;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 5px;
        }
        .label {
            font-weight: bold;
            color: #4F46E5;
            display: inline-block;
            width: 80px;
        }
        .value {
            color: #333;
            font-family: monospace;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .button {
            display: inline-block;
            background: #46e5d2;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Welcome to Student Portal</h2>
        </div>

        <p>Dear <strong>{{ $user->name }}</strong>,</p>

        
        <p>Your student account has been created successfully. Below are your login credentials:</p>

        <div class="credentials">
            <div class="credential-item">
                <span class="label">Email:</span>
                <span class="value">{{ $user->email }}</span>
            </div>
            <div class="credential-item">
                <span class="label">Password:</span>
                <span class="value">{{ $password }}</span>
            </div>
        </div>

        <p>You can login to the student portal using the link below:</p>
        
        <div style="text-align: center;">
            <a href="{{ url('http://localhost:3000/login') }}" class="button">Login to Portal</a>
        </div>

        <p>If you have any questions or need assistance, please contact the school administration.</p>

        <div class="footer">
            <p>This is an automated message, please do not reply to this email.</p>
            <p>&copy; {{ date('Y') }} School Management System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>