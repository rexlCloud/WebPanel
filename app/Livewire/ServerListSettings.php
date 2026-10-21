<?php

namespace App\Livewire;

use App\Support\ChatComponent;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;

/**
 * The MOTD the proxies show in the server list, with a preview rendered like the proxies do.
 */
#[Title('Server list')]
class ServerListSettings extends CloudComponent
{
    #[Validate('required|string|max:1000')]
    public string $motd = '';

    #[Validate('required|string|max:1000')]
    public string $maintenanceMotd = '';

    public bool $maintenance = false;

    public function mount(): void
    {
        $current = $this->load(fn ($cloud) => $cloud->motd(), null);
        if ($current) {
            $this->motd = $current['motd'] ?? '';
            $this->maintenanceMotd = $current['maintenance_motd'] ?? '';
            $this->maintenance = (bool) ($current['maintenance'] ?? false);
        }
    }

    public function save(): void
    {
        $this->validate();
        $this->attempt(fn ($cloud) => $cloud->updateMotd($this->motd, $this->maintenanceMotd),
            'Saved. The proxies show it with the next server list refresh.');
    }

    private function preview(string $text): string
    {
        if (trim($text) === '') {
            return '';
        }

        return ChatComponent::toHtml($this->load(fn ($cloud) => $cloud->renderText($text), $text));
    }

    public function render()
    {
        return view('livewire.server-list-settings', [
            'motdPreview' => $this->preview($this->motd),
            'maintenancePreview' => $this->preview($this->maintenanceMotd),
        ]);
    }
}
