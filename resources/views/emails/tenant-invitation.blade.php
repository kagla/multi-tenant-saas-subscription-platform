<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .btn { display: inline-block; padding: 12px 24px; background: {{ $tenant->primary_color ?? '#1f2937' }}; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600; }
        .footer { margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #e5e7eb; font-size: 0.875rem; color: #6b7280; }
        .header { border-bottom: 3px solid {{ $tenant->primary_color ?? '#3B82F6' }}; padding-bottom: 1rem; margin-bottom: 1.5rem; }
    </style>
</head>
<body>
    <div class="header">
        @if($tenant->logo_path)
            <img src="{{ asset('storage/' . $tenant->logo_path) }}" alt="{{ $tenantName }}" style="max-height: 40px;">
        @else
            <strong style="font-size: 1.25rem; color: {{ $tenant->primary_color ?? '#1f2937' }};">{{ $tenantName }}</strong>
        @endif
    </div>

    <h2>You're invited!</h2>
    <p>You've been invited to join <strong>{{ $tenantName }}</strong> as a <strong>{{ $role }}</strong>.</p>
    <p>Click the button below to accept the invitation:</p>
    <p style="margin: 2rem 0;">
        <a href="{{ $acceptUrl }}" class="btn">Accept Invitation</a>
    </p>
    <div class="footer">
        <p>This invitation will expire {{ $expiresAt->diffForHumans() }}.</p>
        <p>If you didn't expect this invitation, you can safely ignore this email.</p>
    </div>
</body>
</html>
