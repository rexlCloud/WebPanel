@props(['label', 'percent', 'detail' => null])
@php($percent = max(0, min(100, $percent)))
<div>
    <div class="flex justify-between text-xs text-slate-400">
        <span>{{ $label }}</span>
        <span class="tabular-nums">{{ $detail ?? round($percent).'%' }}</span>
    </div>
    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-800">
        <div @class([
            'h-full rounded-full',
            'bg-emerald-400' => $percent < 70,
            'bg-amber-400' => $percent >= 70 && $percent < 90,
            'bg-red-400' => $percent >= 90,
        ]) style="width: {{ $percent }}%"></div>
    </div>
</div>
