<?php

namespace App\Livewire;

use Livewire\Attributes\Title;
use Livewire\Attributes\Url;

#[Title('Servers')]
class ServerList extends CloudComponent
{
    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $group = '';

    #[Url(except: '')]
    public string $state = '';

    public function stop(string $name): void
    {
        $this->attempt(fn ($cloud) => $cloud->stopServer($name), "Stopping {$name}.");
    }

    public function restart(string $name): void
    {
        $this->attempt(fn ($cloud) => $cloud->restartServer($name), "Restarting {$name}.");
    }

    public function kill(string $name): void
    {
        $this->attempt(fn ($cloud) => $cloud->killServer($name), "Killing {$name}.");
    }

    public function render()
    {
        $servers = $this->load(fn ($cloud) => $cloud->servers());
        $groups = collect($servers)->pluck('server_group')->unique()->sort()->values();

        $filtered = collect($servers)
            ->when($this->search !== '', fn ($c) => $c->filter(
                fn ($s) => str_contains(mb_strtolower($s['server_name']), mb_strtolower($this->search))))
            ->when($this->group !== '', fn ($c) => $c->where('server_group', $this->group))
            ->when($this->state !== '', fn ($c) => $c->where('online_state', $this->state))
            ->sortBy('server_name', SORT_NATURAL)
            ->values();

        return view('livewire.server-list', [
            'servers' => $filtered,
            'total' => count($servers),
            'groups' => $groups,
        ]);
    }
}
