<?php

namespace App\Services\RexlCloud;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Client for the RestModule API (/api/v2).
 */
class RexlCloudClient
{
    /**
     * @param  string  $baseUrl  RestModule URL entered on the login page, e.g. http://10.0.0.10:4343
     */
    public function __construct(private string $baseUrl, private ?string $token = null)
    {
        $this->baseUrl = self::normalizeUrl($baseUrl);
    }

    public static function fromSession(): self
    {
        return new self((string) session('cloud.api_url', ''), session('cloud.token'));
    }

    /**
     * Strips trailing slashes and a pasted "/api/v2" suffix.
     */
    public static function normalizeUrl(string $url): string
    {
        return preg_replace('#(/api/v2)?/*$#i', '', trim($url));
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    // Authentication

    /**
     * @return array{token: string, expires_at: int, user: array{username: string, permissions: list<string>}}
     */
    public function login(string $username, string $password): array
    {
        return $this->send('post', 'auth/login', ['username' => $username, 'password' => $password]);
    }

    public function logout(): void
    {
        $this->send('post', 'auth/logout');
    }

    /**
     * @return array{username: string, permissions: list<string>}
     */
    public function me(): array
    {
        return $this->send('get', 'auth/me');
    }

    public function changePassword(string $currentPassword, string $newPassword): void
    {
        $this->send('post', 'auth/password', [
            'current_password' => $currentPassword,
            'new_password' => $newPassword,
        ]);
    }

    // System

    public function overview(): array
    {
        return $this->send('get', 'overview');
    }

    public function stats(int $limit = 60): array
    {
        return $this->send('get', 'stats', ['limit' => $limit]);
    }

    public function maintenance(): bool
    {
        return (bool) ($this->send('get', 'maintenance')['enabled'] ?? false);
    }

    public function setMaintenance(bool $enabled): void
    {
        $this->send('put', 'maintenance', ['enabled' => $enabled]);
    }

    /**
     * MOTD of the proxies as MiniMessage.
     *
     * @return array{motd: string, maintenance_motd: string, maintenance: bool}
     */
    public function motd(): array
    {
        return $this->send('get', 'motd');
    }

    /**
     * @return array{motd: string, maintenance_motd: string, maintenance: bool}
     */
    public function updateMotd(string $motd, string $maintenanceMotd): array
    {
        return $this->send('put', 'motd', ['motd' => $motd, 'maintenance_motd' => $maintenanceMotd]);
    }

    /**
     * A text rendered like the proxies do, as JSON chat component.
     */
    public function renderText(string $text): mixed
    {
        return $this->send('post', 'motd/preview', ['text' => $text])['component'] ?? '';
    }

    /**
     * @return list<string>
     */
    public function permissions(): array
    {
        return $this->send('get', 'permissions');
    }

    // Servers

    public function servers(): array
    {
        return $this->send('get', 'servers');
    }

    public function server(string $name): array
    {
        return $this->send('get', 'servers/'.$this->segment($name));
    }

    /**
     * @return list<string|null>
     */
    public function console(string $name): array
    {
        return $this->send('get', 'servers/'.$this->segment($name).'/console')['lines'] ?? [];
    }

    /**
     * @return array{ticket: string, expires_in: int, path: string}
     */
    public function consoleTicket(string $name): array
    {
        return $this->send('post', 'servers/'.$this->segment($name).'/console/ticket');
    }

    public function command(string $name, string $command): void
    {
        $this->send('post', 'servers/'.$this->segment($name).'/command', ['command' => $command]);
    }

    public function stopServer(string $name): array
    {
        return $this->send('post', 'servers/'.$this->segment($name).'/stop');
    }

    public function killServer(string $name): array
    {
        return $this->send('post', 'servers/'.$this->segment($name).'/kill');
    }

    public function restartServer(string $name): array
    {
        return $this->send('post', 'servers/'.$this->segment($name).'/restart');
    }

    /**
     * Saves the files of a running server into a template variant of its group.
     */
    public function saveServerToTemplate(string $name, string $variant): array
    {
        return $this->send('post', 'servers/'.$this->segment($name).'/save-template', ['variant' => $variant]);
    }

    // Templates

    /**
     * @return list<array{group: string, variant: string, type: string}>
     */
    public function templates(): array
    {
        return $this->send('get', 'templates');
    }

    /**
     * @return list<array{name: string, directory: bool, size: int, modified: int}>
     */
    public function templateFiles(string $group, string $variant, string $path = ''): array
    {
        return $this->send('get', $this->templatePath($group, $variant).'/files', ['path' => $path]);
    }

    /**
     * The raw response of a file download, for streaming it on.
     */
    public function downloadTemplateFile(string $group, string $variant, string $path): Response
    {
        return $this->raw(fn () => $this->request()->withOptions(['stream' => true])
            ->get($this->templatePath($group, $variant).'/file', ['path' => $path]));
    }

    /**
     * Uploads a file into a template, replacing it if it exists.
     *
     * @param  resource|string  $content
     */
    public function writeTemplateFile(string $group, string $variant, string $path, mixed $content): array
    {
        return $this->decode($this->raw(fn () => $this->request()
            ->timeout(600)
            ->withBody($content, 'application/octet-stream')
            ->put($this->templatePath($group, $variant).'/file?'.http_build_query(['path' => $path]))));
    }

    public function createTemplateFolder(string $group, string $variant, string $path): array
    {
        return $this->send('post', $this->templatePath($group, $variant).'/folder?'.http_build_query(['path' => $path]));
    }

    public function deleteTemplateFile(string $group, string $variant, string $path): array
    {
        return $this->send('delete', $this->templatePath($group, $variant).'/file?'.http_build_query(['path' => $path]));
    }

    private function templatePath(string $group, string $variant): string
    {
        return 'templates/'.$this->segment($group).'/'.$this->segment($variant);
    }

    // Groups

    public function groups(): array
    {
        return $this->send('get', 'groups');
    }

    public function group(string $name): array
    {
        return $this->send('get', 'groups/'.$this->segment($name));
    }

    public function serverJars(): array
    {
        return $this->send('get', 'server-jars');
    }

    /**
     * Java installations reported by the connected wrappers.
     *
     * @return list<array{path: string, version: string, major: int, vendor: string, name: string, wrappers: list<string>}>
     */
    public function javaRuntimes(): array
    {
        return $this->send('get', 'java-runtimes');
    }

    public function createGroup(array $group): array
    {
        return $this->send('post', 'groups', $group);
    }

    public function updateGroup(string $name, array $changes): array
    {
        return $this->send('patch', 'groups/'.$this->segment($name), $changes);
    }

    public function deleteGroup(string $name): array
    {
        return $this->send('delete', 'groups/'.$this->segment($name));
    }

    public function startServers(string $group, int $amount): array
    {
        return $this->send('post', 'groups/'.$this->segment($group).'/start', ['amount' => $amount]);
    }

    public function syncTemplate(string $group): array
    {
        return $this->send('post', 'groups/'.$this->segment($group).'/sync-template');
    }

    // Players

    public function players(): array
    {
        return $this->send('get', 'players');
    }

    public function kickPlayer(string $uuid, ?string $message = null): void
    {
        $this->send('post', 'players/'.$this->segment($uuid).'/kick', array_filter(['message' => $message]));
    }

    public function messagePlayer(string $uuid, string $message): void
    {
        $this->send('post', 'players/'.$this->segment($uuid).'/message', ['message' => $message]);
    }

    /**
     * A player who joined at least once, online or not, with their stored data.
     *
     * @param  string  $player  UUID or name
     * @return array{uuid: string, name: string, first_login: int, last_login: int, online: bool, data: list<array{key: string, type: string, value: mixed, readable: bool, editable: bool, size: int}>}
     */
    public function player(string $player): array
    {
        return $this->send('get', 'players/'.$this->segment($player))['data'];
    }

    /**
     * @param  string|null  $type  e.g. "int" or "string"; null keeps the type of the current value
     */
    public function setPlayerData(string $player, string $key, ?string $type, string $value): array
    {
        return $this->send('put', 'players/'.$this->segment($player).'/data/'.$this->segment($key),
            array_filter(['type' => $type, 'value' => $value], fn ($v) => $v !== null))['data'];
    }

    public function removePlayerData(string $player, string $key): void
    {
        $this->send('delete', 'players/'.$this->segment($player).'/data/'.$this->segment($key));
    }

    // Wrappers

    public function wrappers(): array
    {
        return $this->send('get', 'wrappers');
    }

    /**
     * @return array{name: string, address: string, key: string}
     */
    public function addWrapper(string $address, ?string $key = null): array
    {
        return $this->send('post', 'wrappers', array_filter(['address' => $address, 'key' => $key]))['data'];
    }

    public function stopWrapper(string $name): array
    {
        return $this->send('post', 'wrappers/'.$this->segment($name).'/stop');
    }

    // Modules

    public function modules(): array
    {
        return $this->send('get', 'modules');
    }

    public function loadModule(string $file): void
    {
        $this->send('post', 'modules/load', ['file' => $file]);
    }

    public function reloadModule(string $name): void
    {
        $this->send('post', 'modules/'.$this->segment($name).'/reload');
    }

    public function disableModule(string $name): void
    {
        $this->send('post', 'modules/'.$this->segment($name).'/disable');
    }

    // Panel users

    public function users(): array
    {
        return $this->send('get', 'users');
    }

    /**
     * @param  list<string>  $permissions
     */
    public function createUser(string $username, string $password, array $permissions = []): array
    {
        return $this->send('post', 'users', [
            'username' => $username,
            'password' => $password,
            'permissions' => array_values($permissions),
        ]);
    }

    public function deleteUser(string $username): void
    {
        $this->send('delete', 'users/'.$this->segment($username));
    }

    /**
     * @param  list<string>  $permissions
     */
    public function setPermissions(string $username, array $permissions): array
    {
        return $this->send('put', 'users/'.$this->segment($username).'/permissions', [
            'permissions' => array_values($permissions),
        ]);
    }

    /**
     * URL the browser connects to for the live console of a server.
     */
    public function consoleSocketUrl(string $name): string
    {
        $base = preg_replace('#^http#i', 'ws', $this->baseUrl);

        return $base.$this->consoleTicket($name)['path'];
    }

    private function request(): PendingRequest
    {
        $request = Http::baseUrl($this->baseUrl.'/api/v2')
            ->acceptJson()
            ->asJson()
            ->timeout(config('rexlcloud.timeout'));

        return $this->token ? $request->withToken($this->token) : $request;
    }

    private function send(string $method, string $path, array $data = []): mixed
    {
        return $this->decode($this->raw(fn () => match (true) {
            $method === 'get' => $this->request()->get($path, $data),
            // An empty array would be sent as "[]", which the API rejects as body
            $data === [] => $this->request()->send(strtoupper($method), $path),
            default => $this->request()->{$method}($path, $data),
        }));
    }

    /**
     * Runs a request; errors become exceptions.
     *
     * @param  callable(): Response  $call
     */
    private function raw(callable $call): Response
    {
        try {
            $response = $call();
        } catch (ConnectionException $e) {
            throw new ApiUnavailableException('The cloud is not reachable right now.', 0, 'unavailable');
        }

        if ($response->status() === 401) {
            throw new UnauthenticatedException(
                $response->json('message') ?? 'Please log in again.', 401, $response->json('error'));
        }

        if ($response->failed()) {
            throw new ApiException(
                $response->json('message') ?? 'The cloud answered with HTTP '.$response->status().'.',
                $response->status(),
                $response->json('error'),
            );
        }

        return $response;
    }

    private function decode(Response $response): mixed
    {
        return $response->json();
    }

    private function segment(string $value): string
    {
        return rawurlencode($value);
    }
}
