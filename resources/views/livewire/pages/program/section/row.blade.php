<tr wire:key="program-row-{{ $record->id }}"
    class="hover:bg-gray-50/80 dark:hover:bg-gray-800/50 transition-colors duration-150">

    <td class="px-4 py-3.5 w-full max-w-0 sm:max-w-none sm:w-auto">
        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
            {{ $record->name }}
        </div>
        <p class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400">
            {{ $record->cluster?->name ?? '—' }}
        </p>
    </td>

    <td class="px-4 py-3.5 hidden md:table-cell">
        <div class="text-sm text-gray-700 dark:text-gray-300 tabular-nums">{{ $record->year }}</div>
    </td>

    <td class="px-4 py-3.5 hidden md:table-cell">
        <div class="text-sm text-gray-700 dark:text-gray-300 tabular-nums">Q{{ $record->quarter }}</div>
    </td>

    <td class="px-4 py-3.5 hidden sm:table-cell">
        @if($record->status === 'published')
            <span class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-xs font-medium bg-teal-50 text-teal-700 border border-teal-100 dark:bg-teal-900/20 dark:text-teal-400 dark:border-teal-800/40">
                <span class="inline-block h-1.5 w-1.5 rounded-full bg-teal-500"></span>
                {{ __('Terbit') }}
            </span>
        @else
            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium bg-amber-50 text-amber-700 border border-amber-100 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-800/40">
                {{ __('Draf') }}
            </span>
        @endif
    </td>

    <td class="px-4 py-3.5 whitespace-nowrap w-px">
        <div class="flex items-center gap-2">
            @if($record->status === 'published')
                @can('pelatihan.notification.send')
                    <button type="button"
                        x-data="{ busy: false }"
                        x-on:click="busy = true; $wire.dispatch('sendProgramNotification', { id: '{{ $record->id }}' }); setTimeout(() => busy = false, 4000)"
                        x-bind:disabled="busy"
                        x-bind:class="busy ? 'opacity-60 cursor-progress' : ''"
                        title="{{ __('Kirim notifikasi') }}"
                        class="inline-flex items-center gap-1.5 rounded-md border border-teal-200 bg-teal-50 px-2 py-1 text-xs font-semibold text-teal-700 transition hover:bg-teal-100 dark:border-teal-800/40 dark:bg-teal-900/20 dark:text-teal-300">
                        <x-lucide-send class="size-3" />
                        {{ __('Kirim') }}
                    </button>
                @endcan
            @endif

            @if(auth()->user()->can('pelatihan.program.update') || auth()->user()->can('pelatihan.program.delete'))
                <livewire:core.shared-components.item-actions
                    :editUrl="auth()->user()->can('pelatihan.program.update') ? route('pelatihan.program.edit', $record->id) : null"
                    :deleteId="auth()->user()->can('pelatihan.program.delete') ? $record->id : null"
                    deleteEvent="deletePelatihanProgram"
                    confirmMessage="{{ __('Yakin ingin menghapus program ini?') }}"
                    wire:key="item-actions-{{ $record->id }}" />
            @endif
        </div>
    </td>
</tr>
