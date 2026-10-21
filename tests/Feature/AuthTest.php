<?php

namespace Tests\Feature;

use App\Http\Controllers\AuthController;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\FakesCloud;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use FakesCloud;

    public function test_guests_are_redirected_to_the_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/servers')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Log in with your panel account');
    }

    public function test_login_stores_the_token_and_redirects_to_the_dashboard(): void
    {
        $this->fakeCloud([
            self::API.'/auth/login' => Http::response([
                'token' => 'new-token', 'expires_at' => 0,
                'user' => ['username' => 'admin', 'permissions' => ['SEE_ALL_SERVERS']],
            ]),
        ]);

        $this->post('/login', ['api_url' => 'http://cloud.test/api/v2/', 'username' => 'admin', 'password' => 'secret123'])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('cloud.api_url', 'http://cloud.test')
            ->assertSessionHas('cloud.token', 'new-token')
            ->assertSessionHas('cloud.permissions', ['SEE_ALL_SERVERS'])
            ->assertCookie(AuthController::API_URL_COOKIE, 'http://cloud.test');

        Http::assertSent(fn (Request $request) => $request->url() === self::API.'/auth/login'
            && $request['username'] === 'admin' && $request['password'] === 'secret123');
    }

    public function test_wrong_credentials_show_an_error(): void
    {
        $this->fakeCloud([
            self::API.'/auth/login' => Http::response(['error' => 'unauthorized', 'message' => 'Wrong username or password'], 401),
        ]);

        $this->from('/login')->post('/login', ['api_url' => 'http://cloud.test', 'username' => 'admin', 'password' => 'nope'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['username' => 'Wrong username or password.'])
            ->assertSessionMissing('cloud.token');
    }

    public function test_logins_are_rate_limited(): void
    {
        $this->fakeCloud([
            self::API.'/auth/login' => Http::response(['error' => 'unauthorized', 'message' => 'Wrong'], 401),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['api_url' => 'http://cloud.test', 'username' => 'admin', 'password' => 'nope']);
        }

        $this->post('/login', ['api_url' => 'http://cloud.test', 'username' => 'admin', 'password' => 'nope'])
            ->assertSessionHasErrors(['username' => __('Too many login attempts. Try again in 60 seconds.')]);
        Http::assertSentCount(5);
    }

    public function test_an_unreachable_cloud_is_reported_on_login(): void
    {
        Http::fake([self::API.'/*' => Http::failedConnection()]);

        $this->post('/login', ['api_url' => 'http://cloud.test', 'username' => 'admin', 'password' => 'secret123'])
            ->assertSessionHasErrors(['api_url' => 'No cloud answered at this URL.']);
    }

    public function test_the_cloud_url_must_be_an_http_url(): void
    {
        Http::fake();

        $this->post('/login', ['api_url' => 'file:///etc/passwd', 'username' => 'admin', 'password' => 'secret123'])
            ->assertSessionHasErrors('api_url');
        Http::assertNothingSent();
    }

    public function test_the_remembered_cloud_url_fills_the_login_form(): void
    {
        $this->withCookie(AuthController::API_URL_COOKIE, 'http://10.0.0.10:4343')
            ->get('/login')
            ->assertOk()
            ->assertSee('value="http://10.0.0.10:4343"', false);
    }

    public function test_logout_ends_the_api_session(): void
    {
        $this->fakeCloud();
        $this->loginAs();

        $this->post('/logout')->assertRedirect('/login')->assertSessionMissing('cloud.token');

        Http::assertSent(fn (Request $request) => $request->url() === self::API.'/auth/logout'
            && $request->hasHeader('Authorization', 'Bearer test-token'));
    }

    public function test_an_expired_token_redirects_to_the_login(): void
    {
        $this->fakeCloud([
            self::API.'/auth/me' => Http::response(['error' => 'unauthorized', 'message' => 'expired'], 401),
        ]);
        $this->loginAs();

        $this->get('/servers')
            ->assertRedirect('/login')
            ->assertSessionHas('status')
            ->assertSessionMissing('cloud.token');
    }

    public function test_an_unreachable_cloud_shows_an_error_page(): void
    {
        Http::fake([self::API.'/*' => Http::failedConnection()]);
        $this->loginAs();

        $this->get('/')->assertStatus(503)->assertSee('The cloud is not reachable');
    }

    public function test_permissions_are_refreshed_on_page_loads(): void
    {
        $this->fakeCloud(permissions: ['SEE_ALL_SERVERS']);
        $this->loginAs(permissions: self::ALL_PERMISSIONS);

        $this->get('/')->assertOk();

        $this->assertSame(['SEE_ALL_SERVERS'], session('cloud.permissions'));
    }
}
