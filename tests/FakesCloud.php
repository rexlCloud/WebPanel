<?php

namespace Tests;

use Illuminate\Support\Facades\Http;

/**
 * Fakes the RestModule API with a small, consistent data set.
 */
trait FakesCloud
{
    protected const API = 'http://cloud.test/api/v2';

    protected const ALL_PERMISSIONS = [
        'CREATE_USER', 'DELETE_USER', 'CHANGE_USER_PERMISSIONS', 'SEE_ALL_SERVERS', 'SEE_ALL_USERS',
        'SEE_ALL_GROUPS', 'START_SERVER', 'STOP_SERVER', 'EXECUTE_ON_SERVER', 'SEE_SERVER_CONSOLE',
        'MANAGE_GROUPS', 'SEE_PLAYERS', 'MANAGE_PLAYERS', 'SEE_WRAPPERS', 'MANAGE_WRAPPERS',
        'MANAGE_MAINTENANCE', 'MANAGE_MODULES', 'MANAGE_TEMPLATES',
    ];

    /**
     * @param  list<string>  $permissions
     */
    protected function loginAs(string $username = 'admin', ?array $permissions = null): void
    {
        session([
            'cloud.api_url' => 'http://cloud.test',
            'cloud.token' => 'test-token',
            'cloud.username' => $username,
            'cloud.permissions' => $permissions ?? self::ALL_PERMISSIONS,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides  extra or replacing fakes, keyed by URL pattern
     */
    protected function fakeCloud(array $overrides = [], ?array $permissions = null): void
    {
        $permissions ??= self::ALL_PERMISSIONS;

        Http::fake($overrides + [
            self::API.'/auth/me' => Http::response(['username' => 'admin', 'permissions' => $permissions]),
            self::API.'/overview' => Http::response([
                'servers_total' => 3, 'servers_online' => 2, 'proxies_online' => 1, 'players_online' => 7,
                'groups' => 2, 'wrappers_registered' => 2, 'wrappers_connected' => 1, 'maintenance' => false,
                'version' => '2.0.0',
            ]),
            self::API.'/stats*' => Http::response([
                ['time' => 1_760_000_060_000, 'cpu_usage' => 0.25, 'used_memory' => 4096, 'free_memory' => 4096,
                    'online_servers' => 2, 'started_servers' => 3, 'online_players' => 7],
                ['time' => 1_760_000_000_000, 'cpu_usage' => 0.2, 'used_memory' => 2048, 'free_memory' => 6144,
                    'online_servers' => 1, 'started_servers' => 2, 'online_players' => 3],
            ]),
            self::API.'/servers' => Http::response([$this->server('Lobby-1'), $this->server('Proxy-1', 'Proxy', 'PROXY')]),
            self::API.'/servers/Lobby-1' => Http::response($this->server('Lobby-1')),
            self::API.'/servers/*/console' => Http::response(['lines' => ["\e[32mHello\e[0m <b>world</b>", null]]),
            self::API.'/servers/*/console/ticket' => Http::response(['ticket' => 'abc', 'expires_in' => 30,
                'path' => '/api/v2/servers/Lobby-1/console/ws?ticket=abc']),
            self::API.'/servers/*' => Http::response(['status' => 'accepted'], 202),
            self::API.'/groups' => Http::response([$this->group('Lobby'), $this->group('Proxy', 'PROXY')]),
            self::API.'/groups/Lobby' => Http::response($this->group('Lobby')),
            self::API.'/groups/*' => Http::response(['status' => 'accepted'], 202),
            self::API.'/server-jars' => Http::response([
                ['id' => 'CUSTOM', 'name' => 'CUSTOM', 'minecraft_version' => null, 'version' => null, 'type' => 'SERVER', 'built' => false, 'custom' => true],
                ['id' => 'PAPER_26_3', 'name' => 'Paper', 'minecraft_version' => '26.3', 'version' => null, 'type' => 'SERVER', 'built' => false],
                ['id' => 'SPIGOT_26_3', 'name' => 'Spigot', 'minecraft_version' => '26.3', 'version' => null, 'type' => 'SERVER', 'built' => true],
                ['id' => 'BUNGEECORD_2104', 'name' => 'BungeeCord', 'minecraft_version' => null, 'version' => '2104', 'type' => 'PROXY'],
                ['id' => 'VELOCITY_3_5_0', 'name' => 'Velocity', 'minecraft_version' => null, 'version' => '3.5.0', 'type' => 'PROXY'],
            ]),
            self::API.'/java-runtimes' => Http::response([
                ['path' => '/opt/java/jdk-25/bin/java', 'version' => '25.0.4', 'major' => 25, 'vendor' => 'Eclipse Adoptium',
                    'name' => 'Java 25.0.4 (Eclipse Adoptium)', 'wrappers' => ['Wrapper-1']],
                ['path' => '/usr/lib/jvm/java-21-openjdk-amd64/bin/java', 'version' => '21.0.8', 'major' => 21, 'vendor' => 'Debian',
                    'name' => 'Java 21.0.8 (Debian)', 'wrappers' => ['Wrapper-1', 'Wrapper-2']],
            ]),
            self::API.'/motd/preview' => Http::response(['component' => [
                'text' => '', 'extra' => [['text' => 'Hello', 'color' => 'gold'], ['text' => "
World", 'color' => '#55ff55', 'bold' => true]],
            ]]),
            self::API.'/motd' => Http::response(['motd' => '<gold>Hello<newline><green>World', 'maintenance_motd' => '<red>Maintenance', 'maintenance' => false]),
            self::API.'/templates' => Http::response([
                ['group' => 'Lobby', 'variant' => 'default', 'type' => 'SERVER'],
                ['group' => 'Lobby', 'variant' => 'map-desert', 'type' => 'SERVER'],
                ['group' => 'global', 'variant' => 'server', 'type' => 'SERVER'],
            ]),
            self::API.'/templates/*/files*' => Http::response([
                ['name' => 'plugins', 'directory' => true, 'size' => 0, 'modified' => 1_760_000_000_000],
                ['name' => 'server.properties', 'directory' => false, 'size' => 10, 'modified' => 1_760_000_000_000],
            ]),
            self::API.'/templates/*/file*' => fn ($request) => $request->method() === 'GET'
                ? Http::response('motd=Hello', 200, ['Content-Length' => '10'])
                : Http::response(['status' => 'ok', 'message' => 'Saved']),
            self::API.'/templates/*' => Http::response(['status' => 'ok']),
            self::API.'/players' => Http::response([[
                'uuid' => '069a79f4-44e9-4726-a5be-fca90e38aaf5', 'name' => 'Notch', 'server' => 'Lobby-1',
                'server_group' => 'Lobby', 'proxy' => 'Proxy-1', 'login' => 1_760_000_000_000,
            ]]),
            self::API.'/players/*' => Http::response(['status' => 'ok']),
            self::API.'/wrappers' => Http::response([
                ['name' => 'Wrapper-1', 'address' => '10.0.0.11', 'connected' => true, 'in_shutdown' => false,
                    'cpu_usage' => 0.4, 'used_memory' => 8_000_000_000, 'free_memory' => 8_000_000_000,
                    'total_memory' => 16_000_000_000, 'servers' => ['Lobby-1']],
                ['name' => 'Wrapper-2', 'address' => '10.0.0.12', 'connected' => false, 'servers' => []],
            ]),
            self::API.'/wrappers*' => Http::response(['status' => 'ok']),
            self::API.'/maintenance' => Http::response(['enabled' => false]),
            self::API.'/modules' => Http::response([
                'loaded' => [['name' => 'RestModule', 'version' => '2.0.0', 'author' => 'rexlNico', 'main_class' => 'de.rexlcloud.restmodule.RestModule']],
                'files' => ['RestModule-2.0.0.jar', 'SignModule.jar'],
                'self' => 'RestModule',
            ]),
            self::API.'/modules/*' => Http::response(['status' => 'ok']),
            self::API.'/users' => Http::response([
                ['username' => 'admin', 'permissions' => $permissions],
                ['username' => 'moderator', 'permissions' => ['SEE_ALL_SERVERS']],
            ]),
            self::API.'/users/*' => Http::response(['status' => 'ok']),
            self::API.'/permissions' => Http::response(self::ALL_PERMISSIONS),
            self::API.'/auth/*' => Http::response(['status' => 'ok']),
        ]);
    }

    protected function server(string $name, string $group = 'Lobby', string $type = 'SERVER'): array
    {
        return [
            'server_name' => $name, 'server_port' => 25700, 'server_state' => '', 'wrapper' => 'Wrapper-1',
            'server_group' => $group, 'server_type' => $type, 'online_players' => 1, 'max_players' => 50,
            'memory' => 1024, 'static' => false, 'online_state' => 'online', 'player_names' => ['Notch'],
        ];
    }

    protected function group(string $name, string $type = 'SERVER'): array
    {
        return [
            'group_name' => $name, 'group_type' => $type, 'group_memory' => 1024, 'group_java_args' => '',
            'group_java_cmd' => 'java', 'group_map_players' => 50, 'group_static' => false,
            'group_fallback' => $name === 'Lobby', 'group_remove_old_logs' => false, 'group_wrapper' => null,
            'group_min_servers' => 1, 'servers' => [$name.'-1'], 'online_players' => 1,
        ];
    }
}
