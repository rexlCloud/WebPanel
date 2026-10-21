@props(['label', 'value', 'hint' => null, 'icon' => null])
<div class="rounded-xl border border-slate-800 bg-slate-900 p-5">
    <div class="flex items-center justify-between text-sm text-slate-400">
        {{ $label }}
        @if ($icon)
            <x-icon :name="$icon" class="size-5 text-slate-500"/>
        @endif
    </div>
    <div class="mt-2 text-3xl font-semibold tabular-nums tracking-tight text-white">{{ $value }}</div>
    @if ($hint)
        <div class="mt-1 text-xs text-slate-500">{{ $hint }}</div>
    @endif
</div>
