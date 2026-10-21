<?php

use App\Http\Controllers\AuthController;
use App\Livewire\Account;
use App\Livewire\Dashboard;
use App\Livewire\GroupDetail;
use App\Livewire\GroupList;
use App\Livewire\Modules;
use App\Livewire\Players;
use App\Livewire\ServerDetail;
use App\Livewire\ServerList;
use App\Http\Controllers\TemplateDownloadController;
use App\Livewire\ServerListSettings;
use App\Livewire\Templates;
use App\Livewire\Users;
use App\Livewire\Wrappers;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('cloud.auth')->group(function () {
    Route::livewire('/', Dashboard::class)->name('dashboard');
    Route::livewire('/servers', ServerList::class)->name('servers');
    Route::livewire('/servers/{name}', ServerDetail::class)->name('servers.show');
    Route::livewire('/groups', GroupList::class)->name('groups');
    Route::livewire('/groups/{name}', GroupDetail::class)->name('groups.show');
    Route::livewire('/players', Players::class)->name('players');
    Route::livewire('/wrappers', Wrappers::class)->name('wrappers');
    Route::livewire('/server-list', ServerListSettings::class)->name('server-list');
    Route::livewire('/templates', Templates::class)->name('templates');
    Route::get('/templates/download', TemplateDownloadController::class)->name('templates.download');
    Route::livewire('/modules', Modules::class)->name('modules');
    Route::livewire('/users', Users::class)->name('users');
    Route::livewire('/account', Account::class)->name('account');
});
