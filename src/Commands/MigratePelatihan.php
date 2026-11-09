<?php

namespace Bale\Pelatihan\Commands;

use Bale\Cms\Models\BaleList;
use Bale\Cms\Services\TenantManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class MigratePelatihan extends Command
{
    protected $signature = 'pelatihan:migrate {--tenant= : The slug of the tenant}';

    protected $description = 'Run Pelatihan migrations for a specific tenant database';

    public function handle(): int
    {
        $path = 'database/migrations/pelatihan';
        $published = glob(base_path($path.'/*.php')) ?: [];

        if ($published === []) {
            $this->error('Migrasi belum dipublikasikan. Jalankan dulu: php artisan pelatihan:publish-migrations');

            return self::FAILURE;
        }

        $tenantSlug = $this->option('tenant');

        if (! $tenantSlug) {
            $tenants = BaleList::all();

            if ($tenants->isEmpty()) {
                $this->error('No tenants found in bale_lists table.');

                return self::FAILURE;
            }

            $tenantSlug = $this->choice(
                'Which tenant database do you want to migrate?',
                $tenants->pluck('slug')->toArray()
            );
        }

        try {
            $tenant = BaleList::where('slug', $tenantSlug)->firstOrFail();

            TenantManager::initializeFromBaleUuid($tenant->id);
            $connection = TenantManager::getActiveConnection();

            if (! $connection) {
                throw new \Exception("Failed to activate connection for tenant {$tenant->slug}");
            }

            $this->info("Migrating Pelatihan tables (DB: {$tenant->database_name})...");

            $this->call('migrate', [
                '--database' => $connection,
                '--path' => $path,
                '--force' => true,
            ]);

            $this->seedSettingsRow($connection);

            $this->info("Migration for tenant {$tenant->slug} completed.");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Migration failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Satu baris pengaturan global per tenant. Kredensial Gemini diisi admin
     * lewat UI, jadi baris hanya perlu ada.
     */
    protected function seedSettingsRow(string $connection): void
    {
        $db = DB::connection($connection);

        if (! $db->getSchemaBuilder()->hasTable('pelatihan_settings')) {
            return;
        }

        if ($db->table('pelatihan_settings')->exists()) {
            return;
        }

        $db->table('pelatihan_settings')->insert([
            'id' => (string) Str::uuid7(),
            'gemini_api_key' => null,
            'gemini_model' => 'gemini-3.1-flash-lite',
            'email_subject' => 'Informasi Program Pelatihan {program}',
            'email_body' => "Halo {nama},\n\nAnda terdaftar pada program pelatihan {program} ({cluster}) tahun {tahun} {quarter}.\n\nSalam,\nDinas Tenaga Kerja",
            'wa_body' => 'Halo {nama}, Anda terdaftar pada program {program} ({cluster}) {tahun} {quarter}.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
