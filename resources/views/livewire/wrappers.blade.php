<div @unless ($adding) wire:poll.10s @endunless>
    <x-page-header title="Wrappers" subtitle="Machines that run the servers">
        @if ($cloudUser?->can('MANAGE_WRAPPERS'))
            <x-slot:actions>
                <button type="button" wire:click="$set('adding', true)" class="btn btn-primary"><x-icon name="plus" class="size-4"/> Register wrapper</button>
            </x-slot:actions>
        @endif
    </x-page-header>

    <x-load-error :message="$loadError"/>

    @if ($registered)
        <div class="mb-6 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-5 text-sm">
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-2">
                    <p class="font-medium text-emerald-200">{{ $registered['name'] }} was registered for {{ $registered['address'] }}.</p>
                    <p class="text-emerald-100/80">Put this key into <code class="rounded bg-black/30 px-1">config/wrapper.json</code> as <code class="rounded bg-black/30 px-1">authKey</code> on that machine. It is not shown again.</p>
                    <div class="flex items-center gap-2" x-data="{ copied: false }">
                        <code class="rounded-lg bg-black/40 px-3 py-2 font-mono text-white select-all">{{ $registered['key'] }}</code>
                        <button type="button" class="btn btn-secondary btn-sm"
                                @click="navigator.clipboard.writeText(@js($registered['key'])); copied = true; setTimeout(() => copied = false, 2000)">
                            <x-icon name="key" class="size-4"/> <span x-text="copied ? 'Copied' : 'Copy'"></span>
                        </button>
                    </div>
                </div>
                <button type="button" wire:click="$set('registered', null)" class="text-emerald-200/70 hover:text-white" aria-label="Dismiss">
                    <x-icon name="x" class="size-5"/>
                </button>
            </div>
        </div>
    @endif

    @if (count($wrappers) === 0)
        <x-empty icon="wrapper" title="No wrappers registered" text="Register a wrapper, then start it with the generated key."/>
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($wrappers as $wrapper)
                <div class="card p-5" wire:key="wrapper-{{ $wrapper['name'] }}">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-lg font-semibold text-white">{{ $wrapper['name'] }}</div>
                            <div class="font-mono text-xs text-slate-500">{{ $wrapper['address'] }}</div>
                        </div>
                        <x-state-badge :state="$wrapper['connected'] ? (($wrapper['in_shutdown'] ?? false) ? 'stopping' : 'connected') : 'offline'"/>
                    </div>

                    @if ($wrapper['connected'])
                        @php($total = max(1, $wrapper['total_memory']))
                        <div class="mt-5 space-y-3">
                            <x-meter label="CPU" :percent="$wrapper['cpu_usage'] * 100"/>
                            <x-meter label="Memory" :percent="$wrapper['used_memory'] / $total * 100"
                                     :detail="number_format($wrapper['used_memory'] / 1073741824, 1).' / '.number_format($wrapper['total_memory'] / 1073741824, 1).' GB'"/>
                        </div>
                        <div class="mt-4 text-xs text-slate-500">
                            {{ count($wrapper['servers']) }} {{ Str::plural('server', count($wrapper['servers'])) }}@if (count($wrapper['servers'])): {{ implode(', ', $wrapper['servers']) }}@endif
                        </div>
                        @if ($cloudUser?->can('MANAGE_WRAPPERS'))
                            <div class="mt-4 border-t border-slate-800 pt-4">
                                <button type="button" wire:click="stop(@js($wrapper['name']))"
                                        wire:confirm="Stop {{ $wrapper['name'] }}? All of its servers are stopped too."
                                        class="btn btn-danger btn-sm"><x-icon name="stop" class="size-4"/> Stop wrapper</button>
                            </div>
                        @endif
                    @else
                        <p class="mt-5 text-sm text-slate-500">Not connected. Start the wrapper on {{ $wrapper['address'] }} to bring it online.</p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @if ($adding)
        <x-modal title="Register wrapper" close="$set('adding', false)">
            <form wire:submit="register" class="space-y-4">
                <div>
                    <label for="wrapper-address" class="label">IP address of the wrapper</label>
                    <input id="wrapper-address" wire:model="address" type="text" class="input" placeholder="10.0.0.12" autofocus>
                    @error('address') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="wrapper-key" class="label">Key (optional)</label>
                    <input id="wrapper-key" wire:model="key" type="text" class="input font-mono" placeholder="Generated if empty">
                    <p class="mt-1 text-xs text-slate-500">Use the key a new wrapper printed on its first start, or leave it empty to generate one.</p>
                    @error('key') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="$set('adding', false)" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary">Register</button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
