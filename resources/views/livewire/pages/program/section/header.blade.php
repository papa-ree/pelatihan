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
