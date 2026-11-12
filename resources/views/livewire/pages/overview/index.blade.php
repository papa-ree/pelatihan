<div>
    <x-core::breadcrumb :active="__('Overview Pelatihan')" />

    <div class="mt-6 space-y-4">
        <x-core::page-header
            gradient
            :title="__('Pelatihan')"
            :subtitle="$period
                ? __('Permintaan pelatihan masyarakat dan perusahaan. Periode: :name (:year).', ['name' => $period->name, 'year' => $period->year])
                : __('Belum ada periode yang bisa ditampilkan.')">
            <x-slot name="action">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                    <div class="w-full sm:w-56">
                        <x-core::select-dropdown
                            model="periodId"
                            :items="$periodOptions"
                            labelField="label"
                            valueField="value"
                            :placeholder="__('Pilih periode')" />
                    </div>

                    @can('pelatihan.cluster.analyze')
                        <x-core::button variant="success"
                            wire:click="runAnalysis"
                            wire:loading.attr="disabled"
                            wire:target="runAnalysis"
                            :disabled="! $isActivePeriod || $pendingAi === 0 || $analysisState === 'running'"
                            title="{{ ! $isActivePeriod
                                ? __('Analisis hanya untuk periode aktif.')
                                : ($isActivePeriod && $analysisState === 'running'
                                    ? __('Analisis sedang berjalan.')
                                    : ($pendingAi === 0
                                        ? __('Tidak ada permintaan yang menunggu analisis.')
                                        : __('Jalankan analisis AI untuk periode aktif.'))) }}"
                            label="{{ $isActivePeriod && $analysisState === 'running' ? __('Sedang berjalan') : __('Jalankan Analisis AI') }}">
                            <x-slot name="icon">
                                <x-lucide-sparkles class="h-4 w-4" wire:loading.remove wire:target="runAnalysis" />
                                <x-lucide-loader-circle class="h-4 w-4 animate-spin" wire:loading wire:target="runAnalysis" />
                            </x-slot>
                        </x-core::button>
                    @endcan
                </div>
            </x-slot>
        </x-core::page-header>

        <div wire:loading.class="opacity-60" wire:target="periodId" class="space-y-4 transition-opacity duration-150">
            @if(! $period)
                <div class="rounded-xl border border-gray-200 bg-white py-12 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <span class="mx-auto flex size-12 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300">
                        <x-lucide-calendar-x class="size-6" />
                    </span>
                    <p class="mt-3 text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Belum ada periode.') }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Buat periode terlebih dahulu agar overview terisi.') }}</p>
                </div>
            @else
                @php
                    $periodBadge = $period->year;
                @endphp

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <x-core::stat-card-gradient
                        :label="__('Permintaan')"
                        :value="number_format($totalSubmissions)"
                        icon="inbox"
                        tone="teal"
                        :badge="$periodBadge"
                        :hint="__('Masuk pada periode ini')" />

                    <x-core::stat-card-gradient
                        :label="__('Masyarakat')"
                        :value="number_format($citizensCount)"
                        icon="users"
                        tone="indigo"
                        :hint="__('Warga yang mengisi aspirasi')" />

                    <x-core::stat-card-gradient
                        :label="__('Klaster')"
                        :value="number_format($totalClusters)"
                        icon="boxes"
                        tone="sky"
                        :hint="__(':count menunggu persetujuan', ['count' => number_format($pendingClusters)])" />

                    <x-core::stat-card-gradient
                        :label="__('Program')"
                        :value="number_format($totalPrograms)"
                        icon="presentation"
                        tone="emerald"
                        :hint="__(':count terbit', ['count' => number_format($publishedPrograms)])" />
                </div>

                @if($approvedWithoutProgram > 0)
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-xs text-amber-800 dark:border-amber-800/40 dark:bg-amber-900/20 dark:text-amber-300">
                        <x-lucide-triangle-alert class="size-4 shrink-0" />
                        <span class="font-semibold">{{ __(':count klaster disetujui belum punya program.', ['count' => number_format($approvedWithoutProgram)]) }}</span>
                        @can('pelatihan.program.view')
                            <a href="{{ route('pelatihan.program.index') }}" wire:navigate
                                class="font-semibold underline underline-offset-2 hover:no-underline">
                                {{ __('Buka Program') }}
                            </a>
                        @endcan
                    </div>
                @endif

                {{-- Status analisis AI: tanpa interval. Satu fetch hanya saat tab aktif kembali. --}}
                <div
                    wire:key="analysis-status-{{ $period->id }}"
                    x-data="{
                        state: @js($isActivePeriod ? $analysisState : 'archive'),
                        startedAt: @js($analysisStartedAt),
                        isActive: @js($isActivePeriod),
                        pending: {{ $pendingAi }},
                        processed: {{ $processedAi }},
                        failed: {{ $failedAi }},
                        total: {{ $totalSubmissions }},
                        labels: {
                            running: @js(__('Analisis AI sedang berjalan')),
                            done: @js(__('Analisis AI sudah dijalankan')),
                            idle: @js(__('Analisis AI belum dijalankan')),
                            empty: @js(__('Belum ada permintaan')),
                            archive: @js(__('Periode arsip')),
                        },
                        hints: {
                            running: @js(__('Memproses permintaan pada periode ini.')),
                            done: @js(__('Hasil analisis siap ditinjau di halaman Klaster.')),
                            idle: @js(__('Tekan Jalankan Analisis AI untuk memproses permintaan periode ini.')),
                            empty: @js(__('Belum ada aspirasi yang masuk pada periode ini.')),
                            archive: @js(__('Analisis hanya tersedia untuk periode aktif.')),
                        },
                        async check() {
                            if (! this.isActive || document.hidden) {
                                return;
                            }

                            try {
                                const response = await fetch(@js(route('pelatihan.analysis.status', ['period' => $period->id])), {
                                    headers: { Accept: 'application/json' },
                                });

                                if (! response.ok) {
                                    return;
                                }

                                const data = await response.json();
                                const wasRunning = this.state === 'running';

                                this.state = data.state;
                                this.startedAt = data.started_at;
                                this.pending = data.pending;
                                this.processed = data.processed;
                                this.failed = data.failed;
                                this.total = data.processed + data.pending + data.failed;

                                if (wasRunning && this.state !== 'running') {
                                    $wire.$refresh();
                                }
                            } catch (error) {
                                // Jaringan putus sesaat: biarkan status terakhir tampil.
                            }
                        },
                        startedLabel() {
                            if (! this.startedAt) {
                                return '';
                            }

                            return @js(__('mulai')) + ' ' + new Date(this.startedAt)
                                .toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                        },
                    }"
                    x-on:visibilitychange.document="check()"
                    class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex min-w-0 items-center gap-3">
                            <div
                                :class="{
                                    'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300': state === 'running',
                                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300': state === 'done',
                                    'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300': state === 'idle' || state === 'empty' || state === 'archive'
                                }"
                                :aria-busy="state === 'running'"
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg">
                                <x-lucide-loader-circle x-show="state === 'running'" x-cloak class="size-4 animate-spin" />
                                <x-lucide-circle-check x-show="state === 'done'" x-cloak class="size-4" />
                                <x-lucide-activity x-show="state === 'idle'" x-cloak class="size-4" />
                                <x-lucide-inbox x-show="state === 'empty'" x-cloak class="size-4" />
                                <x-lucide-archive x-show="state === 'archive'" x-cloak class="size-4" />
                            </div>

                            <div class="min-w-0">
                                <h2 class="text-sm font-semibold text-gray-900 dark:text-white" x-text="labels[state] ?? labels.idle"></h2>
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                    <span x-text="hints[state] ?? hints.idle"></span>
                                    <template x-if="state === 'running' && startedAt">
                                        <span class="text-gray-400 dark:text-gray-500">· <span x-text="startedLabel()"></span></span>
                                    </template>
                                </p>
                                <p x-show="state !== 'empty'" class="mt-0.5 text-xs tabular-nums text-gray-500 dark:text-gray-400">
                                    <span x-text="processed"></span>/<span x-text="total"></span> {{ __('sudah dianalisis') }}
                                    · <span x-text="pending"></span> {{ __('menunggu') }}
                                    · <span x-text="failed"></span> {{ __('gagal') }}
                                </p>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <span x-show="state === 'running'" x-cloak
                                class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                                <span class="inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-amber-500"></span>
                                {{ __('berjalan') }}
                            </span>

                            @if($lastRunAt)
                                <span x-show="state !== 'running' && state !== 'empty'" x-cloak
                                    class="text-[11px] text-gray-400 dark:text-gray-500">
                                    {{ __('Terakhir dijalankan') }} {{ $lastRunAt->diffForHumans() }}
                                </span>
                            @endif

                            @can('pelatihan.cluster.analyze')
                                @if($failedAi > 0 && $isActivePeriod)
                                    <button type="button" wire:click="retryFailed" wire:loading.attr="disabled" wire:target="retryFailed"
                                        x-show="state !== 'running'" x-cloak
                                        class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100 disabled:opacity-60 dark:border-rose-800/40 dark:bg-rose-900/20 dark:text-rose-300">
                                        <x-lucide-rotate-cw class="size-3.5" wire:loading.remove wire:target="retryFailed" />
                                        <x-lucide-loader-circle class="size-3.5 animate-spin" wire:loading wire:target="retryFailed" />
                                        {{ __('Coba lagi yang gagal') }} ({{ number_format($failedAi) }})
                                    </button>
                                @endif
                            @endcan
                        </div>
                    </div>
                </div>

                @if($isActivePeriod && $clusterSummary->isNotEmpty())
                    @php
                        $chartClusters = $clusterSummary->take(8);
                    @endphp

                    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex flex-col gap-3 border-b border-gray-200 px-5 py-4 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    <x-lucide-chart-column class="size-4" />
                                </div>
                                <div class="min-w-0">
                                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ __('Klaster hasil analisis') }}
                                    </h2>
                                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $clusterSummary->count() > 8
                                            ? __(':shown klaster teratas dari :total, diurutkan dari permintaan terbanyak.', ['shown' => 8, 'total' => $clusterSummary->count()])
                                            : __(':count klaster, diurutkan dari permintaan terbanyak.', ['count' => number_format($clusterSummary->count())]) }}
                                    </p>
                                </div>
                            </div>

                            <span class="inline-flex shrink-0 items-center gap-3 self-start rounded-full bg-gray-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-gray-600 dark:bg-gray-700 dark:text-gray-300 sm:self-auto">
                                <span class="inline-flex items-center gap-1">
                                    <span class="inline-block size-1.5 rounded-full bg-teal-500"></span>
                                    {{ __('Umum') }}
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <span class="inline-block size-1.5 rounded-full bg-sky-500"></span>
                                    {{ __('Industri') }}
                                </span>
                            </span>
                        </div>

                        <div class="p-4 sm:p-5">
                            <div wire:key="cluster-chart-{{ $period->id }}-{{ $chartClusters->sum('requests') }}">
                                <x-core::chart
                                    type="bar"
                                    :labels="$chartClusters->pluck('name')->all()"
                                    :datasets="[
                                        [
                                            'label' => __('Umum'),
                                            'data' => $chartClusters->pluck('umum')->all(),
                                            'backgroundColor' => '#14B8A6',
                                        ],
                                        [
                                            'label' => __('Industri'),
                                            'data' => $chartClusters->pluck('industri')->all(),
                                            'backgroundColor' => '#0EA5E9',
                                        ],
                                    ]"
                                    :options="[
                                        'indexAxis' => 'y',
                                        'plugins' => ['legend' => ['display' => true, 'position' => 'bottom', 'labels' => ['boxWidth' => 10, 'font' => ['size' => 11], 'color' => '#9CA3AF']]],
                                        'scales' => [
                                            'x' => ['stacked' => true, 'beginAtZero' => true, 'ticks' => ['precision' => 0, 'font' => ['size' => 10], 'color' => '#9CA3AF'], 'grid' => ['color' => 'rgba(156,163,175,0.15)']],
                                            'y' => ['stacked' => true, 'ticks' => ['font' => ['size' => 11], 'color' => '#9CA3AF'], 'grid' => ['display' => false]],
                                        ],
                                    ]" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex flex-col gap-3 border-b border-gray-200 px-5 py-4 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    <x-lucide-table-2 class="size-4" />
                                </div>
                                <div class="min-w-0">
                                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ __('Rincian klaster') }}
                                    </h2>
                                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('Jumlah permintaan, sumber pemohon, dan rekomendasi AI.') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead>
                                    <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        <th class="px-5 py-3">{{ __('Klaster') }}</th>
                                        <th class="px-3 py-3 text-right">{{ __('Permintaan') }}</th>
                                        <th class="px-3 py-3">{{ __('Sumber (umum / industri)') }}</th>
                                        <th class="px-5 py-3">{{ __('Rekomendasi') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                                    @foreach($clusterSummary as $row)
                                        <tr wire:key="cluster-row-{{ $period->id }}-{{ $loop->index }}" class="hover:bg-gray-50/80 dark:hover:bg-gray-800/50">
                                            <td class="px-5 py-3">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $row['name'] }}</div>
                                                @if($row['sub_category'])
                                                    <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $row['sub_category'] }}</div>
                                                @endif
                                            </td>
                                            <td class="px-3 py-3 text-right text-sm tabular-nums text-gray-700 dark:text-gray-300">
                                                {{ number_format($row['requests']) }}
                                            </td>
                                            <td class="px-3 py-3">
                                                <div class="flex items-center gap-2">
                                                    <div class="flex h-1.5 w-20 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                                                        <div class="bg-teal-500" style="width: {{ $row['umum_percent'] }}%"></div>
                                                        <div class="bg-sky-500" style="width: {{ $row['industri_percent'] }}%"></div>
                                                    </div>
                                                    <span class="text-xs tabular-nums text-gray-600 dark:text-gray-300">
                                                        {{ $row['umum_percent'] }}% / {{ $row['industri_percent'] }}%
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="px-5 py-3">
                                                @if($row['recommendation'])
                                                    <p class="line-clamp-2 max-w-md text-xs text-gray-600 dark:text-gray-300" title="{{ $row['recommendation'] }}">
                                                        {{ $row['recommendation'] }}
                                                    </p>
                                                @else
                                                    <span class="text-xs text-gray-400 dark:text-gray-500">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
