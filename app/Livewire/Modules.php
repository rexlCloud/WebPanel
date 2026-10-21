<?php

namespace App\Livewire;

use Livewire\Attributes\Title;

#[Title('Modules')]
class Modules extends CloudComponent
{
    public function loadModule(string $file): void
    {
        $this->attempt(fn ($cloud) => $cloud->loadModule($file), "Loaded {$file}.");
    }

    public function reloadModule(string $name): void
    {
        $this->attempt(fn ($cloud) => $cloud->reloadModule($name), "Reloaded {$name}.");
    }

    public function disableModule(string $name): void
    {
        $this->attempt(fn ($cloud) => $cloud->disableModule($name), "Disabled {$name}.");
    }

    public function render()
    {
        $modules = $this->load(fn ($cloud) => $cloud->modules(), ['loaded' => [], 'files' => [], 'self' => null]);

        return view('livewire.modules', [
            'loaded' => collect($modules['loaded'])->sortBy('name')->values(),
            'files' => collect($modules['files'])->sort()->values(),
            'self' => $modules['self'],
        ]);
    }
}
