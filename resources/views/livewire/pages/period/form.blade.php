<div>
    <x-core::breadcrumb
        :items="[['label' => __('Periode Pelatihan'), 'route' => 'pelatihan.period.index']]"
        :active="$periodId ? __('Edit Periode') : __('Tambah Periode')" />

    <div class="mt-6 space-y-4">
        <x-core::page-header
            :title="$periodId ? __('Edit Periode') : __('Periode Baru')"
            :subtitle="__('Mengaktifkan periode ini akan menonaktifkan periode lain. Permintaan lama tetap tersimpan.')" />

        <div class="max-w-4xl">
            <x-core::page-container>
                <form wire:submit="save" class="space-y-5">
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                        <div class="md:col-span-2">
                            <x-core::input label="{{ __('Nama Periode') }}" wire:model="name" required />
                        </div>
                        <x-core::input type="number" label="{{ __('Tahun') }}" wire:model="year" required />
                    </div>

                    <x-core::textarea label="{{ __('Keterangan') }}" wire:model="description" rows="3" />

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <x-core::input type="date" label="{{ __('Mulai') }}" wire:model="starts_at" />
                        <x-core::input type="date" label="{{ __('Selesai') }}" wire:model="ends_at" />
                    </div>

                    @if(! $periodId)
                        <div class="space-y-2">
                            <x-core::select-dropdown
                                label="{{ __('Jadwal Analitik AI') }}"
                                model="ai_schedule"
                                :items="[
                                    ['label' => __('Tidak dijadwalkan'), 'value' => 'none'],
                                    ['label' => __('Mingguan'), 'value' => 'weekly'],
                                    ['label' => __('Bulanan'), 'value' => 'monthly'],
                                ]"
                                labelField="label"
                                valueField="value" />
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('Jadwal hanya dapat dipilih saat periode dibuat.') }}
                            </p>
                        </div>
                    @endif

                    <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                        <div>
                            <label for="active-toggle" class="cursor-pointer text-sm font-semibold text-gray-900 dark:text-white">
                                {{ __('Jadikan Periode Aktif') }}
                            </label>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('Formulir publik hanya membuka periode aktif') }}
                            </p>
                        </div>
                        <label for="active-toggle" class="relative inline-block h-6 w-12 cursor-pointer">
                            <input type="checkbox" id="active-toggle" wire:model="activate" class="peer sr-only" />
                            <span class="absolute inset-0 rounded-full bg-gray-300 transition-colors duration-300 peer-checked:bg-teal-500 dark:bg-gray-600"></span>
                            <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform duration-300 peer-checked:translate-x-6"></span>
                        </label>
                    </div>

                    <div class="flex items-center justify-between border-t border-gray-200 pt-5 dark:border-gray-700">
                        <x-core::secondary-button link href="{{ route('pelatihan.period.index') }}" label="{{ __('Batal') }}" />
                        <x-core::button type="submit" label="{{ __('Simpan Periode') }}">
                            <x-slot name="icon"><x-lucide-check class="h-4 w-4" /></x-slot>
                        </x-core::button>
                    </div>
                </form>
            </x-core::page-container>
        </div>
    </div>
</div>
