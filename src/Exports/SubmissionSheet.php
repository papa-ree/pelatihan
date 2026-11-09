<?php

namespace Bale\Pelatihan\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

final class SubmissionSheet implements FromArray, WithHeadings, WithTitle
{
    /**
     * @param  list<array<string, string|int>>  $rows
     */
    public function __construct(private readonly array $rows) {}

    public function array(): array
    {
        return array_map(fn (array $row) => [
            $row['submitted_at'],
            $row['type'],
            $row['name'],
            $row['email'],
            $row['nik'],
            $row['need'],
            $row['job'],
            $row['workers'],
            $row['region'],
            $row['cluster'],
            $row['status'],
        ], $this->rows);
    }

    public function headings(): array
    {
        return [
            __('Tanggal'),
            __('Jenis'),
            __('Nama'),
            __('Email'),
            __('NIK'),
            __('Kebutuhan'),
            __('Pekerjaan / Bidang Usaha'),
            __('Jumlah Tenaga Kerja'),
            __('Wilayah Domisili'),
            __('Klaster'),
            __('Status'),
        ];
    }

    public function title(): string
    {
        return __('Permintaan');
    }
}
