<div>
    <x-core::breadcrumb :active="__('Klaster Pelatihan')" />

    <div class="mt-6 space-y-4">
        <x-core::page-header
            :title="__('Klaster Pelatihan')"
            :subtitle="__('Hasil pengelompokan AI atas permintaan pelatihan. Setujui, tolak, atau gabungkan sebelum membuat program.')" />

        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="w-full max-w-xs">
                <x-core::select-dropdown
                    label="{{ __('Periode') }}"
                    model="periodId"
                    :items="$periodOptions"
                    labelField="label"
                    valueField="value"
                    placeholder="{{ __('Pilih periode') }}" />
            </div>

            @can('pelatihan.cluster.analyze')
                <x-core::button variant="success" wire:click="analyze" wire:loading.attr="disabled"
                    label="{{ __('Jalankan AI') }}">
                    <x-slot name="icon">
                        <x-lucide-sparkles class="h-4 w-4" wire:loading.remove wire:target="analyze" />
                        <x-lucide-loader-circle class="h-4 w-4 animate-spin" wire:loading wire:target="analyze" />
                    </x-slot>
                </x-core::button>
            @endcan
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-core::stat-card-gradient
                :label="__('Klaster Menunggu')"
                :value="$counts['pending']"
                icon="hourglass"
                tone="amber"
                :hint="__('Belum diputuskan')" />

            <x-core::stat-card-gradient
                :label="__('Klaster Disetujui')"
                :value="$counts['approved']"
                icon="check-check"
                tone="emerald"
                :hint="__('Siap dibuat program')" />

            <x-core::stat-card-gradient
                :label="__('Belum Dianalisis')"
                :value="$pendingAi"
                icon="clock"
                tone="indigo"
                :hint="__('Permintaan menunggu AI')" />
        </div>

        <x-core::table>
            <x-slot:thead>
                <tr class="text-left">
                    <x-core::table-th :label="__('Klaster')" />
                    <x-core::table-th :label="__('Kata Kunci')" class="hidden lg:table-cell" />
                    <x-core::table-th :label="__('Urgensi')" align="center" />
                    <x-core::table-th :label="__('Anggota')" align="center" />
                    <x-core::table-th :label="__('Status')" />
                    <x-core::table-th :label="__('Aksi')" />
                </tr>
            </x-slot:thead>
            <x-slot:tbody>
                    @forelse($clusters as $cluster)
                        <tr wire:key="cluster-row-{{ $cluster->id }}"
                            class="align-top transition-colors duration-150 hover:bg-gray-50/80 dark:hover:bg-gray-700/40">
                            <td class="px-4 py-3">
                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $cluster->name }}</div>
                                @if($cluster->sub_category)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $cluster->sub_category }}</div>
                                @endif
                                @if($cluster->summary)
                                    <p class="mt-1 max-w-xl text-xs text-gray-500 dark:text-gray-400">{{ $cluster->summary }}</p>
                                @endif
                            </td>

                            <td class="hidden px-4 py-3 lg:table-cell">
                                <div class="flex max-w-xs flex-wrap gap-1">
                                    @foreach(array_slice((array) $cluster->keywords, 0, 5) as $keyword)
                                        <span class="rounded-md bg-gray-100 px-1.5 py-0.5 text-[11px] text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ $keyword }}</span>
                                    @endforeach
                                </div>
                            </td>

                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex size-7 items-center justify-center rounded-full text-xs font-bold
                                    {{ $cluster->urgency_score >= 4 ? 'bg-rose-50 text-rose-700 dark:bg-rose-900/20 dark:text-rose-400' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                                    {{ $cluster->urgency_score }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-center text-sm tabular-nums text-gray-700 dark:text-gray-300">
                                {{ $cluster->submissions_count }}
                            </td>

                            <td class="px-4 py-3">
                                @php
                                    $badge = match ($cluster->status) {
                                        'approved' => 'bg-teal-50 text-teal-700 border-teal-100 dark:bg-teal-900/20 dark:text-teal-400 dark:border-teal-800/40',
                                        'rejected' => 'bg-rose-50 text-rose-700 border-rose-100 dark:bg-rose-900/20 dark:text-rose-400 dark:border-rose-800/40',
                                        'merged' => 'bg-gray-100 text-gray-600 border-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600',
                                        default => 'bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-800/40',
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-medium {{ $badge }}">
                                    {{ __(ucfirst($cluster->status)) }}
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                @if(in_array($cluster->status, ['pending', 'approved'], true))
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if($cluster->status === 'pending')
                                            @can('pelatihan.cluster.approve')
                                                <button type="button" wire:click="approve('{{ $cluster->id }}')"
                                                    class="rounded-md border border-teal-200 bg-teal-50 px-2 py-1 text-xs font-semibold text-teal-700 transition hover:bg-teal-100 dark:border-teal-800/40 dark:bg-teal-900/20 dark:text-teal-300">
                                                    {{ __('Setujui') }}
                                                </button>
                                            @endcan

                                            @can('pelatihan.cluster.reject')
                                                <button type="button" wire:click="reject('{{ $cluster->id }}')"
                                                    wire:confirm="{{ __('Tolak klaster ini? Permintaannya kembali ke antrean analisis.') }}"
                                                    class="rounded-md border border-rose-200 bg-rose-50 px-2 py-1 text-xs font-semibold text-rose-700 transition hover:bg-rose-100 dark:border-rose-800/40 dark:bg-rose-900/20 dark:text-rose-300">
                                                    {{ __('Tolak') }}
                                                </button>
                                            @endcan
                                        @endif

                                        @can('pelatihan.cluster.merge')
                                            <div class="flex items-center gap-1">
                                                <select wire:model="mergeTargets.{{ $cluster->id }}"
                                                    class="rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                                    <option value="">{{ __('Gabung ke...') }}</option>
                                                    @foreach($mergeOptions as $option)
                                                        @continue($option->id === $cluster->id)
                                                        <option value="{{ $option->id }}">{{ $option->name }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="button" wire:click="merge('{{ $cluster->id }}')"
                                                    class="rounded-md border border-gray-200 px-2 py-1 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700">
                                                    {{ __('Gabung') }}
                                                </button>
                                            </div>
                                        @endcan
                                    </div>
                                @elseif($cluster->status === 'merged')
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('Digabung ke') }} {{ $cluster->mergedInto?->name ?? '—' }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center">
                                <span class="mx-auto flex size-12 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                    <x-lucide-boxes class="size-6" />
                                </span>
                                <p class="mt-3 text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Belum ada klaster pada periode ini.') }}</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ __('Klik "Jalankan AI" untuk menganalisis permintaan yang masuk.') }}
                                </p>
                            </td>
                        </tr>
                    @endforelse
            </x-slot:tbody>
        </x-core::table>
    </div>
</div>
