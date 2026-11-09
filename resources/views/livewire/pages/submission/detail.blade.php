<div>
    <x-core::breadcrumb
        :items="[['label' => __('Permintaan Pelatihan'), 'route' => 'pelatihan.submission.index']]"
        :active="__('Detail Permintaan')" />

    <div class="mt-6 space-y-4">
        <x-core::page-header
            :title="__('Detail Permintaan')"
            :subtitle="__('Data formulir yang dikirim warga atau perusahaan melalui portal pelatihan.')" />

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <div class="h-full overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Data Formulir') }}</h2>
                    </div>
                    <dl class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse(($submission->data ?? []) as $key => $value)
                            <div class="grid grid-cols-1 gap-1 px-5 py-3 sm:grid-cols-3">
                                <dt class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    {{ \Illuminate\Support\Str::headline($key) }}
                                </dt>
                                <dd class="text-sm text-gray-900 dark:text-gray-100 sm:col-span-2">
                                    {{ is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : ($value ?: '—') }}
                                </dd>
                            </div>
                        @empty
                            <div class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ __('Tidak ada data formulir.') }}
                            </div>
                        @endforelse
                    </dl>
                </div>
            </div>

            <div class="space-y-4">
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Pengirim') }}</h2>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('Nama') }}</dt>
                            <dd class="text-right text-gray-900 dark:text-gray-100">{{ $submission->user?->name ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('Email') }}</dt>
                            <dd class="text-right break-all text-gray-900 dark:text-gray-100">{{ $submission->user?->email ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('NIK') }}</dt>
                            <dd class="text-right font-mono text-gray-900 dark:text-gray-100">{{ $profile?->maskedNik() ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('WhatsApp') }}</dt>
                            <dd class="text-right text-gray-900 dark:text-gray-100">{{ $profile?->whatsapp ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Status') }}</h2>

                    @php
                        $status = match ($submission->status) {
                            \Bale\Pelatihan\Models\Submission::STATUS_PROCESSED => [
                                'label' => __('Selesai'),
                                'class' => 'bg-teal-50 text-teal-700 border-teal-100 dark:bg-teal-900/20 dark:text-teal-300 dark:border-teal-800/40',
                            ],
                            \Bale\Pelatihan\Models\Submission::STATUS_FAILED => [
                                'label' => __('Gagal'),
                                'class' => 'bg-rose-50 text-rose-700 border-rose-100 dark:bg-rose-900/20 dark:text-rose-300 dark:border-rose-800/40',
                            ],
                            default => [
                                'label' => __('Menunggu AI'),
                                'class' => 'bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800/40',
                            ],
                        };
                    @endphp

                    <span class="mt-2 inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-medium {{ $status['class'] }}">
                        {{ $status['label'] }}
                    </span>

                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('Periode') }}: {{ $submission->period?->name }} ({{ $submission->period?->year }})
                    </p>
                    @if($submission->ai_error)
                        <p class="mt-2 text-xs text-rose-600 dark:text-rose-400">{{ $submission->ai_error }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
