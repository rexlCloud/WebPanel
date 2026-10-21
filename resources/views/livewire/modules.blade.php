<div>
    <x-page-header title="Modules" subtitle="Extensions loaded into the master"/>

    <x-load-error :message="$loadError"/>

    <h2 class="mb-3 text-sm font-medium text-slate-300">Loaded ({{ $loaded->count() }})</h2>
    @if ($loaded->isEmpty())
        <x-empty icon="module" title="No modules loaded"/>
    @else
        <div class="card mb-8 divide-y divide-slate-800">
            @foreach ($loaded as $module)
                <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4" wire:key="module-{{ $module['name'] }}">
                    <div class="min-w-0">
                        <div class="font-medium text-white">
                            {{ $module['name'] }}
                            <span class="ml-1 text-xs font-normal text-slate-500">v{{ $module['version'] }} by {{ $module['author'] }}</span>
                            @if ($module['name'] === $self)
                                <span class="ml-1 rounded bg-cyan-500/15 px-1.5 py-0.5 text-xs text-cyan-300">serves this panel</span>
                            @endif
                        </div>
                        <div class="truncate font-mono text-xs text-slate-500">{{ $module['main_class'] }}</div>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" wire:click="reloadModule(@js($module['name']))" class="btn btn-secondary btn-sm">
                            <x-icon name="restart" class="size-4"/> Reload
                        </button>
                        @if ($module['name'] !== $self)
                            <button type="button" wire:click="disableModule(@js($module['name']))"
                                    wire:confirm="Disable {{ $module['name'] }}?"
                                    class="btn btn-danger btn-sm"><x-icon name="stop" class="size-4"/> Disable</button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <h2 class="mb-3 text-sm font-medium text-slate-300">Files in the module folder</h2>
    @if ($files->isEmpty())
        <x-empty icon="module" title="The module folder is empty" text="Copy module jars into the master's modules folder."/>
    @else
        <div class="card divide-y divide-slate-800">
            @foreach ($files as $file)
                <div class="flex items-center justify-between gap-3 px-5 py-3" wire:key="file-{{ $file }}">
                    <span class="font-mono text-sm text-slate-300">{{ $file }}</span>
                    <button type="button" wire:click="loadModule(@js($file))" class="btn btn-secondary btn-sm">
                        <x-icon name="play" class="size-4"/> Load
                    </button>
                </div>
            @endforeach
        </div>
    @endif
</div>
