<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $notification->title }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background: #ffffff;
            padding: 30px;
            border: 1px solid #e0e0e0;
            border-top: none;
        }
        .announcement-badge {
            display: inline-block;
            background: #ff6b6b;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .priority-high {
            border-left: 4px solid #ff6b6b;
            padding-left: 20px;
        }
        .priority-urgent {
            border-left: 4px solid #c92a2a;
            padding-left: 20px;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 12px;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0; font-size: 24px;">
            @if($notification->is_announcement)
                📢 Announcement
            @else
                Notification
            @endif
        </h1>
    </div>
    
    <div class="content {{ $notification->priority === 'high' || $notification->priority === 'urgent' ? 'priority-' . $notification->priority : '' }}">
        @if($notification->is_announcement)
            <div class="announcement-badge">ANNOUNCEMENT</div>
        @endif
        
        <h2 style="margin-top: 0; color: #333;">{{ $notification->title }}</h2>
        
        <div style="white-space: pre-wrap; color: #555;">{{ $notification->message }}</div>
        
        @if($notification->data && count($notification->data) > 0)
            <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 6px;">
                <strong>Details:</strong>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    @foreach($notification->data as $key => $value)
                        <li><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ $value }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
    
    <div class="footer">
        <p>This is an automated notification from {{ config('app.name') }}</p>
        <p>You received this email because you are subscribed to notifications.</p>
    </div>
</body>
</html>

