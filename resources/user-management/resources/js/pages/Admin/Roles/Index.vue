<template>
  <AppLayout title="Roles">
    <template #actions>
      <DsgButton v-if="canCreate" :uri="route('admin.roles.create')" label="Add role" />
    </template>

    <div class="flex flex-1 flex-col overflow-hidden px-4 pb-4">
      <DsgTable
        ref="dsgTableRef"
        :fields="fields"
        :get-url="route('dsg-table', { tableName: 'roles' })"
        :filter-endpoint="route('dsg-table.filters', { tableName: 'roles' })"
        :has-header="false"
        class="flex-1"
      >
        <template #dsg-table--search-header>
          <DsgTableSearch name="term" @dsg-table:term-changed="handleTermChanged" />
        </template>

        <template #dsg-table--footer-left-extras="slotProps">
          <DsgTablePerPage :per-page="slotProps.perPage" />
        </template>
      </DsgTable>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { useTemplateRef } from "vue";
import { usePage } from "@inertiajs/vue3";
import { DsgTable, DsgTablePerPage, DsgTableSearch } from "@dsg/table";
import { DsgButton } from "@dsg";
import { route } from "ziggy-js";
import AppLayout from "@layouts/App.vue";

const props = defineProps<{ fields: Record<string, unknown>[] }>();
const permissions = usePage<{ auth: { permissions?: string[] } }>().props.auth.permissions ?? [];
const fields = props.fields;
const canCreate = permissions.includes("admin-portal-roles-permissions-create");
const dsgTableRef = useTemplateRef<InstanceType<typeof DsgTable>>("dsgTableRef");
const handleTermChanged = (term: string | null): void => {
  dsgTableRef.value?.termChanged?.(term);
};
</script>
