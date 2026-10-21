<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-slate-950 text-slate-200 antialiased">
@php
    $navigation = [
        ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'home', 'permission' => null],
        ['route' => 'servers', 'label' => 'Servers', 'icon' => 'server', 'permission' => 'SEE_ALL_SERVERS'],
        ['route' => 'groups', 'label' => 'Groups', 'icon' => 'groups', 'permission' => 'SEE_ALL_GROUPS'],
        ['route' => 'players', 'label' => 'Players', 'icon' => 'players', 'permission' => 'SEE_PLAYERS'],
        ['route' => 'wrappers', 'label' => 'Wrappers', 'icon' => 'wrapper', 'permission' => 'SEE_WRAPPERS'],
        ['route' => 'templates', 'label' => 'Templates', 'icon' => 'folder', 'permission' => 'MANAGE_TEMPLATES'],
        ['route' => 'server-list', 'label' => 'Server list', 'icon' => 'signal', 'permission' => 'MANAGE_MAINTENANCE'],
        ['route' => 'modules', 'label' => 'Modules', 'icon' => 'module', 'permission' => 'MANAGE_MODULES'],
        ['route' => 'users', 'label' => 'Panel users', 'icon' => 'users', 'permission' => 'SEE_ALL_USERS'],
    ];
@endphp
<div x-data="{ sidebar: false }" class="min-h-full">
    {{-- Mobile top bar --}}
    <div class="sticky top-0 z-30 flex items-center gap-3 border-b border-slate-800 bg-slate-950/90 px-4 py-3 backdrop-blur lg:hidden">
        <button type="button" @click="sidebar = true" class="rounded-md p-1.5 text-slate-400 hover:bg-slate-800 hover:text-white" aria-label="Open navigation">
            <x-icon name="menu" class="size-6"/>
        </button>
        <span class="font-semibold text-white">rexlCloud</span>
    </div>

    {{-- Sidebar --}}
    <div x-show="sidebar" x-transition.opacity class="fixed inset-0 z-40 bg-black/60 lg:hidden" @click="sidebar = false" x-cloak></div>
    <aside :class="sidebar ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-slate-800 bg-slate-900 transition-transform lg:translate-x-0">
        <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3 px-6 py-5">
            <span class="grid size-9 place-items-center rounded-lg bg-cyan-500/15 text-cyan-400 ring-1 ring-cyan-500/30">
                <x-icon name="cloud" class="size-5"/>
            </span>
            <span class="text-lg font-semibold tracking-tight text-white">rexlCloud</span>
        </a>
        <nav class="flex-1 space-y-1 px-3">
            @foreach ($navigation as $item)
                @if ($item['permission'] === null || $cloudUser?->can($item['permission']))
                    @php($active = request()->routeIs($item['route'], $item['route'].'.*'))
                    <a href="{{ route($item['route']) }}" wire:navigate
                       @class([
                           'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                           'bg-slate-800 text-white' => $active,
                           'text-slate-400 hover:bg-slate-800/60 hover:text-white' => ! $active,
                       ])>
                        <x-icon :name="$item['icon']" class="size-5"/>
                        {{ $item['label'] }}
                    </a>
                @endif
            @endforeach
        </nav>
        <div class="border-t border-slate-800 p-3">
            <a href="{{ route('account') }}" wire:navigate class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-slate-300 hover:bg-slate-800/60">
                <span class="grid size-8 place-items-center rounded-full bg-slate-700 text-xs font-semibold uppercase text-white">
                    {{ mb_substr($cloudUser?->username ?? '?', 0, 2) }}
                </span>
                <span class="min-w-0 flex-1 truncate">{{ $cloudUser?->username }}</span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="mt-1 flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-slate-400 hover:bg-slate-800/60 hover:text-white">
                    <x-icon name="logout" class="size-5"/> Log out
                </button>
            </form>
        </div>
    </aside>

    <main class="lg:pl-64">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            {{ $slot }}
        </div>
    </main>
</div>

<x-toasts/>
@livewireScriptConfig
</body>
</html>
