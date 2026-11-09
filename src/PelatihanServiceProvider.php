<?php

namespace Bale\Pelatihan;

use Bale\Pelatihan\Commands\AnalyzePeriod;
use Bale\Pelatihan\Commands\InstallPelatihan;
use Bale\Pelatihan\Commands\MigratePelatihan;
use Bale\Pelatihan\Commands\PublishConfigPelatihan;
use Bale\Pelatihan\Commands\PublishMigrationsPelatihan;
use Bale\Pelatihan\Listeners\MarkProgramNotificationFailed;
use Bale\Pelatihan\Listeners\MarkProgramNotificationSent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Component as LivewireComponent;
use Livewire\Livewire;
use Symfony\Component\Finder\Finder;

class PelatihanServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/pelatihan.php', 'pelatihan');

        $this->registerCommands();
    }

    protected function registerCommands(): void
    {
        $commands = [
            'command.pelatihan:install' => InstallPelatihan::class,
            'command.pelatihan:migrate' => MigratePelatihan::class,
            'command.pelatihan:publish-migrations' => PublishMigrationsPelatihan::class,
            'command.pelatihan:publish-config' => PublishConfigPelatihan::class,
            'command.pelatihan:analyze' => AnalyzePeriod::class,
        ];

        foreach ($commands as $key => $class) {
            $this->app->bind($key, $class);
        }

        $this->commands(array_keys($commands));
    }

    public function boot(): void
    {
        $this->app->booted(function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });

        $this->registerViews();
        $this->loadMigrations();
        $this->offerPublishing();
        $this->registerLivewire4Namespaces();
        $this->registerLivewireComponents();
        $this->registerSchedule();
        $this->registerEventListeners();
    }

    protected function registerEventListeners(): void
    {
        Event::listen(NotificationFailed::class, MarkProgramNotificationFailed::class);
        Event::listen(NotificationSent::class, MarkProgramNotificationSent::class);
    }

    /**
     * Scheduler harian memeriksa periode aktif yang ai_schedule-nya jatuh tempo.
     */
    protected function registerSchedule(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('pelatihan:analyze --due')
                ->dailyAt('01:00')
                ->withoutOverlapping();
        });
    }

    protected function registerLivewire4Namespaces(): void
    {
        Livewire::addNamespace(
            'pelatihan-pages',
            __DIR__.'/../resources/views/livewire/pages'
        );
    }

    protected function loadMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'pelatihan');
    }

    protected function offerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/pelatihan.php' => config_path('pelatihan.php'),
        ], 'pelatihan:config');

        $this->publishes($this->getMigrations(), 'pelatihan:migrations');
    }

    protected function getMigrations(): array
    {
        $migrations = [];
        $sourcePath = __DIR__.'/../database/migrations/';

        if (! is_dir($sourcePath)) {
            return $migrations;
        }

        foreach (glob($sourcePath.'*.{php,stub}', GLOB_BRACE) as $file) {
            $migrations[$file] = $this->getMigrationFileName(basename($file));
        }

        return $migrations;
    }

    protected function getMigrationFileName(string $filename): string
    {
        $migrationName = str_replace('.php.stub', '.php', $filename);
        $migrationName = str_replace('.stub', '', $migrationName);

        return database_path('migrations/pelatihan/'.$migrationName);
    }

    /**
     * Registrasi semua komponen Livewire di src/Livewire dengan alias
     * `pelatihan.` diikuti struktur folder (kebab-case).
     */
    protected function registerLivewireComponents(): void
    {
        $namespace = 'Bale\\Pelatihan\\Livewire';
        $basePath = __DIR__.'/Livewire';

        if (! is_dir($basePath)) {
            return;
        }

        $finder = new Finder;
        $finder->files()->in($basePath)->name('*.php');

        foreach ($finder as $file) {
            $relativePathname = $file->getRelativePathname();
            $nsPath = str_replace(['/', '\\'], '\\', $relativePathname);
            $class = $namespace.'\\'.Str::beforeLast($nsPath, '.php');

            if (! class_exists($class) || ! is_subclass_of($class, LivewireComponent::class)) {
                continue;
            }

            $withoutExt = Str::replaceLast('.php', '', $relativePathname);
            $segments = preg_split('#[\\/\\\\]#', $withoutExt);
            $kebab = array_map(fn ($s) => Str::kebab($s), $segments);

            Livewire::component('pelatihan.'.implode('.', $kebab), $class);
        }
    }
}
