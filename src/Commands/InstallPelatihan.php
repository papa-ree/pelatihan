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
        $option = $this->choice(
            'What would you like to install/run?',
            [
                0 => 'All (Config, Migrations & Permissions)',
                1 => 'Publish Config Only',
                2 => 'Role & Permissions Only',
                3 => 'Migrations Only',
            ],
            0
        );

        $all = in_array($option, ['All (Config, Migrations & Permissions)', 0], true);

        try {
            if ($all || in_array($option, ['Publish Config Only', 1], true)) {
                $this->call('pelatihan:publish-config', ['--force' => true]);
            }

            if ($all || in_array($option, ['Role & Permissions Only', 2], true)) {
                $this->seedPermissions();
            }

            if ($all || in_array($option, ['Migrations Only', 3], true)) {
                if ($this->call('pelatihan:publish-migrations', ['--force' => true]) !== self::SUCCESS) {
                    return self::FAILURE;
                }

                if ($this->call('pelatihan:migrate', ['--tenant' => $this->option('tenant')]) !== self::SUCCESS) {
                    return self::FAILURE;
                }
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
