<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Email Preview</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; padding: 20px; background: #f7fafc; }
        .card { background: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .subject { font-weight: 600; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="subject">Subject: {{ $subject }}</div>

        <div class="email-body">
            {!! $htmlBody !!}
        </div>
    </div>
</body>
</html>
