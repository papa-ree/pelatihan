<?php

namespace Bale\Pelatihan\Commands;

use Bale\Cms\Models\BaleList;
use Bale\Cms\Services\TenantManager;
use Bale\Pelatihan\Jobs\RunPeriodAnalysisJob;
use Bale\Pelatihan\Models\Period;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AnalyzePeriod extends Command
{
    protected $signature = 'pelatihan:analyze
        {period? : UUID periode (kosong = periode aktif)}
        {--tenant= : Slug tenant}
        {--due : Hanya periode yang jadwal AI-nya sudah jatuh tempo}';

    protected $description = 'Jalankan analisis AI untuk permintaan pelatihan yang belum diproses';

    public function handle(): int
    {
        if ($this->option('due')) {
            $this->dispatchDue();

            return self::SUCCESS;
        }

        $tenant = $this->resolveTenant();

        if (! $tenant) {
            return self::FAILURE;
        }

        TenantManager::initializeFromBaleUuid($tenant->id);

        $period = $this->argument('period')
            ? Period::find($this->argument('period'))
            : Period::active();

        if (! $period) {
            $this->error('Periode tidak ditemukan.');

            return self::FAILURE;
        }

        RunPeriodAnalysisJob::dispatch($tenant->id, $period->id);

        $this->info("Analisis periode \"{$period->name}\" ({$tenant->slug}) masuk antrean.");

        return self::SUCCESS;
    }

    private function resolveTenant(): ?BaleList
    {
        if ($slug = $this->option('tenant')) {
            return BaleList::where('slug', $slug)->first() ?: $this->failed('Tenant tidak ditemukan.');
        }

        if (BaleList::count() === 1) {
            return BaleList::first();
        }

        $this->error('Sebutkan --tenant=slug karena ada lebih dari satu tenant.');

        return null;
    }

    /**
     * Dipanggil scheduler harian: hanya periode aktif yang jadwalnya jatuh
     * tempo dan masih punya permintaan belum dianalisis.
     */
    private function dispatchDue(): void
    {
        foreach (BaleList::all() as $tenant) {
            try {
                TenantManager::initializeFromBaleUuid($tenant->id);

                $connection = TenantManager::getActiveConnection();

                if (! $connection || ! Schema::connection($connection)->hasTable('pelatihan_periods')) {
                    continue;
                }

                $period = Period::active();

                if (! $period || ! $this->isDue($period) || ! $period->submissions()->pendingAi()->exists()) {
                    continue;
                }

                RunPeriodAnalysisJob::dispatch($tenant->id, $period->id);
                $this->info("Antre: {$tenant->slug} / {$period->name}");
            } catch (Throwable $e) {
                report($e);
                $this->warn("Lewati {$tenant->slug}: {$e->getMessage()}");
            }
        }
    }

    private function isDue(Period $period): bool
    {
        $interval = match ($period->ai_schedule) {
            'weekly' => now()->subWeek(),
            'monthly' => now()->subMonth(),
            default => null,
        };

        if (! $interval) {
            return false;
        }

        return ! $period->last_ai_run_at || $period->last_ai_run_at->lt($interval);
    }

    private function failed(string $message): null
    {
        $this->error($message);

        return null;
    }
}
