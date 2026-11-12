<div class="space-y-4">
    <x-core::breadcrumb :active="__('Pengaturan Pelatihan')" />

    <x-core::page-header
        :title="__('Pengaturan Pelatihan')"
        :subtitle="__('Kredensial AI dan template notifikasi untuk tenant ini.')">
        <x-slot name="action">
            <div class="flex flex-col items-start gap-2 sm:flex-row sm:items-center sm:gap-4">
                @if($updatedAt)
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('Terakhir diperbarui :time', ['time' => $updatedAt->diffForHumans()]) }}
                    </span>
                @endif

                @if($canUpdate)
                    <x-core::button type="submit" form="pelatihan-setting-form" label="{{ __('Simpan Pengaturan') }}">
                        <x-slot name="icon">
                            <x-lucide-save class="h-4 w-4" />
                        </x-slot>
                    </x-core::button>
                @endif
            </div>
        </x-slot>
    </x-core::page-header>

    <x-core::alert variant="info" :title="__('Kredensial tidak pernah ditampilkan lagi')">
        {{ __('Gemini API Key disimpan terenkripsi dan tidak dikirim balik ke halaman ini. Isi kolomnya hanya untuk mengganti kunci yang sudah tersimpan.') }}
    </x-core::alert>

    <form id="pelatihan-setting-form" wire:submit="save" class="max-w-4xl space-y-4">
        {{-- Analitik AI --}}
        <x-core::page-container>
            <div class="flex flex-col gap-3 border-b border-gray-200 pb-4 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        <x-lucide-sparkles class="size-4" />
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Analitik AI') }}</h2>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('Dipakai untuk mengelompokkan permintaan pelatihan menjadi klaster.') }}
                        </p>
                    </div>
                </div>

                <x-core::badge :color="$hasApiKey ? 'emerald' : 'amber'"
                    :label="$hasApiKey ? __('Kunci terisi') : __('Kunci belum diisi')" />
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <x-core::label :value="__('Model Gemini')" for="gemini_model" />
                    <x-core::input id="gemini_model" type="text" wire:model="gemini_model"
                        :disabled="! $canUpdate" />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('Nama model sesuai API Gemini, misalnya gemini-3.1-flash-lite.') }}
                    </p>
                    <x-core::input-error for="gemini_model" class="mt-1" />
                </div>

                <div>
                    <x-core::label :value="__('Gemini API Key')" for="password" />
                    <x-core::input usePasswordField wire:model="gemini_api_key"
                        :disabled="! $canUpdate"
                        placeholder="{{ $hasApiKey ? __('•••• tersimpan, isi untuk mengganti') : __('Belum diisi') }}" />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ $hasApiKey
                            ? __('Kosongkan bila tidak ingin mengganti kunci yang tersimpan.')
                            : __('Wajib diisi sebelum analisis AI bisa dijalankan.') }}
                    </p>
                    <x-core::input-error for="gemini_api_key" class="mt-1" />
                </div>
            </div>
        </x-core::page-container>

        {{-- Template Notifikasi --}}
        <x-core::page-container>
            <div class="flex items-center gap-3 border-b border-gray-200 pb-4 dark:border-gray-700">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                    <x-lucide-mail class="size-4" />
                </div>
                <div class="min-w-0">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Template Notifikasi') }}</h2>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('Pesan yang dikirim ke peserta setelah program diterbitkan.') }}
                    </p>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2">
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('Placeholder:') }}</span>
                @foreach(['{nama}', '{program}', '{cluster}', '{tahun}', '{quarter}'] as $placeholder)
                    <x-core::badge :label="$placeholder" />
                @endforeach
            </div>

            <div class="mt-4 space-y-4">
                <div>
                    <x-core::label :value="__('Subjek Email')" for="email_subject" />
                    <x-core::input id="email_subject" type="text" wire:model="email_subject"
                        :disabled="! $canUpdate" />
                    <x-core::input-error for="email_subject" class="mt-1" />
                </div>

                <div>
                    <x-core::label :value="__('Isi Email')" for="email_body" />
                    <x-core::textarea id="email_body" rows="6" wire:model="email_body"
                        :disabled="! $canUpdate" />
                    <x-core::input-error for="email_body" class="mt-1" />
                </div>
            </div>

            <div class="mt-5 border-t border-gray-200 pt-4 dark:border-gray-700">
                <label class="flex cursor-pointer items-center gap-3">
                    <x-core::checkbox wire:model.live="whatsapp_enabled" :disabled="! $canUpdate" />
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Kirim notifikasi WhatsApp') }}</span>
                </label>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('Kalau mati, peserta hanya menerima email dan template WhatsApp disembunyikan.') }}
                </p>
                <x-core::input-error for="whatsapp_enabled" class="mt-1" />
            </div>

            @if($whatsapp_enabled)
                <div class="mt-4">
                    <x-core::label :value="__('Isi WhatsApp')" for="wa_body" />
                    <x-core::textarea id="wa_body" rows="4" wire:model="wa_body"
                        :disabled="! $canUpdate" />
                    <x-core::input-error for="wa_body" class="mt-1" />
                </div>
            @endif
        </x-core::page-container>
    </form>
</div>
