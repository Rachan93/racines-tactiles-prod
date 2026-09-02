<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Created</title>
    <style>
        /* Tailwind CSS (lite) for email */
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background-color: #f4f4f7;
            color: #51545e;
            margin: 0;
            padding: 0;
            -webkit-text-size-adjust: none;
        }
        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #f4f4f7;
            padding: 0;
            margin: 0;
            line-height: 1.4;
        }
        .content {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            padding: 10px 0;
        }
        .header h1 {
            margin: 0;
            color: #333333;
            font-size: 24px;
            font-weight: bold;
        }
        .body {
            padding: 20px;
            text-align: left;
        }
        .body h2 {
            color: #333333;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .body p {
            margin: 0;
            color: #51545e;
            font-size: 16px;
        }
        .footer {
            text-align: center;
            padding: 10px 0;
            color: #a8aaaf;
            font-size: 12px;
        }
        .footer a {
            color: #3869d4;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="content">
            <div class="header">
                <h1>New Course Created</h1>
            </div>
            <div class="body">
                <h2>{{ $course->name }}</h2>
                <p>A new course titled "<strong>{{ $course->name }}</strong>" has been created. You can view the details by logging into your dashboard.</p>
                <p>Thank you for using our service!</p>
            </div>
            <div class="footer">
                <p>&copy; {{ date('Y') }} Your Company. All rights reserved.</p>
                <p><a href="{{ url('/') }}">Visit our website</a></p>
            </div>
        </div>
    </div>
</body>
</html>

