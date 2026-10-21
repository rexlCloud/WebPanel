<div>
    <x-page-header :title="$name" :back="route('servers')">
        @if ($server)
            <x-slot:actions>
                @if ($cloudUser?->can('MANAGE_TEMPLATES') && ($server['online_state'] ?? '') === 'online' && ! ($server['static'] ?? false))
                    <button type="button" wire:click="$set('savingTemplate', true)" class="btn btn-secondary"><x-icon name="download" class="size-4"/> Save to template</button>
                @endif
                @if ($cloudUser?->can('STOP_SERVER', 'START_SERVER'))
                    <button type="button" wire:click="restart" class="btn btn-secondary"><x-icon name="restart" class="size-4"/> Restart</button>
                @endif
                @if ($cloudUser?->can('STOP_SERVER'))
                    <button type="button" wire:click="stop" class="btn btn-secondary"><x-icon name="stop" class="size-4"/> Stop</button>
                    <button type="button" wire:click="kill" wire:confirm="Kill {{ $name }}? The process is terminated immediately and unsaved data is lost."
                            class="btn btn-danger"><x-icon name="bolt" class="size-4"/> Kill</button>
                @endif
            </x-slot:actions>
        @endif
    </x-page-header>

    <x-load-error :message="$loadError"/>

    @if ($savingTemplate)
        <x-modal title="Save {{ $name }} into a template" close="$set('savingTemplate', false)">
            <form wire:submit="saveToTemplate" class="space-y-4">
                <p class="text-sm text-slate-300">
                    Copies the server's files (worlds, configs, plugins) into the template of its group; files with the
                    same path are replaced, others stay. Logs, caches, the server jar and the cloud plugin are left out.
                    The worlds are saved first.
                </p>
                <div>
                    <label for="save-variant" class="label">Variant</label>
                    <input id="save-variant" wire:model="saveVariant" class="input" placeholder="default">
                    @error('saveVariant') <p class="field-error">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-slate-500">default: every new server gets it. Another name (e.g. map-desert) makes a variant; each new server gets one at random.</p>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="$set('savingTemplate', false)" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">Save</button>
                </div>
                <div wire:loading wire:target="saveToTemplate" class="text-sm text-cyan-300">Saving, this can take a minute…</div>
            </form>
        </x-modal>
    @endif

    <div wire:poll.5s>
        @if ($server)
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="card p-5">
                    <div class="text-sm text-slate-400">State</div>
                    <div class="mt-2"><x-state-badge :state="$server['online_state']"/></div>
                    @if ($server['server_state'])
                        <div class="mt-2 text-xs text-slate-500">Game state: {{ $server['server_state'] }}</div>
                    @endif
                </div>
                <x-stat label="Players" :value="$server['online_players'].' / '.$server['max_players']"/>
                <div class="card p-5">
                    <div class="text-sm text-slate-400">Group</div>
                    <div class="mt-2 font-medium text-white">
                        @if ($cloudUser?->can('SEE_ALL_GROUPS'))
                            <a href="{{ route('groups.show', $server['server_group']) }}" wire:navigate class="hover:text-cyan-300">{{ $server['server_group'] }}</a>
                        @else
                            {{ $server['server_group'] }}
                        @endif
                    </div>
                    <div class="mt-1 text-xs text-slate-500">{{ $server['server_type'] }} · {{ $server['memory'] }} MB{{ $server['static'] ? ' · static' : '' }}</div>
                </div>
                <div class="card p-5">
                    <div class="text-sm text-slate-400">Wrapper</div>
                    <div class="mt-2 font-medium text-white">{{ $server['wrapper'] ?? '–' }}</div>
                    <div class="mt-1 text-xs text-slate-500">Port {{ $server['server_port'] }}</div>
                </div>
            </div>
            @if (count($server['player_names']) > 0)
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($server['player_names'] as $player)
                        <span class="rounded-md bg-slate-800 px-2 py-1 text-xs text-slate-300">{{ $player }}</span>
                    @endforeach
                </div>
            @endif
        @else
            <x-empty icon="server" title="This server is no longer running" text="It was stopped or replaced. The console below shows its last output."/>
        @endif
    </div>

    @if ($cloudUser?->can('SEE_SERVER_CONSOLE'))
        <section class="card mt-6 overflow-hidden" wire:ignore
                 x-data="serverConsole({ canExecute: @js($cloudUser->can('EXECUTE_ON_SERVER')) })">
            <div class="flex items-center justify-between border-b border-slate-800 px-4 py-3">
                <h2 class="flex items-center gap-2 text-sm font-medium text-slate-300"><x-icon name="terminal" class="size-4"/> Console</h2>
                <span class="flex items-center gap-2 text-xs text-slate-400">
                    <span class="size-2 rounded-full"
                          :class="{ 'bg-emerald-400': status === 'live', 'bg-amber-400 animate-pulse': status === 'connecting', 'bg-sky-400': status === 'polling', 'bg-slate-500': status === 'ended' }"></span>
                    <span x-text="{ live: 'Live', connecting: 'Connecting…', polling: 'Updating every 3s', ended: 'Ended' }[status]"></span>
                </span>
            </div>
            <div x-ref="scroller" class="h-[28rem] overflow-y-auto bg-slate-950 px-4 py-3 font-mono text-xs leading-5 text-slate-300">
                <div x-ref="output" class="break-words whitespace-pre-wrap"></div>
            </div>
            <form x-show="canExecute" @submit.prevent="send" class="flex items-center gap-2 border-t border-slate-800 bg-slate-900 px-3 py-2">
                <span class="font-mono text-sm text-cyan-400">&gt;</span>
                <input x-model="command" @keydown.arrow-up.prevent="previousCommand" @keydown.arrow-down.prevent="nextCommand"
                       :disabled="status === 'ended'" type="text" maxlength="1000" autocomplete="off" spellcheck="false"
                       placeholder="Enter a command" aria-label="Console command"
                       class="flex-1 bg-transparent font-mono text-sm text-white placeholder:text-slate-600 focus:outline-none">
                <button type="submit" class="btn btn-secondary btn-sm" :disabled="status === 'ended'">Send</button>
            </form>
        </section>
    @endif
</div>
