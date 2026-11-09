<?php

namespace Bale\Pelatihan;

class PelatihanPermissions
{
    const VIEW = 'pelatihan.read';

    const PERIOD_VIEW = 'pelatihan.period.view';

    const PERIOD_CREATE = 'pelatihan.period.create';

    const PERIOD_UPDATE = 'pelatihan.period.update';

    const PERIOD_DELETE = 'pelatihan.period.delete';

    const SUBMISSION_VIEW = 'pelatihan.submission.view';

    const CITIZEN_VIEW = 'pelatihan.citizen.view';

    const CLUSTER_VIEW = 'pelatihan.cluster.view';

    const CLUSTER_APPROVE = 'pelatihan.cluster.approve';

    const CLUSTER_MERGE = 'pelatihan.cluster.merge';

    const CLUSTER_REJECT = 'pelatihan.cluster.reject';

    const CLUSTER_ANALYZE = 'pelatihan.cluster.analyze';

    const PROGRAM_VIEW = 'pelatihan.program.view';

    const PROGRAM_CREATE = 'pelatihan.program.create';

    const PROGRAM_UPDATE = 'pelatihan.program.update';

    const PROGRAM_DELETE = 'pelatihan.program.delete';

    const NOTIFICATION_SEND = 'pelatihan.notification.send';

    const EXPORT = 'pelatihan.export';

    const SETTINGS_VIEW = 'pelatihan.settings.view';

    const SETTINGS_UPDATE = 'pelatihan.settings.update';

    const ALL = [
        self::VIEW,
        self::PERIOD_VIEW,
        self::PERIOD_CREATE,
        self::PERIOD_UPDATE,
        self::PERIOD_DELETE,
        self::SUBMISSION_VIEW,
        self::CITIZEN_VIEW,
        self::CLUSTER_VIEW,
        self::CLUSTER_APPROVE,
        self::CLUSTER_MERGE,
        self::CLUSTER_REJECT,
        self::CLUSTER_ANALYZE,
        self::PROGRAM_VIEW,
        self::PROGRAM_CREATE,
        self::PROGRAM_UPDATE,
        self::PROGRAM_DELETE,
        self::NOTIFICATION_SEND,
        self::EXPORT,
        self::SETTINGS_VIEW,
        self::SETTINGS_UPDATE,
    ];
}
