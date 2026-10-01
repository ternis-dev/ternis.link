<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $og['title'] }}</title>
<link rel="canonical" href="{{ $destination }}">
<meta property="og:title" content="{{ $og['title'] }}">
@if($og['description'] !== null)<meta property="og:description" content="{{ $og['description'] }}">@endif
@if($og['image'] !== null)<meta property="og:image" content="{{ $og['image'] }}">@endif
<meta property="og:url" content="{{ $link->short_url }}">
<meta http-equiv="refresh" content="0;url={{ $destination }}">
</head>
<body>
<p><a href="{{ $destination }}">Continue to {{ $og['title'] }}</a></p>
<script>window.location.replace(@js($destination));</script>
</body>
</html>
