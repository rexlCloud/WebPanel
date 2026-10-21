<?php

namespace App\Livewire;

use App\Services\RexlCloud\ApiException;
use App\Services\RexlCloud\RexlCloudClient;
use App\Services\RexlCloud\UnauthenticatedException;
use App\Support\CloudUser;
use Livewire\Component;

/**
 * Base for pages that talk to the RestModule. API errors are shown as notifications
 * instead of breaking the page; an expired login redirects to the login page.
 */
abstract class CloudComponent extends Component
{
    /**
     * Error from the last attempt to load the page data, shown above the content.
     */
    public ?string $loadError = null;

    protected function cloud(): RexlCloudClient
    {
        return RexlCloudClient::fromSession();
    }

    protected function user(): CloudUser
    {
        return CloudUser::current() ?? new CloudUser('', []);
    }

    protected function can(string ...$permissions): bool
    {
        return $this->user()->can(...$permissions);
    }

    /**
     * Loads data for rendering; returns the default when the API fails.
     *
     * @template T
     *
     * @param  callable(RexlCloudClient): T  $callback
     * @param  T  $default
     * @return T
     */
    protected function load(callable $callback, mixed $default = []): mixed
    {
        try {
            $result = $callback($this->cloud());
            $this->loadError = null;

            return $result;
        } catch (UnauthenticatedException) {
            $this->expireSession();
        } catch (ApiException $e) {
            $this->loadError = $e->getMessage();
        }

        return $default;
    }

    /**
     * Runs a state-changing call and reports the outcome as notification.
     *
     * @param  callable(RexlCloudClient): mixed  $callback
     */
    protected function attempt(callable $callback, ?string $success = null): bool
    {
        try {
            $callback($this->cloud());
            if ($success !== null) {
                $this->notify($success);
            }

            return true;
        } catch (UnauthenticatedException) {
            $this->expireSession();
        } catch (ApiException $e) {
            $this->notify($e->getMessage(), 'error');
        }

        return false;
    }

    protected function notify(string $message, string $type = 'success'): void
    {
        $this->dispatch('notify', message: $message, type: $type);
    }

    private function expireSession(): void
    {
        CloudUser::forget();
        session()->flash('status', 'Your session has expired, please log in again.');
        $this->redirectRoute('login');
    }
}
