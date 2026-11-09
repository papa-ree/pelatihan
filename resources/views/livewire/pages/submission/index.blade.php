<div>
    <x-core::breadcrumb :active="__('Permintaan Pelatihan')" />

    <div class="mt-6 space-y-4">
        <x-core::page-header
            :title="__('Permintaan Pelatihan')"
            :subtitle="__('Data yang dikirim warga dan perusahaan melalui portal pelatihan.')" />

        <div class="max-w-xs">
            <x-core::select-dropdown
                label="{{ __('Periode') }}"
                model="periodId"
                :items="$periodOptions"
                labelField="label"
                valueField="value"
                placeholder="{{ __('Semua periode') }}" />
        </div>

        <livewire:core-shared-components::data-table
            wire:key="submission-table-{{ $periodId ?? 'all' }}"
            model="Bale\Pelatihan\Models\Submission"
            rowView="pelatihan::livewire.pages.submission.section.row"
            connectionResolver="Bale\Cms\Services\TenantConnectionService::resolveForQuery"
            :with="['user']"
            :constraints="$periodId ? ['period_id' => $periodId] : []"
            :columns="[
                ['key' => 'type', 'label' => __('Jenis'), 'sortable' => true],
                ['key' => 'status', 'label' => __('Status AI'), 'sortable' => true],
                ['key' => 'submitted_at', 'label' => __('Dikirim'), 'sortable' => true],
                ['key' => 'actions', 'label' => '', 'sortable' => false],
            ]"
            :searchable="['type', 'status']"
            sortField="submitted_at"
            sortDirection="desc"
            :perPage="20" />
    </div>
</div>
