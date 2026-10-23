<?php

namespace App\Livewire;

/**
 * A player who joined at least once, online or not, with the data plugins stored for them.
 */
class PlayerDetail extends CloudComponent
{
    /**
     * Types the cloud can write; plugins read a value with exactly its type (e.g. Integer for "int").
     */
    public const TYPES = [
        'string' => 'Text (String)',
        'int' => 'Whole number (Integer)',
        'long' => 'Large whole number (Long)',
        'double' => 'Decimal (Double)',
        'float' => 'Decimal (Float)',
        'boolean' => 'true / false (Boolean)',
        'short' => 'Small whole number (Short)',
        'byte' => 'Byte',
        'uuid' => 'UUID',
    ];

    public string $uuid;

    public string $name = '';

    /**
     * Whether the dialog for adding or changing a value is open.
     */
    public bool $editing = false;

    /**
     * Whether the dialog changes an existing value (its key cannot be changed then).
     */
    public bool $existing = false;

    public string $key = '';

    public string $type = 'string';

    public string $value = '';

    public function mount(string $player): void
    {
        // Throws a 404 if the player never joined
        $found = $this->cloud()->player($player);
        $this->uuid = $found['uuid'];
        $this->name = $found['name'];
    }

    public function add(): void
    {
        $this->open('', 'string', '', false);
    }

    /**
     * @param  mixed  $value  the current value as the API showed it
     */
    public function edit(string $key, string $type, mixed $value): void
    {
        $this->open($key, $type, is_bool($value) ? ($value ? 'true' : 'false') : (string) $value, true);
    }

    public function close(): void
    {
        $this->editing = false;
    }

    public function save(): void
    {
        if (! $this->can('MANAGE_PLAYERS')) {
            return;
        }
        $this->validate([
            'key' => 'required|string|max:255',
            'type' => 'required|in:'.implode(',', array_keys(self::TYPES)),
            'value' => 'present|string|max:65536',
        ], ['type.in' => 'Choose one of the types.']);

        if ($this->attempt(fn ($cloud) => $cloud->setPlayerData($this->uuid, $this->key, $this->type, $this->value),
            "Saved {$this->key} for {$this->name}.")) {
            $this->close();
        }
    }

    public function remove(string $key): void
    {
        if (! $this->can('MANAGE_PLAYERS')) {
            return;
        }
        $this->attempt(fn ($cloud) => $cloud->removePlayerData($this->uuid, $key), "Removed {$key} from {$this->name}.");
    }

    public function render()
    {
        $player = $this->load(fn ($cloud) => $cloud->player($this->uuid), null);
        if ($player !== null) {
            $this->name = $player['name'];
        }

        return view('livewire.player-detail', ['player' => $player, 'types' => self::TYPES])->title($this->name);
    }

    private function open(string $key, string $type, string $value, bool $existing): void
    {
        $this->key = $key;
        $this->type = array_key_exists($type, self::TYPES) ? $type : 'string';
        $this->value = $value;
        $this->existing = $existing;
        $this->editing = true;
        $this->resetValidation();
    }
}
