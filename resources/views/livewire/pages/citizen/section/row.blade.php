<tr wire:key="citizen-row-{{ $record->id }}"
    class="hover:bg-gray-50/80 dark:hover:bg-gray-800/50 transition-colors duration-150">

    <td class="px-4 py-3.5 w-full max-w-0 sm:max-w-none sm:w-auto">
        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
            {{ $record->user?->name ?? __('Tanpa nama') }}
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400">
            {{ $record->user?->email }} · NIK {{ $record->maskedNik() ?? '—' }}
        </div>
    </td>

    <td class="px-4 py-3.5 hidden sm:table-cell">
        <div class="text-sm text-gray-700 dark:text-gray-300">
            {{ $record->completed_at ?? '—' }}
        </div>
    </td>
</tr>
