<?php

namespace App\Livewire;

use Livewire\Attributes\Title;

#[Title('Panel users')]
class Users extends CloudComponent
{
    public bool $creating = false;

    public string $username = '';

    public string $password = '';

    /** @var list<string> */
    public array $newPermissions = [];

    /** User whose permissions are being edited. */
    public ?string $editing = null;

    /** @var list<string> */
    public array $editPermissions = [];

    public function openCreate(): void
    {
        $this->reset('username', 'password', 'newPermissions');
        $this->resetValidation();
        $this->creating = true;
    }

    public function create(): void
    {
        $this->validate([
            'username' => ['required', 'regex:/^[A-Za-z0-9_-]{3,32}$/'],
            'password' => ['required', 'string', 'min:8'],
        ], ['username.regex' => '3 to 32 letters, numbers, - or _.']);

        $created = $this->attempt(
            fn ($cloud) => $cloud->createUser($this->username, $this->password, $this->newPermissions),
            "Created the user {$this->username}.");

        if ($created) {
            $this->creating = false;
            $this->reset('username', 'password', 'newPermissions');
        }
    }

    public function edit(string $username, array $permissions): void
    {
        $this->editing = $username;
        $this->editPermissions = $permissions;
    }

    public function savePermissions(): void
    {
        $saved = $this->attempt(fn ($cloud) => $cloud->setPermissions($this->editing, $this->editPermissions),
            "Saved the permissions of {$this->editing}.");

        if ($saved) {
            $this->editing = null;
        }
    }

    public function delete(string $username): void
    {
        $this->attempt(fn ($cloud) => $cloud->deleteUser($username), "Deleted the user {$username}.");
    }

    public function render()
    {
        return view('livewire.users', [
            'users' => $this->load(fn ($cloud) => $cloud->users()),
            'permissions' => $this->load(fn ($cloud) => $cloud->permissions()),
        ]);
    }
}
