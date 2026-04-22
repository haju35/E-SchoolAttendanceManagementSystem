<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Welcome to E-School Attendance System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 10px;
        }
        .header {
            background-color: #4f46e5;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            padding: 20px;
        }
        .password-box {
            background-color: #f3f4f6;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 18px;
            text-align: center;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            background-color: #4f46e5;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            padding: 20px;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to E-School Attendance System</h1>
        </div>
        
        <div class="content">
            <p>Dear {{ $name }},</p>
            
            <p>An account has been created for you in the E-School Attendance System with the role of <strong>{{ ucfirst($role) }}</strong>.</p>
            
            <p>Here are your login credentials:</p>
            
            <div class="password-box">
                <strong>Email:</strong> {{ $email }}<br>
                <strong>Temporary Password:</strong> {{ $password }}
            </div>
            
            <p>Please click the button below to login and change your password:</p>
            
            <p style="text-align: center;">
                <a href="{{ $login_url }}" class="button">Login to Your Account</a>
            </p>
            
            <p><strong>Important:</strong> For security reasons, please change your password after your first login.</p>
            
            <p>If you have any questions or need assistance, please contact the system administrator.</p>
            
            <p>Best regards,<br>E-School Attendance System Team</p>
        </div>
        
        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
            <p>&copy; {{ date('Y') }} E-School Attendance System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>