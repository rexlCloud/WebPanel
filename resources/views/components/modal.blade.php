@props(['title', 'close'])
{{-- Rendered only while open; $close is the Livewire action that closes it --}}
<div class="fixed inset-0 z-50 flex items-end justify-center bg-black/60 p-4 sm:items-center" wire:keydown.escape.window="{{ $close }}">
    <div class="absolute inset-0" wire:click="{{ $close }}"></div>
    <div role="dialog" aria-modal="true" aria-label="{{ $title }}"
         class="relative max-h-full w-full max-w-lg overflow-y-auto rounded-xl border border-slate-800 bg-slate-900 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-800 px-6 py-4">
            <h2 class="text-base font-semibold text-white">{{ $title }}</h2>
            <button type="button" wire:click="{{ $close }}" class="text-slate-400 hover:text-white" aria-label="Close">
                <x-icon name="x" class="size-5"/>
            </button>
        </div>
        <div class="px-6 py-5">{{ $slot }}</div>
    </div>
</div>
