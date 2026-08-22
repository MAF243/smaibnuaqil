<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ config('app.name', 'SMA Ibnu Aqil') }}</title>
  <x-ui.assets />
</head>
<body class="ui-gradient min-h-screen text-slate-950">
  <div class="flex min-h-screen items-center justify-center p-4">
    <div class="w-full max-w-md">
      <div class="mb-6 text-center">
        <a href="/" class="inline-flex items-center gap-3"><img src="{{ asset('asset/logo.jpg') }}" class="h-12 w-12 rounded-2xl object-cover ring-1 ring-border" alt=""><span class="text-xl font-black tracking-tight">SMA Ibnu'Aqil</span></a>
      </div>
      <x-ui.card>{{ $slot }}</x-ui.card>
    </div>
  </div>
</body>
</html>
