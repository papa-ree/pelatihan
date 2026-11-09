<?php

use Bale\Pelatihan\PelatihanPermissions;

/**
 * Menu untuk package bale/pelatihan (layout tenant/CMS).
 */
return [
    'type' => 'tenant',

    'groups' => [
        [
            'key' => 'pelatihan',
            'label' => 'Pelatihan',
            'icon' => 'graduation-cap',
            'items' => [
                [
                    'label' => 'Overview',
                    'url' => 'pelatihan/overview',
                    'icon' => 'bar-chart-3',
                    'permission' => PelatihanPermissions::VIEW,
                    'table' => 'pelatihan_periods',
                ],
                [
                    'label' => 'Periode',
                    'url' => 'pelatihan/periods',
                    'icon' => 'calendar-range',
                    'permission' => PelatihanPermissions::PERIOD_VIEW,
                    'table' => 'pelatihan_periods',
                ],
                [
                    'label' => 'Permintaan',
                    'url' => 'pelatihan/submissions',
                    'icon' => 'inbox',
                    'permission' => PelatihanPermissions::SUBMISSION_VIEW,
                    'table' => 'pelatihan_submissions',
                ],
                [
                    'label' => 'Klaster',
                    'url' => 'pelatihan/clusters',
                    'icon' => 'boxes',
                    'permission' => PelatihanPermissions::CLUSTER_VIEW,
                    'table' => 'pelatihan_clusters',
                ],
                [
                    'label' => 'Program',
                    'url' => 'pelatihan/programs',
                    'icon' => 'presentation',
                    'permission' => PelatihanPermissions::PROGRAM_VIEW,
                    'table' => 'pelatihan_programs',
                ],
                [
                    'label' => 'Masyarakat',
                    'url' => 'pelatihan/citizens',
                    'icon' => 'users',
                    'permission' => PelatihanPermissions::CITIZEN_VIEW,
                    'table' => 'pelatihan_citizen_profiles',
                ],
                [
                    'label' => 'Pengaturan',
                    'url' => 'pelatihan/settings',
                    'icon' => 'settings',
                    'permission' => PelatihanPermissions::SETTINGS_VIEW,
                    'table' => 'pelatihan_settings',
                ],
            ],
        ],
    ],
];
