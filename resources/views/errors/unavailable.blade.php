<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="15">
    <title>Cloud unavailable · {{ config('app.name') }}</title>
    @fonts
    @vite(['resources/css/app.css'])
</head>
<body class="grid h-full place-items-center bg-slate-950 px-4 text-center text-slate-200 antialiased">
<main class="max-w-md">
    <x-icon name="cloud" class="mx-auto size-12 text-slate-600"/>
    <h1 class="mt-4 text-xl font-semibold text-white">The cloud is not reachable</h1>
    <p class="mt-2 text-sm text-slate-400">{{ $message }} Make sure the master is running with the RestModule loaded. This page retries automatically.</p>
    <form method="POST" action="{{ route('logout') }}" class="mt-6">
        @csrf
        <button type="submit" class="btn btn-secondary">Log out</button>
    </form>
</main>
</body>
</html>
