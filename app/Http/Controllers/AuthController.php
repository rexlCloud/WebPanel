<?php

namespace App\Http\Controllers;

use App\Services\RexlCloud\ApiException;
use App\Services\RexlCloud\ApiUnavailableException;
use App\Services\RexlCloud\RexlCloudClient;
use App\Support\CloudUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Cookie that remembers the cloud URL for the login form.
     */
    public const API_URL_COOKIE = 'rexlcloud_api_url';

    public function showLogin(Request $request): View|RedirectResponse
    {
        if (CloudUser::current() !== null) {
            return redirect()->route('dashboard');
        }

        return view('auth.login', ['apiUrl' => old('api_url', $request->cookie(self::API_URL_COOKIE))]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'api_url' => ['required', 'string', 'max:255', 'url:http,https'],
            'username' => ['required', 'string', 'max:64'],
            'password' => ['required', 'string', 'max:256'],
        ]);

        $throttleKey = Str::lower($credentials['username']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'username' => 'Too many login attempts. Try again in '.RateLimiter::availableIn($throttleKey).' seconds.',
            ]);
        }

        $client = new RexlCloudClient($credentials['api_url']);
        try {
            $login = $client->login($credentials['username'], $credentials['password']);
        } catch (ApiUnavailableException) {
            throw ValidationException::withMessages(['api_url' => 'No cloud answered at this URL.']);
        } catch (ApiException $e) {
            RateLimiter::hit($throttleKey, 60);
            throw ValidationException::withMessages([
                'username' => $e->status === 429 ? $e->getMessage() : 'Wrong username or password.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();
        CloudUser::store($client->baseUrl(), $login['token'], $login['user']);
        Cookie::queue(self::API_URL_COOKIE, $client->baseUrl(), 60 * 24 * 365);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        try {
            RexlCloudClient::fromSession()->logout();
        } catch (ApiException) {
            // The token may already be expired; the local session is cleared anyway
        }

        CloudUser::forget();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
