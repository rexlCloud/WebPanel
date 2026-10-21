<?php

namespace App\Support;

/**
 * The panel user logged in through the RestModule, as stored in the session.
 * Permissions are only used to hide actions; the RestModule enforces them.
 */
class CloudUser
{
    /**
     * @param  list<string>  $permissions
     */
    public function __construct(
        public readonly string $username,
        public readonly array $permissions,
    ) {
    }

    public static function current(): ?self
    {
        if (! session()->has('cloud.token') || ! session()->has('cloud.api_url')) {
            return null;
        }

        return new self(session('cloud.username', ''), session('cloud.permissions', []));
    }

    /**
     * @param  array{username: string, permissions: list<string>}  $user
     */
    public static function store(string $apiUrl, string $token, array $user): void
    {
        session()->put('cloud.api_url', $apiUrl);
        session()->put('cloud.token', $token);
        self::update($user);
    }

    /**
     * @param  array{username: string, permissions: list<string>}  $user
     */
    public static function update(array $user): void
    {
        session()->put('cloud.username', $user['username']);
        session()->put('cloud.permissions', $user['permissions']);
    }

    public static function forget(): void
    {
        session()->forget(['cloud.api_url', 'cloud.token', 'cloud.username', 'cloud.permissions']);
    }

    public function can(string ...$permissions): bool
    {
        return array_diff($permissions, $this->permissions) === [];
    }
}
