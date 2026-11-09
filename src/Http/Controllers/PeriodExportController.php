<?php

namespace Bale\Pelatihan\Http\Controllers;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Core\Support\Pdf;
use Bale\Pelatihan\Exports\PeriodExport;
use Bale\Pelatihan\Models\Period;
use Bale\Pelatihan\Support\PeriodReport;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Unduh data satu periode: XLSX (Klaster + Permintaan) atau PDF ringkasan.
 */
final class PeriodExportController
{
    public function __invoke(string $period, string $format): BinaryFileResponse|Response
    {
        TenantConnectionService::ensureActive();

        $period = Period::findOrFail($period);
        $report = new PeriodReport($period);

        $filename = 'pelatihan-'.$period->year.'-'.Str::slug($period->name);

        if ($format === 'pdf') {
            return Pdf::download(
                'pelatihan::pdf.period',
                ['report' => $report],
                $filename.'.pdf',
                'a4',
                'landscape',
            );
        }

        return Excel::download(new PeriodExport($report), $filename.'.xlsx');
    }
}
