{{ $notification->title }}

@if($notification->is_announcement)
📢 ANNOUNCEMENT
@endif

{{ $notification->message }}

@if($notification->data && count($notification->data) > 0)
Details:
@foreach($notification->data as $key => $value)
- {{ ucfirst(str_replace('_', ' ', $key)) }}: {{ $value }}
@endforeach
@endif

---
This is an automated notification from {{ config('app.name') }}
You received this email because you are subscribed to notifications.

