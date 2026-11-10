<div class="space-y-4">
    <x-core::page-header
        :title="__('Periode Pelatihan')"
        :subtitle="__('Hanya satu periode yang aktif. Periode baru menonaktifkan periode lama tanpa menghapus permintaan.')"
    >
        <x-slot name="action">
            @can('pelatihan.period.create')
                <x-core::button link href="{{ route('pelatihan.period.create') }}" label="{{ __('Tambah Periode') }}">
                    <x-slot name="icon">
                        <x-lucide-plus class="w-5 h-5" />
                    </x-slot>
                </x-core::button>
            @endcan
        </x-slot>
    </x-core::page-header>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-core::stat-card-gradient
            :label="__('Total Periode')"
            :value="number_format($totalPeriods)"
            icon="calendar-range"
            tone="teal"
            :hint="__('Seluruh periode tersimpan')" />

        <x-core::stat-card-gradient
            :label="__('Periode Aktif')"
            :value="number_format($activePeriods)"
            icon="calendar-check"
            tone="emerald"
            :hint="__('Dipakai portal pelatihan')" />

        <x-core::stat-card-gradient
            :label="__('Permintaan')"
            :value="number_format($totalSubmissions)"
            icon="inbox"
            tone="indigo"
            :hint="__('Semua periode')" />

        <x-core::stat-card-gradient
            :label="__('Program Terbit')"
            :value="number_format($publishedPrograms)"
            icon="book-open-check"
            tone="sky"
            :hint="__('Sudah diumumkan')" />
    </div>
</div>
