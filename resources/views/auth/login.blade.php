<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log in · {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @fonts
    @vite(['resources/css/app.css'])
</head>
<body class="grid h-full place-items-center bg-slate-950 px-4 text-slate-200 antialiased">
<main class="w-full max-w-sm">
    <div class="mb-8 flex flex-col items-center gap-3 text-center">
        <span class="grid size-12 place-items-center rounded-xl bg-cyan-500/15 text-cyan-400 ring-1 ring-cyan-500/30">
            <x-icon name="cloud" class="size-7"/>
        </span>
        <h1 class="text-2xl font-semibold tracking-tight text-white">rexlCloud</h1>
        <p class="text-sm text-slate-400">Log in with your panel account</p>
    </div>

    @if (session('status'))
        <p class="mb-4 rounded-lg border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-200">{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4 rounded-xl border border-slate-800 bg-slate-900 p-6 shadow-xl">
        @csrf
        <div>
            <label for="api_url" class="label">Cloud URL</label>
            <input id="api_url" name="api_url" type="url" value="{{ $apiUrl }}" required @if (! $apiUrl) autofocus @endif
                   placeholder="http://10.0.0.10:4343" autocomplete="url" class="input">
            <p class="mt-1 text-xs text-slate-500">Address of the RestModule on your master</p>
            @error('api_url')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="username" class="label">Username</label>
            <input id="username" name="username" type="text" value="{{ old('username') }}" required @if ($apiUrl) autofocus @endif autocomplete="username" class="input">
        </div>
        <div>
            <label for="password" class="label">Password</label>
            <input id="password" name="password" type="password" required autocomplete="current-password" class="input">
        </div>
        @error('username')
            <p class="text-sm text-red-400">{{ $message }}</p>
        @enderror
        <button type="submit" class="btn btn-primary w-full justify-center">Log in</button>
    </form>
</main>
</body>
</html>
