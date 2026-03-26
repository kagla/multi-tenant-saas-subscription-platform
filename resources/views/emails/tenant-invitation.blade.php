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

    <h2>초대되었습니다!</h2>
    <p><strong>{{ $tenantName }}</strong>에 <strong>{{ $role }}</strong>(으)로 초대되었습니다.</p>
    <p>아래 버튼을 클릭하여 초대를 수락하세요:</p>
    <p style="margin: 2rem 0;">
        <a href="{{ $acceptUrl }}" class="btn">초대 수락</a>
    </p>
    <div class="footer">
        <p>이 초대는 {{ $expiresAt->diffForHumans() }} 후에 만료됩니다.</p>
        <p>예상하지 못한 초대라면, 이 이메일을 무시해도 됩니다.</p>
    </div>
</body>
</html>
