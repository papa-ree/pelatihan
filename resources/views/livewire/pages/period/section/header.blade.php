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
