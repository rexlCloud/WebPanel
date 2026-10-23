<div @unless ($editing) wire:poll.10s @endunless>
    <x-page-header :title="$name" :back="route('players')" :subtitle="$uuid">
        @if ($player && $cloudUser?->can('MANAGE_PLAYERS'))
            <x-slot:actions>
                <button type="button" wire:click="add" class="btn btn-primary"><x-icon name="plus" class="size-4"/> Add value</button>
            </x-slot:actions>
        @endif
    </x-page-header>

    <x-load-error :message="$loadError"/>

    @if ($player)
        <div class="grid gap-6 lg:grid-cols-3">
            <section class="card space-y-4 p-5">
                <div class="flex items-center gap-4">
                    <img src="https://mc-heads.net/avatar/{{ $player['uuid'] }}/64" alt="" class="size-16 rounded-lg" loading="lazy">
                    <div class="min-w-0">
                        <div class="truncate text-lg font-semibold text-white">{{ $player['name'] }}</div>
                        @if ($player['online'])
                            <span class="inline-flex items-center gap-1.5 text-sm text-emerald-400"><span class="size-2 rounded-full bg-emerald-400"></span> Online</span>
                        @else
                            <span class="inline-flex items-center gap-1.5 text-sm text-slate-400"><span class="size-2 rounded-full bg-slate-500"></span> Offline</span>
                        @endif
                    </div>
                </div>
                <dl class="space-y-3 text-sm">
                    @if ($player['online'])
                        <div>
                            <dt class="text-slate-500">Server</dt>
                            <dd class="text-slate-200">
                                @if ($player['server'] ?? null)
                                    <a href="{{ route('servers.show', $player['server']) }}" wire:navigate class="hover:text-cyan-300">{{ $player['server'] }}</a>
                                @else
                                    –
                                @endif
                                @if ($player['server_group'] ?? null)
                                    <span class="text-xs text-slate-500">({{ $player['server_group'] }})</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Proxy</dt>
                            <dd class="text-slate-200">{{ $player['proxy'] ?? '–' }}</dd>
                        </div>
                        @if ($player['login'] ?? null)
                            <div>
                                <dt class="text-slate-500">Online since</dt>
                                <dd class="text-slate-200" title="{{ \Illuminate\Support\Carbon::createFromTimestampMs($player['login'])->toDateTimeString() }}">
                                    {{ \Illuminate\Support\Carbon::createFromTimestampMs($player['login'])->diffForHumans() }}
                                </dd>
                            </div>
                        @endif
                    @endif
                    @foreach (['last_login' => 'Last joined', 'first_login' => 'First joined'] as $field => $label)
                        @if ($player[$field] > 0)
                            <div>
                                <dt class="text-slate-500">{{ $label }}</dt>
                                <dd class="text-slate-200" title="{{ \Illuminate\Support\Carbon::createFromTimestampMs($player[$field])->toDateTimeString() }}">
                                    {{ \Illuminate\Support\Carbon::createFromTimestampMs($player[$field])->diffForHumans() }}
                                </dd>
                            </div>
                        @endif
                    @endforeach
                </dl>
            </section>

            <section class="lg:col-span-2">
                <h2 class="mb-3 text-sm font-medium text-slate-300">Player data ({{ count($player['data']) }})</h2>
                @if (empty($player['data']))
                    <x-empty icon="database" title="No data stored" text="Plugins store values with player.setData(key, value)."/>
                @else
                    <div class="card overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-800">
                            <thead>
                            <tr>
                                <th class="table-head">Key</th>
                                <th class="table-head">Type</th>
                                <th class="table-head">Value</th>
                                <th class="table-head"><span class="sr-only">Actions</span></th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800">
                            @foreach ($player['data'] as $entry)
                                <tr wire:key="data-{{ $entry['key'] }}" class="align-top hover:bg-slate-800/40">
                                    <td class="table-cell font-mono text-sm text-white">{{ $entry['key'] }}</td>
                                    <td class="table-cell">
                                        <span class="rounded bg-slate-800 px-1.5 py-0.5 font-mono text-xs text-slate-300" title="{{ $entry['type'] }}">{{ \Illuminate\Support\Str::afterLast($entry['type'], '.') }}</span>
                                    </td>
                                    <td class="table-cell w-full min-w-48 whitespace-normal text-slate-200">
                                        @if (! $entry['readable'])
                                            <span class="text-sm italic text-slate-500" title="{{ $entry['type'] }}">Only the plugin that stored it can read this ({{ $entry['size'] }} bytes)</span>
                                        @elseif (is_array($entry['value']))
                                            <pre class="max-h-48 overflow-auto whitespace-pre-wrap break-all font-mono text-xs text-slate-300">{{ json_encode($entry['value'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                                        @elseif (is_bool($entry['value']))
                                            <span class="font-mono text-sm">{{ $entry['value'] ? 'true' : 'false' }}</span>
                                        @else
                                            <span class="break-all font-mono text-sm">{{ $entry['value'] }}</span>
                                        @endif
                                    </td>
                                    <td class="table-cell text-right">
                                        @if ($cloudUser?->can('MANAGE_PLAYERS'))
                                            <div class="flex justify-end gap-1">
                                                @if ($entry['editable'])
                                                    <button type="button" wire:click="edit(@js($entry['key']), @js($entry['type']), @js($entry['value']))" class="btn btn-ghost btn-sm" title="Change">
                                                        <x-icon name="pencil" class="size-4"/><span class="sr-only">Change {{ $entry['key'] }}</span>
                                                    </button>
                                                @endif
                                                <button type="button" wire:click="remove(@js($entry['key']))"
                                                        wire:confirm="Remove {{ $entry['key'] }} from {{ $name }}? Plugins will no longer find this value."
                                                        class="btn btn-ghost btn-sm text-red-400 hover:text-red-300" title="Remove">
                                                    <x-icon name="trash" class="size-4"/><span class="sr-only">Remove {{ $entry['key'] }}</span>
                                                </button>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>
    @endif

    @if ($editing)
        <x-modal :title="($existing ? 'Change ' : 'Add a value for ').($existing ? $key : $name)" close="close">
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label for="data-key" class="label">Key</label>
                    <input id="data-key" wire:model="key" type="text" maxlength="255" class="input font-mono" @disabled($existing)
                           placeholder="e.g. myplugin:coins" @unless ($existing) autofocus @endunless>
                    @error('key') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="data-type" class="label">Type</label>
                    <select id="data-type" wire:model.live="type" class="input">
                        @foreach ($types as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Plugins read the value with exactly this type, e.g. <code>player.&lt;Integer&gt;getData(key)</code> for a whole number.</p>
                    @error('type') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="data-value" class="label">Value</label>
                    @if ($type === 'boolean')
                        <select id="data-value" wire:model="value" class="input">
                            <option value="">Choose…</option>
                            <option value="true">true</option>
                            <option value="false">false</option>
                        </select>
                    @elseif ($type === 'string')
                        <textarea id="data-value" wire:model="value" rows="3" maxlength="65536" class="input font-mono" @if ($existing) autofocus @endif></textarea>
                    @else
                        <input id="data-value" wire:model="value" type="text" class="input font-mono" @if ($existing) autofocus @endif
                               inputmode="{{ in_array($type, ['int', 'long', 'short', 'byte', 'double', 'float'], true) ? 'decimal' : 'text' }}">
                    @endif
                    @error('value') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="close" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
