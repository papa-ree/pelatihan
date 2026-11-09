<div>
    <x-core::breadcrumb
        :items="[['label' => __('Program Pelatihan'), 'route' => 'pelatihan.program.index']]"
        :active="$programId ? __('Edit Program') : __('Tambah Program')" />

    <div class="mt-6 space-y-4">
        <x-core::page-header
            :title="$programId ? __('Edit Program') : __('Program Baru')"
            :subtitle="__('Hanya klaster yang disetujui yang bisa dijadikan program.')" />

        <div class="max-w-4xl space-y-4">
            <x-core::page-container>
                <form wire:submit="save" class="space-y-5">
                    <x-core::input label="{{ __('Nama Program') }}" wire:model="name" required />

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                        <x-core::select-dropdown
                            label="{{ __('Klaster') }}"
                            model="cluster_id"
                            :items="$clusterOptions"
                            labelField="label"
                            valueField="value"
                            placeholder="{{ __('Pilih klaster disetujui') }}" />

                        <x-core::input type="number" label="{{ __('Tahun') }}" wire:model="year" required />
                        <x-core::input type="number" label="{{ __('Quarter (1-4)') }}" wire:model="quarter" required />
                    </div>
                    @error('cluster_id')
                        <p class="text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror

                    <x-core::select-dropdown
                        label="{{ __('Status') }}"
                        model="status"
                        :items="[
                            ['label' => __('Draf'), 'value' => 'draft'],
                            ['label' => __('Terbit'), 'value' => 'published'],
                        ]"
                        labelField="label"
                        valueField="value" />

                    <div class="flex items-center justify-between border-t border-gray-200 pt-5 dark:border-gray-700">
                        <x-core::secondary-button link href="{{ route('pelatihan.program.index') }}" label="{{ __('Batal') }}" />
                        <x-core::button type="submit" label="{{ __('Simpan Program') }}">
                            <x-slot name="icon"><x-lucide-check class="h-4 w-4" /></x-slot>
                        </x-core::button>
                    </div>
                </form>
            </x-core::page-container>

            @if($programId)
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Riwayat Pengiriman') }}</h2>
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('50 pengiriman terakhir untuk program ini.') }}</p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-md border border-amber-100 bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 dark:border-amber-800/40 dark:bg-amber-900/20 dark:text-amber-400">
                                    {{ __('Antre') }}: {{ $summary['queued'] }}
                                </span>
                                <span class="inline-flex items-center rounded-md border border-teal-100 bg-teal-50 px-2 py-0.5 text-xs font-medium text-teal-700 dark:border-teal-800/40 dark:bg-teal-900/20 dark:text-teal-300">
                                    {{ __('Terkirim') }}: {{ $summary['sent'] }}
                                </span>
                                <span class="inline-flex items-center rounded-md border border-rose-100 bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 dark:border-rose-800/40 dark:bg-rose-900/20 dark:text-rose-300">
                                    {{ __('Gagal') }}: {{ $summary['failed'] }}
                                </span>

                                @if($summary['failed'] > 0)
                                    @can('pelatihan.notification.send')
                                        <button type="button" wire:click="resendFailed" wire:loading.attr="disabled" wire:target="resendFailed"
                                            class="inline-flex items-center gap-1.5 rounded-md border border-gray-200 px-2.5 py-1 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 disabled:opacity-60 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700">
                                            <x-lucide-rotate-cw class="size-3.5" wire:loading.remove wire:target="resendFailed" />
                                            <x-lucide-loader-circle class="size-3.5 animate-spin" wire:loading wire:target="resendFailed" />
                                            {{ __('Kirim ulang yang gagal') }}
                                        </button>
                                    @endcan
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50/60 dark:bg-gray-800/40">
                                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    <th class="px-4 py-3">{{ __('Waktu') }}</th>
                                    <th class="px-4 py-3">{{ __('Penerima') }}</th>
                                    <th class="px-4 py-3">{{ __('Kanal') }}</th>
                                    <th class="px-4 py-3">{{ __('Status') }}</th>
                                    <th class="px-4 py-3">{{ __('Keterangan') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($logs as $log)
                                    <tr wire:key="notification-log-{{ $log->id }}">
                                        <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400">
                                            {{ $log->sent_at?->format('d M Y H:i') ?? $log->created_at?->format('d M Y H:i') }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                            {{ $log->user?->name ?? $log->user_uuid }}
                                        </td>
                                        <td class="px-4 py-3 text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            {{ $log->channel }}
                                        </td>
                                        <td class="px-4 py-3">
                                            @php
                                                $badge = match ($log->status) {
                                                    'sent' => 'bg-teal-50 text-teal-700 border-teal-100 dark:bg-teal-900/20 dark:text-teal-400 dark:border-teal-800/40',
                                                    'failed' => 'bg-rose-50 text-rose-700 border-rose-100 dark:bg-rose-900/20 dark:text-rose-400 dark:border-rose-800/40',
                                                    default => 'bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-800/40',
                                                };
                                            @endphp
                                            <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-medium {{ $badge }}">
                                                {{ match ($log->status) {
                                                    'sent' => __('Terkirim'),
                                                    'failed' => __('Gagal'),
                                                    default => __('Antre'),
                                                } }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                                            {{ $log->error ?? '—' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                            {{ __('Belum ada notifikasi yang dikirim.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
