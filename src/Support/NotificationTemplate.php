<?php

namespace Bale\Pelatihan\Support;

use Bale\Pelatihan\Models\CitizenUser;
use Bale\Pelatihan\Models\Program;

/**
 * Satu perender placeholder untuk email dan WhatsApp, supaya isi kedua kanal
 * tidak pernah berbeda.
 */
final class NotificationTemplate
{
    public static function render(?string $template, Program $program, CitizenUser $user): string
    {
        $program->loadMissing('cluster');

        return str_replace(
            ['{nama}', '{program}', '{cluster}', '{tahun}', '{quarter}'],
            [
                (string) $user->name,
                (string) $program->name,
                (string) ($program->cluster?->name ?? ''),
                (string) $program->year,
                'Q'.$program->quarter,
            ],
            (string) $template,
        );
    }
}
