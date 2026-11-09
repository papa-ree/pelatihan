<?php

namespace Bale\Pelatihan\Commands;

use Bale\Pelatihan\PelatihanPermissions;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

class InstallPelatihan extends Command
{
    protected $signature = 'pelatihan:install {--tenant= : Slug tenant untuk langkah migration}';

    protected $description = 'Installer bale/pelatihan (config, permission, migration, settings)';

    public function handle(): int
    {
        if (! config('pelatihan.data_key')) {
            $this->error('PELATIHAN_DATA_KEY belum diisi di .env. Nilai harus sama dengan bale-front.');

            return self::FAILURE;
        }

        $option = $this->choice(
            'What would you like to install/run?',
            [
                0 => 'All (Config, Migrations, Permissions & Settings)',
                1 => 'Publish Config Only',
                2 => 'Permissions Only',
                3 => 'Migrations Only',
            ],
            0
        );

        try {
            if (in_array($option, ['All (Config, Migrations, Permissions & Settings)', 'Publish Config Only', 0, 1], true)) {
                $this->call('pelatihan:publish-config', ['--force' => true]);
            }

            if (in_array($option, ['All (Config, Migrations, Permissions & Settings)', 'Permissions Only', 0, 2], true)) {
                $this->seedPermissions();
            }

            if (in_array($option, ['All (Config, Migrations, Permissions & Settings)', 'Migrations Only', 0, 3], true)) {
                $this->call('pelatihan:migrate', ['--tenant' => $this->option('tenant')]);
            }

            $this->info('  Installation step(s) completed successfully.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('  [ERROR] Installation failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    protected function seedPermissions(): void
    {
        foreach (PelatihanPermissions::ALL as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission],
                ['guard_name' => 'web']
            );
        }

        $rootRole = Role::where('name', 'root')->first();

        if ($rootRole) {
            $rootRole->givePermissionTo(PelatihanPermissions::ALL);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $adminRole = Role::firstOrCreate(
            ['name' => 'bale-pelatihan-admin'],
            ['guard_name' => 'web'],
        );
        $adminRole->syncPermissions(PelatihanPermissions::ALL);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info('Permissions seeded.');
    }
}
