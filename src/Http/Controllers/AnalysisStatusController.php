<?php

namespace Bale\Pelatihan\Http\Controllers;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Pelatihan\Models\Period;
use Bale\Pelatihan\Models\Submission;
use Bale\Pelatihan\Support\AnalysisState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Status analisis AI satu periode untuk kartu async di halaman overview.
 * `running` hanya berasal dari penanda cache job; sisanya diturunkan dari
 * status permintaan agar tidak pernah mengaku berjalan tanpa worker.
 */
final class AnalysisStatusController
{
    public function __invoke(Request $request): JsonResponse
    {
        TenantConnectionService::ensureActive();

        $period = Period::find((string) $request->query('period'));

        if (! $period) {
            return response()->json(['state' => 'empty', 'pending' => 0, 'processed' => 0, 'failed' => 0, 'last_run_at' => null, 'started_at' => null]);
        }

        $counts = Submission::query()
            ->where('period_id', $period->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $pending = (int) $counts->get(Submission::STATUS_PENDING, 0);
        $processed = (int) $counts->get(Submission::STATUS_PROCESSED, 0);
        $failed = (int) $counts->get(Submission::STATUS_FAILED, 0);
        $startedAt = AnalysisState::startedAt($period->id);

        $state = match (true) {
            $startedAt !== null => 'running',
            $counts->sum() === 0 => 'empty',
            $pending === 0 => 'done',
            default => 'idle',
        };

        return response()->json([
            'state' => $state,
            'pending' => $pending,
            'processed' => $processed,
            'failed' => $failed,
            'last_run_at' => $period->last_ai_run_at?->toIso8601String(),
            'started_at' => $startedAt,
        ]);
    }
}
