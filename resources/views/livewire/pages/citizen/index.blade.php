<div>
    <x-core::breadcrumb :active="__('Masyarakat')" />

    <div class="mt-6 space-y-4">
        <x-core::page-header
            :title="__('Masyarakat')"
            :subtitle="__('Profil warga yang masuk melalui portal pelatihan. Terpisah dari User Management.')" />

        <form wire:submit="search"
            class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:flex-row sm:items-end">
            <div class="flex-1">
                <x-core::input label="{{ __('Cari NIK (16 digit, exact match)') }}" wire:model="nik" maxlength="16" />
            </div>
            <div class="flex gap-2">
                <x-core::button type="submit" label="{{ __('Cari') }}">
                    <x-slot name="icon"><x-lucide-search class="h-4 w-4" /></x-slot>
                </x-core::button>
                @if($nikHash)
                    <x-core::secondary-button type="button" wire:click="clear" label="{{ __('Reset') }}" />
                @endif
            </div>
        </form>

        <livewire:core-shared-components::data-table
            wire:key="citizen-table-{{ $nikHash ?? 'all' }}"
            model="Bale\Pelatihan\Models\CitizenProfile"
            rowView="pelatihan::livewire.pages.citizen.section.row"
            connectionResolver="Bale\Cms\Services\TenantConnectionService::resolveForQuery"
            :with="['user']"
            :constraints="$nikHash ? ['nik_hash' => $nikHash] : []"
            :columns="[
                ['key' => 'sso_subject', 'label' => __('Profil'), 'sortable' => false],
                ['key' => 'completed_at', 'label' => __('Lengkap'), 'sortable' => true],
            ]"
            :searchable="['sso_subject']"
            sortField="created_at"
            sortDirection="desc"
            :perPage="20" />
    </div>
</div>
