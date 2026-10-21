<div>
    <x-page-header title="Templates" subtitle="Files every new server of a group starts with; changes reach the wrappers within seconds"/>

    <x-load-error :message="$loadError"/>

    <div class="grid gap-6 lg:grid-cols-[16rem_1fr]">
        {{-- Templates --}}
        <nav class="card h-fit divide-y divide-slate-800">
            @forelse ($templates as $templateGroup => $variants)
                <div class="p-3">
                    <div class="px-2 pb-1 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $templateGroup }}</div>
                    @foreach ($variants as $template)
                        @php($id = $template['group'].'/'.$template['variant'])
                        <button type="button" wire:click="select(@js($id))" wire:key="t-{{ $id }}"
                                @class(['flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm',
                                        'bg-cyan-500/15 text-cyan-200' => $group === $template['group'] && $variant === $template['variant'],
                                        'text-slate-300 hover:bg-slate-800' => ! ($group === $template['group'] && $variant === $template['variant'])])>
                            <x-icon name="folder" class="size-4 shrink-0"/> {{ $template['variant'] }}
                        </button>
                    @endforeach
                </div>
            @empty
                <x-empty icon="folder" title="No templates yet" text="Create a group first."/>
            @endforelse
        </nav>

        @if ($group !== '')
            <section class="space-y-4">
                <div class="card flex flex-wrap items-center gap-3 p-4">
                    <div class="min-w-0 flex-1 font-mono text-sm text-slate-300">
                        <button type="button" wire:click="$set('path', '')" class="hover:text-white">{{ $group }}/{{ $variant }}</button>@if ($path !== '')/{{ $path }}@endif
                    </div>
                    @if ($path !== '')
                        <button type="button" wire:click="up" class="btn btn-secondary btn-sm"><x-icon name="arrow-left" class="size-4"/> Up</button>
                    @endif
                    <label class="btn btn-primary btn-sm cursor-pointer">
                        <x-icon name="upload" class="size-4"/> Upload
                        <input type="file" wire:model="upload" class="hidden">
                    </label>
                    @if (! $isGlobal && $variant !== 'default' && $path === '')
                        <button type="button" wire:click="deleteVariant"
                                wire:confirm="Delete the variant {{ $variant }} with all its files?"
                                class="btn btn-danger btn-sm"><x-icon name="trash" class="size-4"/> Delete variant</button>
                    @endif
                </div>
                <div wire:loading wire:target="upload" class="text-sm text-cyan-300">Uploading…</div>
                @error('upload') <p class="field-error">{{ $message }}</p> @enderror

                @if ($editing !== null)
                    <form wire:submit="saveFile" class="card space-y-3 p-4">
                        <div class="flex items-center justify-between">
                            <div class="font-mono text-sm text-slate-300">{{ $editing }}</div>
                            <div class="flex gap-2">
                                <button type="button" wire:click="closeEditor" class="btn btn-secondary btn-sm">Close</button>
                                <button type="submit" class="btn btn-primary btn-sm">Save</button>
                            </div>
                        </div>
                        <textarea wire:model="content" rows="20" spellcheck="false" class="input font-mono text-xs"></textarea>
                    </form>
                @endif

                <div class="card divide-y divide-slate-800">
                    @forelse ($entries as $entry)
                        <div class="flex items-center gap-3 px-4 py-2.5" wire:key="e-{{ $path }}-{{ $entry['name'] }}">
                            @if ($entry['directory'])
                                <button type="button" wire:click="open(@js($entry['name']))" class="flex min-w-0 flex-1 items-center gap-2 text-left text-sm text-slate-200 hover:text-white">
                                    <x-icon name="folder" class="size-4 shrink-0 text-cyan-400"/> <span class="truncate">{{ $entry['name'] }}</span>
                                </button>
                            @else
                                <button type="button" wire:click="edit(@js($entry['name']))" class="flex min-w-0 flex-1 items-center gap-2 text-left text-sm text-slate-300 hover:text-white">
                                    <x-icon name="document" class="size-4 shrink-0 text-slate-500"/> <span class="truncate">{{ $entry['name'] }}</span>
                                </button>
                                <span class="text-xs text-slate-500">{{ $formatSize($entry['size']) }}</span>
                                <a href="{{ route('templates.download', ['group' => $group, 'variant' => $variant, 'path' => ltrim($path.'/'.$entry['name'], '/')]) }}"
                                   class="rounded p-1 text-slate-400 hover:bg-slate-800 hover:text-white" title="Download"><x-icon name="download" class="size-4"/></a>
                            @endif
                            <button type="button" wire:click="delete(@js($entry['name']))"
                                    wire:confirm="Delete {{ $entry['name'] }}{{ $entry['directory'] ? ' with everything in it' : '' }}?"
                                    class="rounded p-1 text-slate-400 hover:bg-red-500/15 hover:text-red-300" title="Delete"><x-icon name="trash" class="size-4"/></button>
                        </div>
                    @empty
                        <x-empty icon="folder" title="No files here yet" text="Upload files or create a folder below."/>
                    @endforelse
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <form wire:submit="createFolder" class="card space-y-2 p-4">
                        <label for="new-folder" class="label">New folder here</label>
                        <div class="flex gap-2">
                            <input id="new-folder" wire:model="newFolder" class="input" placeholder="plugins or plugins/LuckPerms">
                            <button type="submit" class="btn btn-secondary"><x-icon name="plus" class="size-4"/></button>
                        </div>
                        @error('newFolder') <p class="field-error">{{ $message }}</p> @enderror
                    </form>
                    @if (! $isGlobal)
                        <form wire:submit="createVariant" class="card space-y-2 p-4">
                            <label for="new-variant" class="label">New variant of {{ $group }}</label>
                            <div class="flex gap-2">
                                <input id="new-variant" wire:model="newVariant" class="input" placeholder="map-desert">
                                <button type="submit" class="btn btn-secondary"><x-icon name="plus" class="size-4"/></button>
                            </div>
                            @error('newVariant') <p class="field-error">{{ $message }}</p> @enderror
                            <p class="text-xs text-slate-500">Every new server gets default/ and then one variant at random, e.g. a map.</p>
                        </form>
                    @endif
                </div>
            </section>
        @endif
    </div>
</div>
