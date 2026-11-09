<?php

namespace Bale\Pelatihan\Livewire\Pages\Submission;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Pelatihan\Models\Submission;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('cms::layouts.app')]
class Detail extends Component
{
    public string $submissionId;

    public function mount(string $id): void
    {
        TenantConnectionService::ensureActive();

        if (! auth()->user()->can('pelatihan.submission.view')) {
            abort(403, 'Unauthorized action.');
        }

        $this->submissionId = $id;
    }

    #[Title('Detail Permintaan')]
    public function render()
    {
        $submission = Submission::with(['user', 'period', 'profile'])->findOrFail($this->submissionId);

        return view('pelatihan::livewire.pages.submission.detail', [
            'submission' => $submission,
            'profile' => $submission->profile,
        ]);
    }
}
