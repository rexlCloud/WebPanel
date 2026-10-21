<div>
    <x-page-header title="Server list" subtitle="The message of the day the proxies show in the Minecraft server list"/>

    <form wire:submit="save" class="grid gap-6 xl:grid-cols-2">
        @foreach ([
            ['model' => 'motd', 'label' => 'MOTD', 'hint' => 'Shown normally', 'preview' => $motdPreview, 'active' => ! $maintenance],
            ['model' => 'maintenanceMotd', 'label' => 'Maintenance MOTD', 'hint' => 'Shown while maintenance mode is on', 'preview' => $maintenancePreview, 'active' => $maintenance],
        ] as $field)
            <section class="card space-y-3 p-5" wire:key="{{ $field['model'] }}">
                <div class="flex items-center justify-between">
                    <label for="{{ $field['model'] }}" class="label mb-0">{{ $field['label'] }}</label>
                    @if ($field['active'])
                        <span class="rounded-full bg-emerald-500/15 px-2 py-0.5 text-xs text-emerald-300 ring-1 ring-emerald-500/30">Shown now</span>
                    @else
                        <span class="text-xs text-slate-500">{{ $field['hint'] }}</span>
                    @endif
                </div>
                <textarea id="{{ $field['model'] }}" wire:model.live.debounce.400ms="{{ $field['model'] }}" rows="3" maxlength="1000"
                          spellcheck="false" class="input font-mono text-sm"></textarea>
                @error($field['model']) <p class="field-error">{{ $message }}</p> @enderror
                <div class="rounded-lg border border-slate-800 bg-[#1e1e1e] p-3">
                    <div class="flex gap-3">
                        <div class="grid size-12 shrink-0 place-items-center rounded bg-slate-700 text-slate-400">
                            <x-icon name="cloud" class="size-6"/>
                        </div>
                        <div class="min-w-0 font-mono text-sm leading-5 text-[#aaaaaa]">
                            <div class="text-white">Minecraft Server</div>
                            <div class="whitespace-pre-wrap break-words">{!! $field['preview'] !!}</div>
                        </div>
                    </div>
                </div>
            </section>
        @endforeach

        <div class="xl:col-span-2 flex flex-wrap items-center justify-between gap-3">
            <p class="text-xs text-slate-500">
                MiniMessage, e.g. <code class="text-slate-300">&lt;gold&gt;</code>,
                <code class="text-slate-300">&lt;gradient:#09add3:#4ade80&gt;text&lt;/gradient&gt;</code>,
                <code class="text-slate-300">&lt;bold&gt;</code>, or classic codes like <code class="text-slate-300">&amp;a</code>.
                <code class="text-slate-300">&lt;newline&gt;</code> starts the second line.
            </p>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">Save</button>
        </div>
    </form>
</div>
