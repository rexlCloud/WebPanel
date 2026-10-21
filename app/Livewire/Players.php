<?php

namespace App\Livewire;

use Livewire\Attributes\Title;
use Livewire\Attributes\Url;

#[Title('Players')]
class Players extends CloudComponent
{
    #[Url(except: '')]
    public string $search = '';

    /** Player the message/kick dialog is open for. */
    public ?string $selectedUuid = null;

    public string $selectedName = '';

    public string $mode = 'message';

    public string $text = '';

    public function open(string $uuid, string $name, string $mode): void
    {
        $this->selectedUuid = $uuid;
        $this->selectedName = $name;
        $this->mode = $mode === 'kick' ? 'kick' : 'message';
        $this->text = '';
        $this->resetValidation();
    }

    public function close(): void
    {
        $this->selectedUuid = null;
    }

    public function submit(): void
    {
        $this->validate(['text' => ($this->mode === 'message' ? 'required' : 'nullable').'|string|max:256']);

        $done = $this->mode === 'kick'
            ? $this->attempt(fn ($cloud) => $cloud->kickPlayer($this->selectedUuid, $this->text ?: null),
                "{$this->selectedName} was kicked.")
            : $this->attempt(fn ($cloud) => $cloud->messagePlayer($this->selectedUuid, $this->text),
                "Message sent to {$this->selectedName}.");

        if ($done) {
            $this->close();
        }
    }

    public function render()
    {
        $players = collect($this->load(fn ($cloud) => $cloud->players()));

        return view('livewire.players', [
            'players' => $players
                ->when($this->search !== '', fn ($c) => $c->filter(fn ($p) => str_contains(
                    mb_strtolower($p['name'].' '.$p['uuid'].' '.$p['server']), mb_strtolower($this->search))))
                ->values(),
            'total' => $players->count(),
        ]);
    }
}
