<?php

namespace Bale\Pelatihan\Exports;

use Bale\Pelatihan\Support\PeriodReport;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

final class PeriodExport implements Export, WithMultipleSheets
{
    public function __construct(private readonly PeriodReport $report) {}

    /**
     * @return array<int, object>
     */
    public function sheets(): array
    {
        return [
            new ClusterSheet($this->report->clusterRows()),
            new SubmissionSheet($this->report->submissionRows()),
        ];
    }
}
