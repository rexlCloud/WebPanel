<?php

namespace App\Livewire;

use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;

#[Title('Groups')]
class GroupList extends CloudComponent
{
    public bool $creating = false;

    /** Options for the create form, loaded when it is opened. */
    public array $serverJars = [];

    public array $wrapperNames = [];

    #[Validate('required|regex:/^[A-Za-z0-9_-]{1,32}$/')]
    public string $name = '';

    #[Validate('required|in:SERVER,PROXY')]
    public string $type = 'SERVER';

    #[Validate('required|integer|min:256')]
    public int $memory = 1024;

    public bool $static = false;

    #[Validate('required_if:static,true')]
    public string $wrapper = '';

    #[Validate('required|integer|min:0|max:100')]
    public int $minServers = 1;

    public bool $fallback = false;

    #[Validate('required|string')]
    public string $serverJar = '';

    public function openCreate(): void
    {
        $this->serverJars = $this->load(fn ($cloud) => $cloud->serverJars());
        $this->wrapperNames = collect($this->load(fn ($cloud) => $cloud->wrappers()))
            ->where('connected', true)->pluck('name')->all();
        $this->resetValidation();
        $this->creating = true;
    }

    public function updatedType(): void
    {
        // The selected jar has to match the group type
        $this->serverJar = '';
    }

    public function create(): void
    {
        $this->validate(messages: ['name.regex' => 'Only letters, numbers, - and _ (max. 32).']);

        $message = match (true) {
            $this->selectedJarIsCustom() => "The group {$this->name} is being created. Put your server.jar into templates/{$this->name}/default/; its servers start once it is there.",
            $this->selectedJarIsBuilt() => "The group {$this->name} is being created. Spigot is built with BuildTools first, so it appears in a few minutes.",
            default => "The group {$this->name} is being created. The server jar is downloaded in the background.",
        };

        $created = $this->attempt(fn ($cloud) => $cloud->createGroup([
            'name' => $this->name,
            'type' => $this->type,
            'memory' => $this->memory,
            'static' => $this->static,
            'wrapper' => $this->static ? $this->wrapper : null,
            'min_servers' => $this->minServers,
            'fallback' => $this->fallback,
            'server_jar' => $this->serverJar,
        ]), $message);

        if ($created) {
            $this->creating = false;
            $this->reset('name', 'type', 'memory', 'static', 'wrapper', 'minServers', 'fallback', 'serverJar');
        }
    }

    /**
     * Whether the selected jar is built on first use (Spigot via BuildTools) instead of downloaded.
     */
    private function selectedJarIsBuilt(): bool
    {
        return (bool) (collect($this->serverJars)->firstWhere('id', $this->serverJar)['built'] ?? false);
    }

    /**
     * Whether the user brings their own server.jar (e.g. Minestom) instead of a downloaded one.
     */
    private function selectedJarIsCustom(): bool
    {
        return (bool) (collect($this->serverJars)->firstWhere('id', $this->serverJar)['custom'] ?? false);
    }

    public function startServers(string $group, int $amount = 1): void
    {
        $this->attempt(fn ($cloud) => $cloud->startServers($group, $amount),
            "Starting {$amount} server(s) of {$group}.");
    }

    public function render()
    {
        return view('livewire.group-list', [
            'groups' => collect($this->load(fn ($cloud) => $cloud->groups()))->sortBy('group_name', SORT_NATURAL),
            'jarsForType' => collect($this->serverJars)->where('type', $this->type)->values(),
            'selectedJarIsBuilt' => $this->selectedJarIsBuilt(),
            'selectedJarIsBungeeCord' => (collect($this->serverJars)->firstWhere('id', $this->serverJar)['name'] ?? '') === 'BungeeCord',
            'selectedJarIsCustom' => $this->selectedJarIsCustom(),
        ]);
    }
}
