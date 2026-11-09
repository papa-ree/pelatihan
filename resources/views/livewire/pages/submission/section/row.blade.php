<tr wire:key="submission-row-{{ $record->id }}"
    class="hover:bg-gray-50/80 dark:hover:bg-gray-800/50 transition-colors duration-150">

    <td class="px-4 py-3.5 w-full max-w-0 sm:max-w-none sm:w-auto">
        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
            {{ $record->user?->name ?? __('Tanpa nama') }}
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400">
            {{ ucfirst($record->type) }} · {{ $record->user?->email }}
        </div>
    </td>

    <td class="px-4 py-3.5 hidden md:table-cell">
        <div class="text-sm text-gray-700 dark:text-gray-300">{{ ucfirst($record->type) }}</div>
    </td>

    <td class="px-4 py-3.5 hidden lg:table-cell">
        @use('Bale\Pelatihan\Models\Submission')
        @php
            $status = match ($record->status) {
                Submission::STATUS_PROCESSED => [
                    'label' => __('Selesai'),
                    'class' => 'bg-teal-50 text-teal-700 border-teal-100 dark:bg-teal-900/20 dark:text-teal-400 dark:border-teal-800/40',
                ],
                Submission::STATUS_FAILED => [
                    'label' => __('Gagal'),
                    'class' => 'bg-rose-50 text-rose-700 border-rose-100 dark:bg-rose-900/20 dark:text-rose-400 dark:border-rose-800/40',
                ],
                default => [
                    'label' => __('Menunggu AI'),
                    'class' => 'bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-800/40',
                ],
            };
        @endphp
        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium border {{ $status['class'] }}">
            {{ $status['label'] }}
        </span>
    </td>

    <td class="px-4 py-3.5 hidden sm:table-cell">
        <div class="text-sm text-gray-700 dark:text-gray-300">{{ $record->submitted_at }}</div>
    </td>

    <td class="px-4 py-3.5 whitespace-nowrap w-px">
        <a href="{{ route('pelatihan.submission.show', $record->id) }}" wire:navigate
            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
            <x-lucide-eye class="size-3.5" />
            {{ __('Detail') }}
        </a>
    </td>
</tr>
