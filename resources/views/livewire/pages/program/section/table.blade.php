<div>
    <livewire:core-shared-components::data-table
        model="Bale\Pelatihan\Models\Program"
        rowView="pelatihan::livewire.pages.program.section.row"
        connectionResolver="Bale\Cms\Services\TenantConnectionService::resolveForQuery"
        :with="['cluster']"
        :columns="[
            ['key' => 'name', 'label' => __('Program'), 'sortable' => true],
            ['key' => 'year', 'label' => __('Tahun'), 'sortable' => true],
            ['key' => 'quarter', 'label' => __('Quarter'), 'sortable' => true],
            ['key' => 'status', 'label' => __('Status'), 'sortable' => true],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ]"
        :searchable="['name']"
        sortField="created_at"
        sortDirection="desc"
        :perPage="20" />
</div>
