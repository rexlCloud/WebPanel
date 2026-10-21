<?php

namespace App\Livewire;

use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;

#[Title('Dashboard')]
class Dashboard extends CloudComponent
{
    /**
     * Chart series built from the stats of the last hour.
     */
    public array $chart = [];

    public function mount(): void
    {
        $this->chart = $this->buildChart();
    }

    /**
     * Called by the poll; pushes fresh chart data to the browser.
     */
    public function refreshChart(): void
    {
        $this->chart = $this->buildChart();
        $this->dispatch('stats-updated', chart: $this->chart);
    }

    public function toggleMaintenance(bool $enabled): void
    {
        $this->attempt(
            fn ($cloud) => $cloud->setMaintenance($enabled),
            $enabled ? 'Maintenance mode enabled.' : 'Maintenance mode disabled.',
        );
    }

    public function render()
    {
        return view('livewire.dashboard', [
            'overview' => $this->load(fn ($cloud) => $cloud->overview(), null),
        ]);
    }

    private function buildChart(): array
    {
        // The API returns the newest entry first
        $stats = array_reverse($this->load(fn ($cloud) => $cloud->stats(60)));

        return [
            'labels' => array_map(fn ($s) => Carbon::createFromTimestampMs($s['time'])->format('H:i'), $stats),
            'players' => array_column($stats, 'online_players'),
            'servers' => array_column($stats, 'online_servers'),
            'cpu' => array_map(fn ($s) => round($s['cpu_usage'] * 100, 1), $stats),
            // The master stores memory in MB
            'memory' => array_map(fn ($s) => round($s['used_memory'] / 1024, 2), $stats),
        ];
    }
}
