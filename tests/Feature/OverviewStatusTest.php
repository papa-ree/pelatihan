<?php

use Bale\Pelatihan\Support\AnalysisState;
use Illuminate\Support\Facades\Route;

it('menandai analisis berjalan hanya selama job aktif', function () {
    AnalysisState::start('periode-1');

    expect(AnalysisState::startedAt('periode-1'))->not->toBeNull()
        ->and(AnalysisState::startedAt('periode-2'))->toBeNull();

    AnalysisState::finish('periode-1');

    expect(AnalysisState::startedAt('periode-1'))->toBeNull();
});

it('mendaftarkan endpoint status analisis di grup tenant', function () {
    expect(Route::has('pelatihan.analysis.status'))->toBeTrue();
});

it('tidak memasang polling di halaman overview', function () {
    $view = file_get_contents(__DIR__.'/../../resources/views/livewire/pages/overview/index.blade.php');

    expect($view)->not->toContain('setInterval')
        ->not->toContain('wire:poll');
});
