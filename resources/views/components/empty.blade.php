@props(['icon' => 'cloud', 'title', 'text' => null])
<div class="rounded-xl border border-dashed border-slate-800 px-6 py-12 text-center">
    <x-icon :name="$icon" class="mx-auto size-8 text-slate-600"/>
    <p class="mt-3 font-medium text-slate-300">{{ $title }}</p>
    @if ($text)
        <p class="mt-1 text-sm text-slate-500">{{ $text }}</p>
    @endif
    {{ $slot }}
</div>
