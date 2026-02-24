<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; background:#f4f4f4; margin:0; padding:0; }
        .wrap { max-width:560px; margin:40px auto; background:#fff; border-radius:8px; overflow:hidden; }
        .header { background:#1a6b3c; padding:24px; text-align:center; }
        .header h1 { color:#fff; margin:0; font-size:20px; }
        .body { padding:32px; color:#333; line-height:1.6; }
        .btn { display:inline-block; background:#1a6b3c; color:#fff; text-decoration:none;
               padding:12px 28px; border-radius:6px; font-weight:bold; margin:20px 0; }
        .footer { padding:16px 32px; background:#f9f9f9; font-size:12px; color:#888; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="header">
        <h1>🦅 ACM Portal</h1>
    </div>
    <div class="body">
        <p>Hello {{ $user->name }},</p>
        <p>Please verify your email address to complete your ACM Portal account setup.</p>
        <p>Click the button below to verify. This link expires in <strong>48 hours</strong>.</p>
        <p><a href="{{ $verifyUrl }}" class="btn">Verify Email Address</a></p>
        <p>If you did not register for an ACM Portal account, you can safely ignore this email.</p>
        <p>— Abia Community Manchester</p>
    </div>
    <div class="footer">
        If the button doesn't work, copy and paste this link into your browser:<br>
        <a href="{{ $verifyUrl }}">{{ $verifyUrl }}</a>
    </div>
</div>
</body>
</html>
