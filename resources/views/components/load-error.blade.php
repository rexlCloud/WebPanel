@props(['message'])
@if ($message)
    <div class="mb-6 flex items-start gap-3 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
        <x-icon name="warning" class="mt-0.5 size-5 shrink-0"/>
        <span>{{ $message }}</span>
    </div>
@endif
