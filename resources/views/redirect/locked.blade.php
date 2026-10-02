<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Protected link — {{ $domain }}</title>
<style>
body{font-family:system-ui,sans-serif;margin:0;background:#fff;color:#171717}
.wrap{max-width:400px;margin:0 auto;padding:64px 20px;text-align:center}
input{width:100%;box-sizing:border-box;margin:12px 0;padding:12px 14px;border-radius:12px;border:1px solid #d4d4d4;font-size:15px}
button{width:100%;padding:12px;border-radius:12px;border:1px solid #171717;background:#171717;color:#fff;font-size:15px;cursor:pointer}
.error{color:#b91c1c;font-size:14px}
.muted{opacity:.65;font-size:14px}
code{font-family:monospace;background:#f0f0f0;padding:2px 6px;border-radius:6px}
</style>
</head>
<body>
<div class="wrap">
<p style="font-size:44px;margin:0">🔒</p>
<h1 style="font-size:22px">This link is password-protected.</h1>
<p class="muted"><code>{{ $domain }}/{{ $slug }}</code></p>
<form method="POST" action="/{{ $slug }}/unlock">
@csrf
<input type="password" name="password" placeholder="Password" autocomplete="current-password" required autofocus>
@if ($errors->has('password'))<p class="error">{{ $errors->first('password') }}</p>@endif
<button type="submit">Unlock</button>
</form>
</div>
</body>
</html>
