<tr wire:key="period-row-{{ $record->id }}"
    class="hover:bg-gray-50/80 dark:hover:bg-gray-800/50 transition-colors duration-150">

    <td class="px-4 py-3.5 w-full max-w-0 sm:max-w-none sm:w-auto">
        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
            {{ $record->name }}
        </div>
        @if($record->description)
            <p class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-md mt-0.5">
                {{ $record->description }}
            </p>
        @endif
    </td>

    <td class="px-4 py-3.5 hidden md:table-cell">
        <div class="text-sm text-gray-700 dark:text-gray-300 tabular-nums">{{ $record->year }}</div>
    </td>

    <td class="px-4 py-3.5 hidden lg:table-cell">
        <div class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
            {{ $record->ai_schedule }}
        </div>
    </td>

    <td class="px-4 py-3.5 hidden sm:table-cell">
        @if($record->is_active)
            <span class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-xs font-medium bg-teal-50 text-teal-700 border border-teal-100 dark:bg-teal-900/20 dark:text-teal-400 dark:border-teal-800/40">
                <span class="inline-block h-1.5 w-1.5 rounded-full bg-teal-500"></span>
                {{ __('Aktif') }}
            </span>
        @else
            @can('pelatihan.period.update')
                <button type="button"
                    x-data="{ busy: false }"
                    x-on:click="busy = true; $wire.dispatch('activatePeriod', { id: '{{ $record->id }}' }); setTimeout(() => busy = false, 4000)"
                    x-bind:disabled="busy"
                    x-bind:class="busy ? 'opacity-60 cursor-progress' : ''"
                    class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-xs font-medium bg-gray-50 text-gray-600 border border-gray-200 hover:bg-teal-50 hover:text-teal-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700">
                    <x-lucide-power class="size-3" />
                    {{ __('Aktifkan') }}
                </button>
            @else
                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium bg-gray-50 text-gray-600 border border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700">
                    {{ __('Tidak aktif') }}
                </span>
            @endcan
        @endif
    </td>

    <td class="px-4 py-3.5 whitespace-nowrap w-px">
        <div class="flex items-center gap-2">
            @can('pelatihan.cluster.analyze')
                <button type="button"
                    x-data="{ busy: false }"
                    x-on:click="busy = true; $wire.dispatch('analyzePeriod', { id: '{{ $record->id }}' }); setTimeout(() => busy = false, 4000)"
                    x-bind:disabled="busy"
                    x-bind:class="busy ? 'opacity-60 cursor-progress' : ''"
                    title="{{ __('Jalankan analisis AI') }}"
                    class="inline-flex items-center gap-1.5 rounded-md border border-teal-200 bg-teal-50 px-2 py-1 text-xs font-semibold text-teal-700 transition hover:bg-teal-100 dark:border-teal-800/40 dark:bg-teal-900/20 dark:text-teal-300">
                    <x-lucide-sparkles class="size-3" />
                    {{ __('AI') }}
                </button>
            @endcan

            @can('pelatihan.period.view')
                <a href="{{ route('pelatihan.period.export', [$record->id, 'xlsx']) }}"
                    title="{{ __('Unduh Excel') }}"
                    class="inline-flex items-center gap-1.5 rounded-md border border-emerald-200 bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100 dark:border-emerald-800/40 dark:bg-emerald-900/20 dark:text-emerald-300">
                    <x-lucide-file-spreadsheet class="size-3" />
                    XLSX
                </a>
                <a href="{{ route('pelatihan.period.export', [$record->id, 'pdf']) }}"
                    title="{{ __('Unduh PDF') }}"
                    class="inline-flex items-center gap-1.5 rounded-md border border-rose-200 bg-rose-50 px-2 py-1 text-xs font-semibold text-rose-700 transition hover:bg-rose-100 dark:border-rose-800/40 dark:bg-rose-900/20 dark:text-rose-300">
                    <x-lucide-file-text class="size-3" />
                    PDF
                </a>
            @endcan

            @if(auth()->user()->can('pelatihan.period.update') || auth()->user()->can('pelatihan.period.delete'))
                <livewire:core.shared-components.item-actions
                    :editUrl="auth()->user()->can('pelatihan.period.update') ? route('pelatihan.period.edit', $record->id) : null"
                    :deleteId="auth()->user()->can('pelatihan.period.delete') ? $record->id : null"
                    deleteEvent="deletePelatihanPeriod"
                    confirmMessage="{{ __('Yakin ingin menghapus periode ini?') }}"
                    wire:key="item-actions-{{ $record->id }}" />
            @endif
        </div>
    </td>
</tr>
