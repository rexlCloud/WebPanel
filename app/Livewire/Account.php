<?php

namespace App\Livewire;

use Livewire\Attributes\Title;

#[Title('Account')]
class Account extends CloudComponent
{
    public string $currentPassword = '';

    public string $newPassword = '';

    public string $newPassword_confirmation = '';

    public function changePassword(): void
    {
        $this->validate([
            'currentPassword' => ['required', 'string'],
            'newPassword' => ['required', 'string', 'min:8', 'confirmed', 'different:currentPassword'],
        ]);

        $changed = $this->attempt(fn ($cloud) => $cloud->changePassword($this->currentPassword, $this->newPassword),
            'Password changed. Other devices have been logged out.');

        if ($changed) {
            $this->reset('currentPassword', 'newPassword', 'newPassword_confirmation');
        }
    }

    public function render()
    {
        return view('livewire.account');
    }
}
