<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px 20px;
        }
        .content p {
            margin: 15px 0;
        }
        .highlight {
            background-color: #f0f4ff;
            padding: 15px;
            border-left: 4px solid #667eea;
            margin: 20px 0;
        }
        .footer {
            background-color: #f9f9f9;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #eee;
        }
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
        .cta-button {
            display: inline-block;
            background-color: #667eea;
            color: #fff;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            margin: 20px 0;
        }
        .cta-button:hover {
            background-color: #764ba2;
        }
        ul {
            margin: 15px 0;
            padding-left: 20px;
        }
        li {
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Datastat Institute</h1>
        </div>

        <div class="content">
            {!! $htmlBody !!}
        </div>

        <div class="footer">
            <p><strong>Datastat Institute</strong></p>
            <p>
                Email: <a href="mailto:info@datastatresearch.org">info@datastatresearch.org</a> | 
                Phone: +254 724 527 104
            </p>
            <p>
                Website: <a href="https://www.datastatresearch.org">www.datastatresearch.org</a>
            </p>
            <p style="margin-top: 20px; color: #999;">
                © {{ now()->year }} Datastat Institute. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
