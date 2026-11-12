<?php

use Bale\Cms\Middleware\EnsureBaleSelected;
use Bale\Cms\Middleware\SwitchBaleConnection;
use Bale\Pelatihan\Http\Controllers\AnalysisStatusController;
use Bale\Pelatihan\Http\Controllers\PeriodExportController;
use Bale\Pelatihan\Livewire\Pages\Citizen\Index as CitizenIndex;
use Bale\Pelatihan\Livewire\Pages\Cluster\Index as ClusterIndex;
use Bale\Pelatihan\Livewire\Pages\Overview\Index as OverviewIndex;
use Bale\Pelatihan\Livewire\Pages\Period\Form as PeriodForm;
use Bale\Pelatihan\Livewire\Pages\Period\Index as PeriodIndex;
use Bale\Pelatihan\Livewire\Pages\Program\Form as ProgramForm;
use Bale\Pelatihan\Livewire\Pages\Program\Index as ProgramIndex;
use Bale\Pelatihan\Livewire\Pages\Setting\Form as SettingForm;
use Bale\Pelatihan\Livewire\Pages\Submission\Detail as SubmissionDetail;
use Bale\Pelatihan\Livewire\Pages\Submission\Index as SubmissionIndex;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Pelatihan Routes
|--------------------------------------------------------------------------
|
| Diintegrasikan ke prefix /cms agar sesuai navigasi sidebar Bale CMS.
| Middleware tenant memastikan koneksi database sesuai bale yang aktif.
|
*/

Route::middleware(['web', 'auth'])->prefix('cms/pelatihan')->name('pelatihan.')->group(function () {
    Route::middleware([EnsureBaleSelected::class, SwitchBaleConnection::class])->group(function () {
        Route::get('/overview', OverviewIndex::class)->name('overview');

        Route::get('/analysis-status', AnalysisStatusController::class)
            ->middleware('can:pelatihan.read')
            ->name('analysis.status');

        Route::prefix('periods')->name('period.')->group(function () {
            Route::get('/', PeriodIndex::class)->name('index');
            Route::get('/create', PeriodForm::class)->name('create');
            Route::get('/edit/{id}', PeriodForm::class)->name('edit');
            Route::get('/{period}/export/{format}', PeriodExportController::class)
                ->whereIn('format', ['xlsx', 'pdf'])
                ->middleware('can:pelatihan.period.view')
                ->name('export');
        });

        Route::prefix('submissions')->name('submission.')->group(function () {
            Route::get('/', SubmissionIndex::class)->name('index');
            Route::get('/{id}', SubmissionDetail::class)->name('show');
        });

        Route::get('/citizens', CitizenIndex::class)->name('citizen.index');
        Route::get('/clusters', ClusterIndex::class)->name('cluster.index');

        Route::prefix('programs')->name('program.')->group(function () {
            Route::get('/', ProgramIndex::class)->name('index');
            Route::get('/create', ProgramForm::class)->name('create');
            Route::get('/edit/{id}', ProgramForm::class)->name('edit');
        });

        Route::get('/settings', SettingForm::class)->name('setting.index');
    });
});
