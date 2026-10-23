<?php

namespace App\Livewire;

use App\Services\RexlCloud\ApiException;
use App\Services\RexlCloud\UnauthenticatedException;
use App\Support\CloudUser;
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

    /**
     * Name or UUID of any player who joined once, online or not.
     */
    public string $lookup = '';

    public function find(): void
    {
        $this->validate(['lookup' => 'required|string|max:64'], ['lookup.required' => 'Enter a name or UUID.']);
        $query = trim($this->lookup);
        try {
            $player = $this->cloud()->player($query);
            $this->redirectRoute('players.show', ['player' => $player['uuid']], navigate: true);
        } catch (UnauthenticatedException) {
            CloudUser::forget();
            $this->redirectRoute('login');
        } catch (ApiException $e) {
            $this->addError('lookup', $e->status === 404 ? "No player {$query} has joined the network." : $e->getMessage());
        }
    }

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
