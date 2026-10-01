<!DOCTYPE html>
<html lang="{{ $page->locale ?? 'en' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>{{ $page->title }}</title>
<style>
body{font-family:system-ui,sans-serif;margin:0;background:#fff;color:#171717}
.wrap{max-width:400px;margin:0 auto;padding:64px 20px;text-align:center}
input{width:100%;box-sizing:border-box;margin:12px 0;padding:12px 14px;border-radius:12px;border:1px solid #d4d4d4;font-size:15px}
button{width:100%;padding:12px;border-radius:12px;border:1px solid #171717;background:#171717;color:#fff;font-size:15px;cursor:pointer}
.error{color:#b91c1c;font-size:14px}
.muted{opacity:.65;font-size:14px}
</style>
</head>
<body>
<div class="wrap">
<h1 style="font-size:22px">🔒 {{ $page->title }}</h1>
<p class="muted">This page is password-protected.</p>
<form method="POST" action="/unlock/{{ $page->id }}">
@csrf
<input type="password" name="password" placeholder="Password" autocomplete="current-password" required autofocus>
@if ($errors->has('password'))<p class="error">{{ $errors->first('password') }}</p>@endif
<button type="submit">Unlock</button>
</form>
</div>
</body>
</html>
