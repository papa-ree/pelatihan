<?php

use Bale\Pelatihan\Jobs\RunPeriodAnalysisJob;
use Bale\Pelatihan\Listeners\MarkProgramNotificationFailed;
use Bale\Pelatihan\Listeners\MarkProgramNotificationSent;
use Bale\Pelatihan\Models\CitizenProfile;
use Bale\Pelatihan\Models\CitizenUser;
use Bale\Pelatihan\Models\Cluster;
use Bale\Pelatihan\Models\NotificationLog;
use Bale\Pelatihan\Models\Period;
use Bale\Pelatihan\Models\Program;
use Bale\Pelatihan\Models\Setting;
use Bale\Pelatihan\Models\Submission;
use Bale\Pelatihan\Notifications\PelatihanProgramPublished;
use Bale\Pelatihan\Support\CitizenSearch;
use Bale\Pelatihan\Support\NotificationTemplate;
use Bale\Pelatihan\Support\PelatihanCrypto;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

beforeEach(function () {
    config(['pelatihan.data_key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
    PelatihanCrypto::reset();

    Schema::create('pelatihan_periods', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->smallInteger('year');
        $table->string('name');
        $table->text('description')->nullable();
        $table->date('starts_at')->nullable();
        $table->date('ends_at')->nullable();
        $table->boolean('is_active')->default(false);
        $table->string('ai_schedule', 20)->default('none');
        $table->timestamp('last_ai_run_at')->nullable();
        $table->timestamps();
    });

    Schema::create('pelatihan_submissions', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuid('period_id');
        $table->uuid('cluster_id')->nullable();
        $table->char('user_uuid', 36);
        $table->string('type', 20);
        $table->json('data');
        $table->string('status', 40)->default('Pending_AI_Processing');
        $table->text('ai_error')->nullable();
        $table->timestamp('ai_processed_at')->nullable();
        $table->timestamp('submitted_at')->nullable();
        $table->timestamps();
        $table->unique(['user_uuid', 'period_id']);
    });

    Schema::create('pelatihan_citizen_profiles', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->char('user_uuid', 36)->unique();
        $table->string('sso_subject')->nullable();
        $table->text('nik')->nullable();
        $table->char('nik_hash', 64)->nullable()->index();
        $table->text('whatsapp')->nullable();
        $table->timestamp('completed_at')->nullable();
        $table->timestamps();
    });

    Schema::create('pelatihan_settings', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->text('gemini_api_key')->nullable();
        $table->string('gemini_model')->default('gemini-3.1-flash-lite');
        $table->string('email_subject')->nullable();
        $table->text('email_body')->nullable();
        $table->text('wa_body')->nullable();
        $table->boolean('whatsapp_enabled')->default(false);
        $table->timestamps();
    });

    Schema::create('pelatihan_clusters', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuid('period_id');
        $table->string('name');
        $table->string('sub_category')->nullable();
        $table->json('keywords')->nullable();
        $table->unsignedTinyInteger('urgency_score')->default(1);
        $table->text('summary')->nullable();
        $table->text('recommendation')->nullable();
        $table->string('status', 20)->default('pending');
        $table->uuid('merged_into_id')->nullable();
        $table->timestamps();
    });

    Schema::create('pelatihan_programs', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuid('cluster_id');
        $table->smallInteger('year');
        $table->unsignedTinyInteger('quarter');
        $table->string('name');
        $table->string('status', 20)->default('draft');
        $table->timestamp('published_at')->nullable();
        $table->timestamps();
    });

    Schema::create('pelatihan_notification_logs', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuid('program_id');
        $table->char('user_uuid', 36);
        $table->string('channel', 20);
        $table->string('status', 20)->default('queued');
        $table->text('error')->nullable();
        $table->timestamp('sent_at')->nullable();
        $table->timestamps();
    });
});

it('mengaktifkan periode baru tanpa menghapus submission periode lama', function () {
    $old = Period::create(['year' => 2026, 'name' => 'Semester 1']);
    $old->activate();

    $submission = Submission::create([
        'period_id' => $old->id,
        'user_uuid' => (string) Str::uuid(),
        'type' => 'masyarakat',
        'data' => ['kebutuhan' => 'Menjahit'],
    ]);

    $new = Period::create(['year' => 2026, 'name' => 'Semester 2']);
    $new->activate();

    expect(Period::query()->where('is_active', true)->count())->toBe(1)
        ->and($new->fresh()->is_active)->toBeTrue()
        ->and($old->fresh()->is_active)->toBeFalse()
        ->and(Submission::query()->find($submission->id))->not->toBeNull();
});

it('menolak submission kedua untuk akun dan periode yang sama', function () {
    $period = Period::create(['year' => 2026, 'name' => 'Semester 2']);
    $user = (string) Str::uuid();

    Submission::create([
        'period_id' => $period->id,
        'user_uuid' => $user,
        'type' => 'masyarakat',
        'data' => ['kebutuhan' => 'Menjahit'],
    ]);

    expect(fn () => Submission::create([
        'period_id' => $period->id,
        'user_uuid' => $user,
        'type' => 'perusahaan',
        'data' => ['kebutuhan' => 'Servis AC'],
    ]))->toThrow(QueryException::class);
});

it('menyimpan NIK terenkripsi dan nik_hash konsisten untuk NIK yang sama', function () {
    $nik = '3502010101010001';

    $first = CitizenProfile::create(['user_uuid' => (string) Str::uuid(), 'nik' => $nik]);
    $second = CitizenProfile::create(['user_uuid' => (string) Str::uuid(), 'nik' => $nik]);
    $other = CitizenProfile::create(['user_uuid' => (string) Str::uuid(), 'nik' => '3502010101010002']);

    expect($first->fresh()->nik)->toBe($nik)
        ->and($first->fresh()->nik_hash)->toBe(PelatihanCrypto::hash($nik))
        ->and($second->fresh()->nik_hash)->toBe($first->fresh()->nik_hash)
        ->and($other->fresh()->nik_hash)->not->toBe($first->fresh()->nik_hash)
        ->and(DB::table('pelatihan_citizen_profiles')->where('id', $first->id)->value('nik'))->not->toBe($nik);
});

it('menyimpan kunci Gemini terenkripsi di database', function () {
    $setting = Setting::create(['gemini_api_key' => 'rahasia-gemini']);

    expect($setting->fresh()->gemini_api_key)->toBe('rahasia-gemini')
        ->and(DB::table('pelatihan_settings')->where('id', $setting->id)->value('gemini_api_key'))->not->toBe('rahasia-gemini');
});

it('merender placeholder template yang sama untuk email dan WhatsApp', function () {
    $period = Period::create(['year' => 2026, 'name' => 'Semester 2']);
    $cluster = Cluster::create([
        'period_id' => $period->id,
        'name' => 'Menjahit',
        'status' => Cluster::STATUS_APPROVED,
    ]);
    $program = Program::create([
        'cluster_id' => $cluster->id,
        'year' => 2026,
        'quarter' => 4,
        'name' => 'Pelatihan Menjahit Dasar',
        'status' => Program::STATUS_PUBLISHED,
        'published_at' => now(),
    ]);

    $user = new CitizenUser(['name' => 'Budi']);

    $rendered = NotificationTemplate::render(
        'Halo {nama}, ikuti {program} ({cluster}) {tahun} {quarter}.',
        $program,
        $user,
    );

    expect($rendered)->toBe('Halo Budi, ikuti Pelatihan Menjahit Dasar (Menjahit) 2026 Q4.');
});

it('mencatat kegagalan WhatsApp dari event NotificationFailed', function () {
    $programId = (string) Str::uuid();
    $userUuid = (string) Str::uuid();

    $log = NotificationLog::create([
        'program_id' => $programId,
        'user_uuid' => $userUuid,
        'channel' => 'whatsapp',
        'status' => 'queued',
    ]);

    $notification = new PelatihanProgramPublished($programId, $userUuid, 'Subjek', 'Email', 'WA');

    (new MarkProgramNotificationFailed)->handle(new NotificationFailed(
        new CitizenUser,
        $notification,
        'wara',
        ['reason' => 'Device tidak siap.'],
    ));

    expect($log->fresh()->status)->toBe('failed')
        ->and($log->fresh()->error)->toBe('Device tidak siap.');
});

it('membuat CitizenUser bisa menerima notifikasi', function () {
    expect(method_exists(CitizenUser::class, 'notify'))->toBeTrue();
});

it('memilih kanal notifikasi sesuai tujuan yang tersedia', function () {
    $subject = new PelatihanProgramPublished('p', 'u', 'Subjek', 'Email', 'WA', true);

    $emailOnly = new CitizenUser(['email' => 'a@example.com']);
    $emailOnly->setRelation('profile', new CitizenProfile);

    $waOnly = new CitizenUser(['email' => null]);
    $waOnly->setRelation('profile', new CitizenProfile(['whatsapp' => '628123456789']));

    expect($subject->via($emailOnly))->toBe(['mail'])
        ->and($subject->via($waOnly))->toBe(['wara']);
});

it('melewati kanal WhatsApp saat toggle dimatikan', function () {
    $notification = new PelatihanProgramPublished('p', 'u', 'Subjek', 'Email', 'WA');

    $user = new CitizenUser(['email' => 'a@example.com']);
    $user->setRelation('profile', new CitizenProfile(['whatsapp' => '628123456789']));

    $waOnly = new CitizenUser(['email' => null]);
    $waOnly->setRelation('profile', new CitizenProfile(['whatsapp' => '628123456789']));

    expect($notification->via($user))->toBe(['mail'])
        ->and($notification->via($waOnly))->toBe([]);
});

it('menyamarkan NIK dan nomor WhatsApp sebelum dikirim ke AI', function () {
    $period = Period::create(['year' => 2026, 'name' => 'Semester 2']);

    $submission = new Submission(['data' => [
        'kebutuhan' => 'NIK 3502212004120001, WA 081234567890, ingin kursus menjahit.',
        'pekerjaan' => 'Ibu rumah tangga',
    ]]);
    $submission->id = (string) Str::uuid();
    $submission->type = 'masyarakat';

    $job = new RunPeriodAnalysisJob((string) Str::uuid(), $period->id);

    $prompt = (new ReflectionMethod($job, 'prompt'))->invoke($job, $period, collect([$submission]));

    expect($prompt)->not->toContain('3502212004120001')
        ->and($prompt)->not->toContain('081234567890')
        ->and($prompt)->toContain('[NIK]')
        ->and($prompt)->toContain('[nomor]');
});

it('menandai log terkirim per kanal tanpa menimpa kegagalan lain', function () {
    $programId = (string) Str::uuid();
    $userUuid = (string) Str::uuid();

    $email = NotificationLog::create([
        'program_id' => $programId,
        'user_uuid' => $userUuid,
        'channel' => 'email',
        'status' => 'queued',
    ]);

    $wa = NotificationLog::create([
        'program_id' => $programId,
        'user_uuid' => $userUuid,
        'channel' => 'whatsapp',
        'status' => 'queued',
    ]);

    $notification = new PelatihanProgramPublished($programId, $userUuid, 'Subjek', 'Email', 'WA');

    (new MarkProgramNotificationFailed)->handle(new NotificationFailed(
        new CitizenUser,
        $notification,
        'wara',
        ['reason' => 'Device tidak siap.'],
    ));

    (new MarkProgramNotificationSent)->handle(new NotificationSent(
        new CitizenUser,
        $notification,
        'mail',
    ));

    expect($wa->fresh()->status)->toBe('failed')
        ->and($email->fresh()->status)->toBe('sent')
        ->and($email->fresh()->sent_at)->not->toBeNull()
        ->and($email->fresh()->error)->toBeNull();
});

it('mencari warga lewat NIK 16 digit dan LIKE untuk input lain', function () {
    $alpha = CitizenProfile::create([
        'user_uuid' => (string) Str::uuid(),
        'sso_subject' => 'alpha-user',
        'nik' => '3502212004120001',
    ]);

    $beta = CitizenProfile::create([
        'user_uuid' => (string) Str::uuid(),
        'sso_subject' => 'beta-user',
        'nik' => '3502212004120002',
    ]);

    $byNik = CitizenProfile::query();
    CitizenSearch::apply($byNik, '3502212004120002', ['sso_subject']);
    expect($byNik->pluck('id')->all())->toBe([$beta->id]);

    $bySubject = CitizenProfile::query();
    CitizenSearch::apply($bySubject, 'alpha', ['sso_subject']);
    expect($bySubject->pluck('id')->all())->toBe([$alpha->id]);

    $unknownNik = CitizenProfile::query();
    CitizenSearch::apply($unknownNik, '3502212004129999', ['sso_subject']);
    expect($unknownNik->count())->toBe(0);
});
