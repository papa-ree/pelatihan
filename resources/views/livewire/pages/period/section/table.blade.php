<div>
    <livewire:core-shared-components::data-table
        model="Bale\Pelatihan\Models\Period"
        rowView="pelatihan::livewire.pages.period.section.row"
        connectionResolver="Bale\Cms\Services\TenantConnectionService::resolveForQuery"
        :columns="[
            ['key' => 'name', 'label' => __('Periode'), 'sortable' => true],
            ['key' => 'year', 'label' => __('Tahun'), 'sortable' => true],
            ['key' => 'ai_schedule', 'label' => __('Jadwal AI'), 'sortable' => false],
            ['key' => 'is_active', 'label' => __('Status'), 'sortable' => true],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ]"
        :searchable="['name', 'description']"
        sortField="year"
        sortDirection="desc"
        :perPage="20" />
</div>
