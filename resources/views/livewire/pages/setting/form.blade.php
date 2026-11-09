<div>
    <x-core::breadcrumb :active="__('Pengaturan Pelatihan')" />

    <div class="mt-6 space-y-4">
        <x-core::page-header
            :title="__('Pengaturan Pelatihan')"
            :subtitle="__('Kredensial Gemini dan template notifikasi global.')" />

        <div class="max-w-4xl">
            <x-core::page-container>
                <form wire:submit="save" class="space-y-5">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Analitik AI') }}</h3>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('Kredensial hanya dibaca oleh bale/pelatihan dan disimpan terenkripsi.') }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <x-core::input label="{{ __('Model Gemini') }}" wire:model="gemini_model" required />
                        <x-core::input
                            usePasswordField
                            label="{{ __('Gemini API Key') }}"
                            wire:model="gemini_api_key"
                            placeholder="{{ $hasApiKey ? __('•••• tersimpan, isi untuk mengganti') : __('Belum diisi') }}" />
                    </div>

                    <div class="border-t border-gray-200 pt-4 dark:border-gray-700">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Template Notifikasi') }}</h3>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('Placeholder: {nama}, {program}, {cluster}, {tahun}, {quarter}') }}
                        </p>
                    </div>

                    <x-core::input label="{{ __('Subjek Email') }}" wire:model="email_subject" required />
                    <x-core::textarea label="{{ __('Isi Email') }}" wire:model="email_body" rows="6" required />
                    <x-core::textarea label="{{ __('Isi WhatsApp') }}" wire:model="wa_body" rows="4" required />

                    <div class="flex justify-end border-t border-gray-200 pt-5 dark:border-gray-700">
                        <x-core::button type="submit" label="{{ __('Simpan Pengaturan') }}">
                            <x-slot name="icon"><x-lucide-check class="h-4 w-4" /></x-slot>
                        </x-core::button>
                    </div>
                </form>
            </x-core::page-container>
        </div>
    </div>
</div>
