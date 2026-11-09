<?php

namespace Bale\Pelatihan\Livewire\Pages\Program;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Pelatihan\Jobs\SendProgramNotificationToUserJob;
use Bale\Pelatihan\Models\Cluster;
use Bale\Pelatihan\Models\NotificationLog;
use Bale\Pelatihan\Models\Program;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('cms::layouts.app')]
class Form extends Component
{
    public ?string $programId = null;

    public string $cluster_id = '';

    public int $year;

    public int $quarter;

    public string $name = '';

    public string $status = Program::STATUS_DRAFT;

    /** @var array<int, array{label: string, value: string}> */
    public array $clusterOptions = [];

    public function mount(?string $id = null): void
    {
        TenantConnectionService::ensureActive();

        if (! auth()->user()->can($id ? 'pelatihan.program.update' : 'pelatihan.program.create')) {
            abort(403, 'Unauthorized action.');
        }

        $this->year = (int) now()->year;
        $this->quarter = (int) ceil(now()->month / 3);

        if (! $id) {
            return;
        }

        $this->programId = $id;
        $program = Program::findOrFail($id);

        $this->cluster_id = $program->cluster_id;
        $this->year = $program->year;
        $this->quarter = $program->quarter;
        $this->name = $program->name;
        $this->status = $program->status;
    }

    public function save(): void
    {
        TenantConnectionService::ensureActive();

        $this->validate([
            'cluster_id' => 'required|uuid',
            'year' => 'required|integer|min:2000|max:2100',
            'quarter' => 'required|integer|in:1,2,3,4',
            'name' => 'required|string|max:255',
            'status' => 'required|in:draft,published',
        ]);

        // Klaster divalidasi lewat model karena `exists:` akan memakai koneksi
        // default (landlord), bukan koneksi tenant.
        $cluster = Cluster::query()->approved()->find($this->cluster_id);

        if (! $cluster) {
            $this->addError('cluster_id', __('Program hanya bisa dibuat dari klaster yang disetujui.'));

            return;
        }

        $program = $this->programId ? Program::findOrFail($this->programId) : new Program;

        $program->fill([
            'cluster_id' => $cluster->id,
            'year' => $this->year,
            'quarter' => $this->quarter,
            'name' => $this->name,
            'status' => $this->status,
            'published_at' => $this->status === Program::STATUS_PUBLISHED
                ? ($program->published_at ?? now())
                : null,
        ])->save();

        $this->dispatch('toast', message: __('Program berhasil disimpan.'), type: 'success');

        $this->redirect(route('pelatihan.program.index'), navigate: true);
    }

    /**
     * Jadwalkan ulang hanya peserta yang masih punya log `failed`.
     * Job per peserta melewati kanal yang sudah `sent`, jadi tidak dobel.
     */
    public function resendFailed(): void
    {
        TenantConnectionService::ensureActive();

        if (! auth()->user()->can('pelatihan.notification.send')) {
            abort(403, 'Unauthorized action.');
        }

        $program = $this->programId ? Program::find($this->programId) : null;

        if (! $program || $program->status !== Program::STATUS_PUBLISHED) {
            $this->dispatch('toast', message: __('Hanya program terbit yang bisa dikirim.'), type: 'error');

            return;
        }

        $users = NotificationLog::query()
            ->where('program_id', $program->id)
            ->where('status', 'failed')
            ->distinct()
            ->pluck('user_uuid');

        if ($users->isEmpty()) {
            $this->dispatch('toast', message: __('Tidak ada pengiriman yang gagal.'), type: 'error');

            return;
        }

        foreach ($users as $uuid) {
            SendProgramNotificationToUserJob::dispatch(session('bale_active_uuid'), $program->id, $uuid);
        }

        $this->dispatch('toast', message: __(':count peserta dijadwalkan ulang. Pastikan queue worker berjalan.', ['count' => $users->count()]), type: 'success');
    }

    #[Title('Form Program')]
    public function render()
    {
        $this->clusterOptions = Cluster::query()
            ->approved()
            ->with('period')
            ->orderBy('name')
            ->get()
            ->map(fn (Cluster $cluster) => [
                'label' => $cluster->name.' ('.$cluster->period?->name.')',
                'value' => $cluster->id,
            ])
            ->all();

        $logs = $this->programId
            ? NotificationLog::query()
                ->where('program_id', $this->programId)
                ->with('user')
                ->latest()
                ->limit(50)
                ->get()
            : collect();

        $summary = ['queued' => 0, 'sent' => 0, 'failed' => 0];

        if ($this->programId) {
            $counts = NotificationLog::query()
                ->where('program_id', $this->programId)
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            foreach (array_keys($summary) as $status) {
                $summary[$status] = (int) ($counts[$status] ?? 0);
            }
        }

        return view('pelatihan::livewire.pages.program.form', [
            'logs' => $logs,
            'summary' => $summary,
        ]);
    }
}
