<?php

namespace Bale\Pelatihan\Commands;

use Illuminate\Console\Command;

class PublishMigrationsPelatihan extends Command
{
    protected $signature = 'pelatihan:publish-migrations {--force : Timpa file migrasi yang sudah dipublikasikan}';

    protected $description = 'Publish migration files for bale/pelatihan';

    public function handle(): int
    {
        $this->call('vendor:publish', [
            '--tag' => 'pelatihan:migrations',
            '--force' => (bool) $this->option('force'),
        ]);

        $count = count(glob(database_path('migrations/pelatihan/*.php')) ?: []);

        $this->info("Published {$count} migration file(s) to database/migrations/pelatihan.");

        return self::SUCCESS;
    }
}
