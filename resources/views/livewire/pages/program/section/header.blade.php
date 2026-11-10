<div class="space-y-4">
    <x-core::page-header
        :title="__('Program Pelatihan')"
        :subtitle="__('Program hanya dapat dibuat dari klaster yang disetujui. Kirim notifikasi setelah program terbit.')"
    >
        <x-slot name="action">
            @can('pelatihan.program.create')
                <x-core::button link href="{{ route('pelatihan.program.create') }}" label="{{ __('Tambah Program') }}">
                    <x-slot name="icon">
                        <x-lucide-plus class="w-5 h-5" />
                    </x-slot>
                </x-core::button>
            @endcan
        </x-slot>
    </x-core::page-header>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-core::stat-card-gradient
            :label="__('Total Program')"
            :value="number_format($totalPrograms)"
            icon="book-open"
            tone="teal"
            :hint="__('Draf dan terbit')" />

        <x-core::stat-card-gradient
            :label="__('Terbit')"
            :value="number_format($publishedPrograms)"
            icon="book-open-check"
            tone="emerald"
            :hint="__('Sudah diumumkan')" />

        <x-core::stat-card-gradient
            :label="__('Draf')"
            :value="number_format($draftPrograms)"
            icon="file-pen-line"
            tone="amber"
            :hint="__('Belum diumumkan')" />
    </div>
</div>
