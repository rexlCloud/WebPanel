<?php

namespace App\Livewire;

use App\Services\RexlCloud\ApiException;
use App\Services\RexlCloud\UnauthenticatedException;

class ServerDetail extends CloudComponent
{
    public string $name;

    public function mount(string $name): void
    {
        // Throws a 404 if the server does not exist
        $this->name = $this->cloud()->server($name)['server_name'];
    }

    /**
     * WebSocket URL with a fresh single-use ticket, or null if the console cannot be streamed.
     */
    public function consoleSocketUrl(): ?string
    {
        try {
            return $this->cloud()->consoleSocketUrl($this->name);
        } catch (UnauthenticatedException) {
            $this->redirectRoute('login');
        } catch (ApiException) {
        }

        return null;
    }

    /**
     * Console lines for the polling fallback.
     *
     * @return list<string>
     */
    public function consoleLines(): array
    {
        return array_values(array_filter(
            $this->load(fn ($cloud) => $cloud->console($this->name)),
            fn ($line) => $line !== null,
        ));
    }

    public function sendCommand(string $command): bool
    {
        $command = trim($command);
        if ($command === '') {
            return false;
        }

        return $this->attempt(fn ($cloud) => $cloud->command($this->name, $command));
    }

    public function stop(): void
    {
        $this->attempt(fn ($cloud) => $cloud->stopServer($this->name), "Stopping {$this->name}.");
    }

    /**
     * Whether the dialog for saving the server into a template is open.
     */
    public bool $savingTemplate = false;

    /**
     * Template variant the server is saved into.
     */
    public string $saveVariant = 'default';

    public function saveToTemplate(): void
    {
        $this->validate(['saveVariant' => ['required', 'regex:/^[A-Za-z0-9_-]{1,32}$/']],
            ['saveVariant.regex' => 'Letters, numbers, - and _ (max. 32).']);
        try {
            $result = $this->cloud()->saveServerToTemplate($this->name, $this->saveVariant);
            $this->notify($result['message'] ?? "Saving {$this->name} into the template.");
            $this->savingTemplate = false;
        } catch (UnauthenticatedException) {
            $this->expireSession();
        } catch (ApiException $e) {
            $this->notify($e->getMessage(), 'error');
        }
    }

    public function restart(): void
    {
        $this->attempt(fn ($cloud) => $cloud->restartServer($this->name), "Restarting {$this->name}.");
    }

    public function kill(): void
    {
        $this->attempt(fn ($cloud) => $cloud->killServer($this->name), "Killing {$this->name}.");
    }

    public function render()
    {
        $server = $this->load(fn ($cloud) => $cloud->servers(), []);
        $server = collect($server)->firstWhere('server_name', $this->name);

        return view('livewire.server-detail', ['server' => $server])
            ->title($this->name);
    }
}
