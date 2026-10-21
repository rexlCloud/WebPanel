<?php

namespace App\Livewire;

use App\Support\CloudUser;
use Livewire\Attributes\Validate;

class GroupDetail extends CloudComponent
{
    public string $name;

    #[Validate('required|integer|min:101')]
    public int $memory = 1024;

    #[Validate('required|integer|min:0')]
    public int $maxPlayers = 100;

    #[Validate('required|integer|min:0|max:100')]
    public int $minServers = 1;

    public bool $fallback = false;

    public bool $removeOldLogs = false;

    /**
     * "java" for the one on the wrapper's PATH, or the path of a reported Java runtime.
     */
    #[Validate('required|string|max:500')]
    public string $javaCommand = 'java';

    /**
     * The Java command as saved, so it is only sent when it changed.
     */
    public string $savedJavaCommand = 'java';

    public array $javaRuntimes = [];

    #[Validate('required|integer|min:1|max:50')]
    public int $startAmount = 1;

    public function mount(string $name): void
    {
        // Throws a 404 if the group does not exist
        $group = $this->cloud()->group($name);
        $this->name = $group['group_name'];
        $this->fillSettings($group);
        if (CloudUser::current()?->can('MANAGE_GROUPS')) {
            $this->javaRuntimes = $this->load(fn ($cloud) => $cloud->javaRuntimes());
        }
    }

    public function save(): void
    {
        $this->validate([
            'memory' => 'required|integer|min:101',
            'maxPlayers' => 'required|integer|min:0',
            'minServers' => 'required|integer|min:0|max:100',
        ]);

        $changes = [
            'memory' => $this->memory,
            'max_players' => $this->maxPlayers,
            'min_servers' => $this->minServers,
            'fallback' => $this->fallback,
            'remove_old_logs' => $this->removeOldLogs,
        ];
        // Only when changed: the saved runtime may belong to a wrapper that is offline right now
        if ($this->javaCommand !== $this->savedJavaCommand) {
            $this->validateOnly('javaCommand');
            $changes['java_command'] = $this->javaCommand;
        }

        if ($this->attempt(fn ($cloud) => $cloud->updateGroup($this->name, $changes),
            'Settings saved. They apply to servers started from now on.')) {
            $this->savedJavaCommand = $this->javaCommand;
        }
    }

    public function refreshJavaRuntimes(): void
    {
        $this->javaRuntimes = $this->load(fn ($cloud) => $cloud->javaRuntimes());
    }

    public function startServers(): void
    {
        $this->validateOnly('startAmount');
        $this->attempt(fn ($cloud) => $cloud->startServers($this->name, $this->startAmount),
            "Starting {$this->startAmount} server(s).");
    }

    public function syncTemplate(): void
    {
        $this->attempt(fn ($cloud) => $cloud->syncTemplate($this->name), 'The template is being sent to all wrappers.');
    }

    public function stopServer(string $server): void
    {
        $this->attempt(fn ($cloud) => $cloud->stopServer($server), "Stopping {$server}.");
    }

    public function delete(): void
    {
        if ($this->attempt(fn ($cloud) => $cloud->deleteGroup($this->name))) {
            session()->flash('notify', "The group {$this->name} was deleted and its servers are stopping.");
            $this->redirectRoute('groups', navigate: true);
        }
    }

    public function render()
    {
        $group = $this->load(fn ($cloud) => $cloud->group($this->name), null);
        $servers = collect($this->load(fn ($cloud) => $cloud->servers()))
            ->where('server_group', $this->name)
            ->sortBy('server_name', SORT_NATURAL)
            ->values();

        $knownRuntime = $this->javaCommand === 'java'
            || collect($this->javaRuntimes)->contains('path', $this->javaCommand);

        return view('livewire.group-detail', ['group' => $group, 'servers' => $servers, 'knownRuntime' => $knownRuntime])
            ->title($this->name);
    }

    private function fillSettings(array $group): void
    {
        $this->memory = $group['group_memory'];
        $this->maxPlayers = $group['group_map_players'];
        $this->minServers = $group['group_min_servers'];
        $this->fallback = $group['group_fallback'];
        $this->removeOldLogs = $group['group_remove_old_logs'];
        $this->javaCommand = $this->savedJavaCommand = ($group['group_java_cmd'] ?? '') ?: 'java';
    }
}
