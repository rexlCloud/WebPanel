<div wire:poll.30s="refreshChart">
    <x-page-header title="Dashboard" :subtitle="$overview ? 'rexlCloud '.$overview['version'] : null">
        @if ($overview && $cloudUser?->can('MANAGE_MAINTENANCE'))
            <x-slot:actions>
                <button type="button"
                        wire:click="toggleMaintenance({{ $overview['maintenance'] ? 'false' : 'true' }})"
                        wire:confirm="{{ $overview['maintenance'] ? 'Disable maintenance mode? Players can join again.' : 'Enable maintenance mode? Players without permission are kicked.' }}"
                        @class(['btn', 'btn-danger' => ! $overview['maintenance'], 'btn-secondary' => $overview['maintenance']])>
                    <x-icon name="shield" class="size-4"/>
                    {{ $overview['maintenance'] ? 'Disable maintenance' : 'Enable maintenance' }}
                </button>
            </x-slot:actions>
        @endif
    </x-page-header>

    <x-load-error :message="$loadError"/>

    @if ($overview)
        @if ($overview['maintenance'])
            <div class="mb-6 flex items-center gap-3 rounded-lg border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-200">
                <x-icon name="shield" class="size-5"/> The network is in maintenance mode.
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat label="Players online" :value="$overview['players_online']" icon="players"/>
            <x-stat label="Servers online" :value="$overview['servers_online'].' / '.$overview['servers_total']"
                    :hint="$overview['proxies_online'].' '.Str::plural('proxy', $overview['proxies_online'])" icon="server"/>
            <x-stat label="Wrappers connected" :value="$overview['wrappers_connected'].' / '.$overview['wrappers_registered']" icon="wrapper"/>
            <x-stat label="Groups" :value="$overview['groups']" icon="groups"/>
        </div>
    @endif

    <div class="mt-8 grid gap-4 lg:grid-cols-2">
        @foreach (['players' => 'Players', 'servers' => 'Online servers', 'cpu' => 'CPU usage (%)', 'memory' => 'Used memory (GB)'] as $series => $label)
            <div class="card p-5" wire:key="chart-{{ $series }}">
                <h2 class="text-sm font-medium text-slate-300">{{ $label }}</h2>
                <p class="text-xs text-slate-500">Last hour</p>
                @if (count($chart['labels'] ?? []) === 0)
                    <p class="grid h-48 place-items-center text-sm text-slate-500">No statistics recorded yet.</p>
                @else
                    <div class="mt-4 h-48" wire:ignore
                         x-data="statsChart('{{ $series }}', @js($chart))"
                         @stats-updated.window="update($event.detail.chart)">
                        <canvas x-ref="canvas"></canvas>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
