<?php

namespace App\Livewire;

use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;

#[Title('Wrappers')]
class Wrappers extends CloudComponent
{
    public bool $adding = false;

    #[Validate('required|string|max:255')]
    public string $address = '';

    #[Validate('nullable|uuid')]
    public string $key = '';

    /**
     * Credentials of the wrapper registered last; the key is only shown once.
     *
     * @var array{name: string, address: string, key: string}|null
     */
    public ?array $registered = null;

    public function register(): void
    {
        $this->validate();

        $this->attempt(function ($cloud) {
            $this->registered = $cloud->addWrapper(trim($this->address), $this->key !== '' ? $this->key : null);
        }, 'Wrapper registered.');

        if ($this->registered !== null) {
            $this->adding = false;
            $this->reset('address', 'key');
        }
    }

    public function stop(string $name): void
    {
        $this->attempt(fn ($cloud) => $cloud->stopWrapper($name), "Stopping {$name} and its servers.");
    }

    public function render()
    {
        return view('livewire.wrappers', [
            'wrappers' => $this->load(fn ($cloud) => $cloud->wrappers()),
        ]);
    }
}
