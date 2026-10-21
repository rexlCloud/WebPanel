<?php

namespace App\Providers;

use App\Support\CloudUser;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Template uploads (e.g. a server.jar) can be large; PHP's limits are raised in docker/php.ini
        config([
            'livewire.temporary_file_upload.rules' => ['required', 'file', 'max:524288'],
            'livewire.temporary_file_upload.max_upload_time' => 30,
        ]);

        // Read from the session on every render, so Livewire updates see permission changes too
        View::composer('*', fn ($view) => $view->with('cloudUser', CloudUser::current()));
    }
}
