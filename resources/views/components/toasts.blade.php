{{-- Shows notifications dispatched from Livewire components ($this->dispatch('notify', ...)) --}}
<div x-data="toasts(@js(session('notify')))" @notify.window="add($event.detail)"
     class="pointer-events-none fixed inset-x-0 bottom-0 z-[60] flex flex-col items-center gap-2 p-4 sm:items-end">
    <template x-for="toast in items" :key="toast.id">
        <div x-transition.opacity
             class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-lg border px-4 py-3 text-sm shadow-lg backdrop-blur"
             :class="toast.type === 'error'
                ? 'border-red-500/30 bg-red-950/90 text-red-100'
                : 'border-emerald-500/30 bg-emerald-950/90 text-emerald-100'">
            <span class="flex-1" x-text="toast.message"></span>
            <button type="button" @click="remove(toast.id)" class="opacity-70 hover:opacity-100" aria-label="Dismiss">
                <x-icon name="x" class="size-4"/>
            </button>
        </div>
    </template>
</div>
