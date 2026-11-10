<div>
    <x-core::breadcrumb :active="__('Masyarakat')" />

    <div class="mt-6 space-y-4">
        <x-core::page-header
            :title="__('Masyarakat')"
            :subtitle="__('Profil warga yang masuk melalui portal pelatihan. Terpisah dari User Management.')" />

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-core::stat-card-gradient
                :label="__('Total Warga')"
                :value="number_format($totalCitizens)"
                icon="users"
                tone="teal"
                :badge="__('Profil')" />

            <x-core::stat-card-gradient
                :label="__('Profil Lengkap')"
                :value="number_format($completeProfiles)"
                icon="user-check"
                tone="emerald"
                :hint="__('NIK dan data diri terisi')" />

            <x-core::stat-card-gradient
                :label="__('Belum Lengkap')"
                :value="number_format($incompleteProfiles)"
                icon="user-round-x"
                tone="amber"
                :hint="__('Belum mengisi profil')" />
        </div>

        <livewire:core-shared-components::data-table
            model="Bale\Pelatihan\Models\CitizenProfile"
            rowView="pelatihan::livewire.pages.citizen.section.row"
            connectionResolver="Bale\Cms\Services\TenantConnectionService::resolveForQuery"
            :with="['user']"
            :columns="[
                ['key' => 'sso_subject', 'label' => __('Profil'), 'sortable' => false],
                ['key' => 'completed_at', 'label' => __('Lengkap'), 'sortable' => true],
            ]"
            :searchable="['sso_subject']"
            searchPlaceholder="{{ __('Cari NIK 16 digit atau ID akun') }}"
            searchUsing="Bale\Pelatihan\Support\CitizenSearch::apply"
            sortField="created_at"
            sortDirection="desc"
            :perPage="20" />
    </div>
</div>
