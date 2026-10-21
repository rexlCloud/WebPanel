<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\FakesCloud;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use FakesCloud;

    public static function pages(): array
    {
        return [
            'dashboard' => ['/', 'Players online'],
            'servers' => ['/servers', 'Lobby-1'],
            'server' => ['/servers/Lobby-1', 'Console'],
            'groups' => ['/groups', 'Lobby'],
            'group' => ['/groups/Lobby', 'Settings'],
            'players' => ['/players', 'Notch'],
            'wrappers' => ['/wrappers', 'Wrapper-1'],
            'modules' => ['/modules', 'SignModule.jar'],
            'server list' => ['/server-list', 'Maintenance MOTD'],
            'users' => ['/users', 'moderator'],
            'account' => ['/account', 'Change password'],
        ];
    }

    #[DataProvider('pages')]
    public function test_pages_render(string $url, string $text): void
    {
        $this->fakeCloud();
        $this->loginAs();

        $this->get($url)->assertOk()->assertSee($text);
    }

    public function test_unknown_servers_are_404(): void
    {
        $this->fakeCloud([
            self::API.'/servers/Nope-1' => Http::response(['error' => 'not_found', 'message' => 'Server Nope-1 does not exist'], 404),
        ]);
        $this->loginAs();

        $this->get('/servers/Nope-1')->assertNotFound();
    }

    public function test_navigation_and_actions_follow_the_permissions(): void
    {
        $permissions = ['SEE_ALL_SERVERS', 'SEE_SERVER_CONSOLE'];
        $this->fakeCloud(permissions: $permissions);
        $this->loginAs(permissions: $permissions);

        $this->get('/servers')
            ->assertOk()
            ->assertSee('Lobby-1')
            ->assertDontSee('title="Kill"', false)
            ->assertDontSee(route('users'))
            ->assertDontSee(route('wrappers'));

        $this->get('/')->assertDontSee('Enable maintenance');
    }

    public function test_console_output_is_not_rendered_as_html_on_the_server(): void
    {
        $this->fakeCloud();
        $this->loginAs();

        // Console lines are only rendered by the browser (escaped there); the page must not embed them raw
        $this->get('/servers/Lobby-1')->assertOk()->assertDontSee('<b>world</b>', false);
    }
}
