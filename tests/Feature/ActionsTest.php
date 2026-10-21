<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Livewire\GroupDetail;
use App\Livewire\GroupList;
use App\Livewire\Modules;
use App\Livewire\Players;
use App\Livewire\ServerDetail;
use App\Livewire\ServerList;
use App\Livewire\ServerListSettings;
use App\Support\ChatComponent;
use App\Livewire\Users;
use App\Livewire\Wrappers;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\FakesCloud;
use Tests\TestCase;

class ActionsTest extends TestCase
{
    use FakesCloud;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loginAs();
    }

    private function assertApiCall(string $method, string $path, ?array $body = null): void
    {
        Http::assertSent(function (Request $request) use ($method, $path, $body) {
            return $request->method() === $method
                && $request->url() === self::API.$path
                && $request->hasHeader('Authorization', 'Bearer test-token')
                && ($body === null || $request->data() === $body);
        });
    }

    public function test_server_actions_call_the_api(): void
    {
        $this->fakeCloud();

        Livewire::test(ServerList::class)
            ->call('restart', 'Lobby-1')
            ->call('stop', 'Lobby-1')
            ->call('kill', 'Lobby-1')
            ->assertDispatched('notify', message: 'Killing Lobby-1.', type: 'success');

        $this->assertApiCall('POST', '/servers/Lobby-1/restart');
        $this->assertApiCall('POST', '/servers/Lobby-1/stop');
        $this->assertApiCall('POST', '/servers/Lobby-1/kill');
    }

    public function test_api_errors_are_shown_as_notifications(): void
    {
        $this->fakeCloud([
            self::API.'/servers/Lobby-1/stop' => Http::response(['error' => 'forbidden', 'message' => 'Missing permission [STOP_SERVER]'], 403),
        ]);

        Livewire::test(ServerList::class)
            ->call('stop', 'Lobby-1')
            ->assertDispatched('notify', message: 'Missing permission [STOP_SERVER]', type: 'error');
    }

    public function test_server_list_filters(): void
    {
        $this->fakeCloud();

        Livewire::test(ServerList::class)
            ->assertSee('Proxy-1')
            ->set('group', 'Lobby')
            ->assertSee('Lobby-1')
            ->assertDontSee('Proxy-1')
            ->set('group', '')
            ->set('search', 'prox')
            ->assertSee('Proxy-1')
            ->assertDontSee('Lobby-1');
    }

    public function test_console_uses_a_websocket_ticket_and_sends_commands(): void
    {
        $this->fakeCloud();

        $component = Livewire::test(ServerDetail::class, ['name' => 'Lobby-1']);

        $this->assertSame(
            'ws://cloud.test/api/v2/servers/Lobby-1/console/ws?ticket=abc',
            $component->instance()->consoleSocketUrl(),
        );
        $this->assertSame(["\e[32mHello\e[0m <b>world</b>"], $component->instance()->consoleLines());

        $component->call('sendCommand', 'say Grüße $1');
        $this->assertApiCall('POST', '/servers/Lobby-1/command', ['command' => 'say Grüße $1']);
    }

    public function test_groups_are_created_with_the_form_values(): void
    {
        $this->fakeCloud();

        Livewire::test(GroupList::class)
            ->call('openCreate')
            ->assertSee('Paper 26.3')
            ->assertDontSee('Velocity 3.5.0')
            ->set('name', 'Sky Wars')
            ->call('create')
            ->assertHasErrors(['name', 'serverJar'])
            ->set('name', 'SkyWars')
            ->set('memory', 2048)
            ->set('minServers', 2)
            ->set('serverJar', 'PAPER_26_3')
            ->call('create')
            ->assertHasNoErrors()
            ->assertSet('creating', false);

        $this->assertApiCall('POST', '/groups', [
            'name' => 'SkyWars', 'type' => 'SERVER', 'memory' => 2048, 'static' => false, 'wrapper' => null,
            'min_servers' => 2, 'fallback' => false, 'server_jar' => 'PAPER_26_3',
        ]);
    }

    public function test_spigot_jars_explain_that_they_are_built_first(): void
    {
        $this->fakeCloud();

        Livewire::test(GroupList::class)
            ->call('openCreate')
            ->assertSee('Spigot 26.3 (built on first use)')
            ->assertDontSee('BuildTools')
            ->set('serverJar', 'SPIGOT_26_3')
            ->assertSee('BuildTools')
            ->set('name', 'Survival')
            ->call('create')
            ->assertHasNoErrors()
            ->assertDispatched('notify', type: 'success',
                message: 'The group Survival is being created. Spigot is built with BuildTools first, so it appears in a few minutes.');
    }

    public function test_bungeecord_explains_legacy_forwarding(): void
    {
        $this->fakeCloud();

        Livewire::test(GroupList::class)
            ->call('openCreate')
            ->set('type', 'PROXY')
            ->assertSee('BungeeCord 2104')
            ->assertDontSee('legacy forwarding')
            ->set('serverJar', 'BUNGEECORD_2104')
            ->assertSee('legacy forwarding');
    }

    public function test_custom_jars_explain_where_the_jar_goes(): void
    {
        $this->fakeCloud();

        Livewire::test(GroupList::class)
            ->call('openCreate')
            ->assertSee('Custom jar (your own server.jar, e.g. Minestom)')
            ->set('serverJar', 'CUSTOM')
            ->assertSee('MinestomBridge')
            ->set('name', 'Game')
            ->call('create')
            ->assertHasNoErrors()
            ->assertDispatched('notify', type: 'success',
                message: 'The group Game is being created. Put your server.jar into templates/Game/default/; its servers start once it is there.');
    }

    public function test_group_settings_start_and_delete(): void
    {
        $this->fakeCloud();

        Livewire::test(GroupDetail::class, ['name' => 'Lobby'])
            ->assertSet('memory', 1024)
            ->assertSet('fallback', true)
            ->set('memory', 1536)
            ->call('save')
            ->set('startAmount', 3)
            ->call('startServers')
            ->call('syncTemplate')
            ->call('delete')
            ->assertRedirect(route('groups'));

        $this->assertApiCall('PATCH', '/groups/Lobby', [
            'memory' => 1536, 'max_players' => 50, 'min_servers' => 1, 'fallback' => true, 'remove_old_logs' => false,
        ]);
        $this->assertApiCall('POST', '/groups/Lobby/start', ['amount' => 3]);
        $this->assertApiCall('POST', '/groups/Lobby/sync-template');
        $this->assertApiCall('DELETE', '/groups/Lobby');
    }

    public function test_the_java_runtime_of_a_group_can_be_chosen(): void
    {
        $this->fakeCloud();

        Livewire::test(GroupDetail::class, ['name' => 'Lobby'])
            ->assertSet('javaCommand', 'java')
            ->assertSee("Default: java on the wrapper's PATH", false)
            ->assertSee('Java 25.0.4 (Eclipse Adoptium) · Wrapper-1 · /opt/java/jdk-25/bin/java')
            ->assertSee('Java 21.0.8 (Debian) · Wrapper-1, Wrapper-2')
            ->set('javaCommand', '/opt/java/jdk-25/bin/java')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('savedJavaCommand', '/opt/java/jdk-25/bin/java');

        $this->assertApiCall('PATCH', '/groups/Lobby', [
            'memory' => 1024, 'max_players' => 50, 'min_servers' => 1, 'fallback' => true, 'remove_old_logs' => false,
            'java_command' => '/opt/java/jdk-25/bin/java',
        ]);
    }

    public function test_a_runtime_no_wrapper_reports_is_still_shown(): void
    {
        $group = $this->group('Lobby');
        $group['group_java_cmd'] = '/opt/old/jdk-17/bin/java';
        $this->fakeCloud([self::API.'/groups/Lobby' => Http::response($group)]);

        Livewire::test(GroupDetail::class, ['name' => 'Lobby'])
            ->assertSet('javaCommand', '/opt/old/jdk-17/bin/java')
            ->assertSee('/opt/old/jdk-17/bin/java (no connected wrapper reports it)')
            ->call('save');

        // Unchanged, so not sent: the master would reject a runtime no wrapper reports
        Http::assertSent(fn ($request) => $request->method() === 'PATCH' && ! isset($request['java_command']));
    }

    public function test_the_motd_is_shown_with_a_preview_and_saved(): void
    {
        $this->fakeCloud();

        Livewire::test(ServerListSettings::class)
            ->assertSet('motd', '<gold>Hello<newline><green>World')
            ->assertSet('maintenanceMotd', '<red>Maintenance')
            ->assertSee('<span style="color:#ffaa00">Hello</span>', false)
            ->assertSee('Shown now')
            ->set('motd', '<aqua>New MOTD')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertApiCall('PUT', '/motd', ['motd' => '<aqua>New MOTD', 'maintenance_motd' => '<red>Maintenance']);
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/motd/preview') && $request['text'] === '<aqua>New MOTD');
    }

    public function test_chat_components_are_rendered_safely(): void
    {
        $html = ChatComponent::toHtml(['text' => '<script>alert(1)</script>', 'color' => 'red', 'extra' => [
            ['text' => 'bold', 'bold' => true], ['text' => 'bad', 'color' => 'url(evil)'],
        ]]);

        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('font-weight:700', $html);
        $this->assertStringNotContainsString('url(evil)', $html);
        $this->assertSame('plain', ChatComponent::toHtml('plain'));
    }

    public function test_players_can_be_messaged_and_kicked(): void
    {
        $this->fakeCloud();
        $uuid = '069a79f4-44e9-4726-a5be-fca90e38aaf5';

        Livewire::test(Players::class)
            ->assertSee('Notch')
            ->call('open', $uuid, 'Notch', 'message')
            ->call('submit')
            ->assertHasErrors('text')
            ->set('text', '<green>Hi')
            ->call('submit')
            ->assertSet('selectedUuid', null)
            ->call('open', $uuid, 'Notch', 'kick')
            ->call('submit');

        $this->assertApiCall('POST', "/players/{$uuid}/message", ['message' => '<green>Hi']);
        $this->assertApiCall('POST', "/players/{$uuid}/kick");
    }

    public function test_wrappers_can_be_registered_and_show_the_key_once(): void
    {
        $this->fakeCloud([
            self::API.'/wrappers' => function (Request $request) {
                return $request->method() === 'POST'
                    ? Http::response(['status' => 'ok', 'data' => ['name' => 'Wrapper-3', 'address' => '10.0.0.13', 'key' => 'f1c724d7-9f6a-4739-907c-85c8669cfad3']], 201)
                    : Http::response([]);
            },
        ]);

        Livewire::test(Wrappers::class)
            ->set('adding', true)
            ->set('address', '10.0.0.13')
            ->set('key', 'not-a-uuid')
            ->call('register')
            ->assertHasErrors('key')
            ->set('key', '')
            ->call('register')
            ->assertSet('adding', false)
            ->assertSee('f1c724d7-9f6a-4739-907c-85c8669cfad3');

        $this->assertApiCall('POST', '/wrappers', ['address' => '10.0.0.13']);
    }

    public function test_modules_and_maintenance(): void
    {
        $this->fakeCloud();

        Livewire::test(Modules::class)
            ->assertSee('serves this panel')
            ->call('loadModule', 'SignModule.jar')
            ->call('reloadModule', 'RestModule');
        Livewire::test(Dashboard::class)->call('toggleMaintenance', true);

        $this->assertApiCall('POST', '/modules/load', ['file' => 'SignModule.jar']);
        $this->assertApiCall('POST', '/modules/RestModule/reload');
        $this->assertApiCall('PUT', '/maintenance', ['enabled' => true]);
    }

    public function test_users_are_managed(): void
    {
        $this->fakeCloud();

        Livewire::test(Users::class)
            ->call('openCreate')
            ->set('username', 'helper')
            ->set('password', 'short')
            ->call('create')
            ->assertHasErrors('password')
            ->set('password', 'long-enough')
            ->set('newPermissions', ['SEE_PLAYERS'])
            ->call('create')
            ->assertSet('creating', false)
            ->call('edit', 'moderator', ['SEE_ALL_SERVERS'])
            ->set('editPermissions', ['SEE_ALL_SERVERS', 'SEE_PLAYERS'])
            ->call('savePermissions')
            ->assertSet('editing', null)
            ->call('delete', 'moderator');

        $this->assertApiCall('POST', '/users', ['username' => 'helper', 'password' => 'long-enough', 'permissions' => ['SEE_PLAYERS']]);
        $this->assertApiCall('PUT', '/users/moderator/permissions', ['permissions' => ['SEE_ALL_SERVERS', 'SEE_PLAYERS']]);
        $this->assertApiCall('DELETE', '/users/moderator');
    }

    public function test_an_expired_session_during_an_action_redirects_to_the_login(): void
    {
        $this->fakeCloud([
            self::API.'/servers/Lobby-1/stop' => Http::response(['error' => 'unauthorized', 'message' => 'expired'], 401),
        ]);

        Livewire::test(ServerList::class)
            ->call('stop', 'Lobby-1')
            ->assertRedirect(route('login'));

        $this->assertNull(session('cloud.token'));
    }
}
