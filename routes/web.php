<?php

use App\Livewire\Leads\Index as LeadsIndex;
use App\Livewire\Opportunities\Index as OpportunitiesIndex;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('leads', LeadsIndex::class)->name('leads.index');

    Route::livewire('opportunities', OpportunitiesIndex::class)->name('opportunities.index');

    Route::view('follow-ups', 'pages.crm.stub', [
        'title' => 'Follow-ups',
        'heading' => 'Follow-ups',
        'message' => 'Follow-up management arrives in wave 2 (FDR-006).',
    ])->name('follow-ups.index');

    Route::view('tasks', 'pages.crm.stub', [
        'title' => 'Tasks',
        'heading' => 'Tasks',
        'message' => 'Task management arrives in wave 3 (FDR-007).',
    ])->name('tasks.index');
});

require __DIR__.'/settings.php';
