<?php

namespace Bale\Pelatihan\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

final class ClusterSheet implements FromArray, WithHeadings, WithTitle
{
    /**
     * @param  list<array<string, string|int>>  $rows
     */
    public function __construct(private readonly array $rows) {}

    public function array(): array
    {
        return array_map(fn (array $row) => [
            $row['name'],
            $row['sub_category'],
            $row['members'],
            $row['urgency'],
            $row['status'],
            $row['keywords'],
            $row['summary'],
        ], $this->rows);
    }

    public function headings(): array
    {
        return [
            __('Klaster'),
            __('Kategori'),
            __('Anggota'),
            __('Urgensi'),
            __('Status'),
            __('Kata Kunci'),
            __('Ringkasan'),
        ];
    }

    public function title(): string
    {
        return __('Klaster');
    }
}
