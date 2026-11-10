<div>
    <x-core::breadcrumb :active="__('Overview Pelatihan')" />

    <div class="mt-6 space-y-4">
        <x-core::page-header
            gradient
            :title="__('Pelatihan')"
            :subtitle="$period
                ? __('Permintaan pelatihan masyarakat dan perusahaan. Periode aktif: :name (:year).', ['name' => $period->name, 'year' => $period->year])
                : __('Permintaan pelatihan masyarakat dan perusahaan. Belum ada periode aktif.')">
            <x-slot name="action">
                @can('pelatihan.cluster.analyze')
                    <x-core::button variant="success"
                        wire:click="runAnalysis"
                        wire:loading.attr="disabled"
                        :disabled="! $period || $pendingAi === 0"
                        title="{{ $pendingAi === 0 ? __('Tidak ada permintaan yang menunggu analisis.') : __('Jalankan analisis AI untuk periode aktif.') }}"
                        label="{{ __('Jalankan Analisis AI') }}">
                        <x-slot name="icon">
                            <x-lucide-sparkles class="h-4 w-4" wire:loading.remove wire:target="runAnalysis" />
                            <x-lucide-loader-circle class="h-4 w-4 animate-spin" wire:loading wire:target="runAnalysis" />
                        </x-slot>
                    </x-core::button>
                @endcan
            </x-slot>
        </x-core::page-header>

        @php
            $periodBadge = $period ? __('Periode :year', ['year' => $period->year]) : __('Tanpa periode');
        @endphp

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-core::stat-card-gradient
                :label="__('Permintaan')"
                :value="number_format($totalSubmissions)"
                icon="inbox"
                tone="teal"
                :badge="$periodBadge"
                :hint="__('Masuk pada periode aktif')" />

            <x-core::stat-card-gradient
                :label="__('Klaster')"
                :value="number_format($totalClusters)"
                icon="boxes"
                tone="indigo"
                :hint="__('Hasil analisis AI')" />

            <x-core::stat-card-gradient
                :label="__('Klaster Disetujui')"
                :value="number_format($approvedClusters)"
                icon="check-check"
                tone="emerald"
                :hint="__('Siap dibuat program')" />

            <x-core::stat-card-gradient
                :label="__('Program Terbit')"
                :value="number_format($publishedPrograms)"
                icon="book-open-check"
                tone="sky"
                :hint="__('Sudah diumumkan')" />
        </div>

        {{-- Progres analisis AI periode aktif --}}
        <div wire:poll.visible.5s
            class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex flex-col gap-3 border-b border-gray-200 px-5 py-4 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        <x-lucide-activity class="size-4" />
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Progres Analisis AI') }}</h2>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('Dihitung dari status permintaan pada periode aktif.') }}
                        </p>
                    </div>
                </div>

                <span class="inline-flex w-fit items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider
                    {{ $pendingAi === 0 && $totalSubmissions > 0 ? 'bg-teal-50 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' }}">
                    @if(! $period)
                        {{ __('Tanpa periode') }}
                    @elseif($totalSubmissions === 0)
                        {{ __('Belum ada data') }}
                    @elseif($pendingAi === 0)
                        {{ __('Selesai') }}
                    @elseif($lastRunAt || $dispatched)
                        <span class="inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-amber-500"></span>
                        {{ __('Antrean / berjalan') }}
                    @else
                        {{ __('Belum dijalankan') }}
                    @endif
                </span>
            </div>

            <div class="space-y-4 p-4 sm:p-5">
                <div>
                    <div class="flex items-end justify-between gap-3">
                        <div class="text-3xl font-bold tracking-tight text-gray-900 tabular-nums dark:text-white">
                            {{ $progressPercent }}<span class="text-lg font-semibold text-gray-400">%</span>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 tabular-nums">
                            {{ number_format($processedAi + $failedAi) }} / {{ number_format($totalSubmissions) }} {{ __('permintaan') }}
                        </div>
                    </div>

                    <div class="mt-2 h-2.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                        <div class="h-full rounded-full bg-gradient-to-r from-teal-500 to-emerald-500 transition-all duration-500"
                            style="width: {{ $progressPercent }}%"></div>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-1.5 text-xs">
                        <span class="inline-flex items-center gap-1.5 text-gray-500 dark:text-gray-400">
                            <span class="inline-block h-2 w-2 rounded-full bg-teal-500"></span>
                            {{ __('Terproses') }}: <span class="font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ number_format($processedAi) }}</span>
                        </span>
                        <span class="inline-flex items-center gap-1.5 text-gray-500 dark:text-gray-400">
                            <span class="inline-block h-2 w-2 rounded-full bg-rose-500"></span>
                            {{ __('Gagal') }}: <span class="font-semibold tabular-nums {{ $failedAi > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-900 dark:text-gray-100' }}">{{ number_format($failedAi) }}</span>
                        </span>
                        <span class="inline-flex items-center gap-1.5 text-gray-500 dark:text-gray-400">
                            <span class="inline-block h-2 w-2 rounded-full bg-amber-500"></span>
                            {{ __('Menunggu') }}: <span class="font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ number_format($pendingAi) }}</span>
                        </span>
                        @if($lastRunAt)
                            <span class="inline-flex items-center gap-1.5 text-gray-400 dark:text-gray-500">
                                <x-lucide-history class="size-3.5" />
                                {{ __('Terakhir dijalankan') }} {{ $lastRunAt->diffForHumans() }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="flex flex-col gap-2 border-t border-gray-200 pt-3 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-[11px] text-gray-400 dark:text-gray-500">
                        {{ __('Analisis berjalan di queue worker. Pastikan "php artisan queue:work" aktif.') }}
                    </p>

                    @if($failedAi > 0)
                        @can('pelatihan.cluster.analyze')
                            <button type="button" wire:click="retryFailed" wire:loading.attr="disabled" wire:target="retryFailed"
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100 disabled:opacity-60 dark:border-rose-800/40 dark:bg-rose-900/20 dark:text-rose-300">
                                <x-lucide-rotate-cw class="size-3.5" wire:loading.remove wire:target="retryFailed" />
                                {{ __('Coba lagi yang gagal') }} ({{ number_format($failedAi) }})
                            </button>
                        @endcan
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
