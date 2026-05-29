<?php

use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Livewire\FollowUps\Index as FollowUpsIndex;
use App\Livewire\Leads\Index as LeadsIndex;
use App\Livewire\Opportunities\Index as OpportunitiesIndex;
use App\Livewire\Tasks\Index as TasksIndex;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', DashboardIndex::class)->name('dashboard');

    Route::livewire('leads', LeadsIndex::class)->name('leads.index');

    Route::livewire('opportunities', OpportunitiesIndex::class)->name('opportunities.index');

    Route::livewire('follow-ups', FollowUpsIndex::class)->name('follow-ups.index');

    Route::livewire('tasks', TasksIndex::class)->name('tasks.index');
});

require __DIR__.'/settings.php';
