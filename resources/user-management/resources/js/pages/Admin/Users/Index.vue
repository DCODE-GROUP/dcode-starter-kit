<template>
    <AppLayout title="Users">
        <template #actions>
            <DsgButton
                v-if="canCreate"
                :uri="route('admin.users.create')"
                label="Add user"
            />
        </template>

        <div class="flex flex-1 flex-col overflow-hidden pb-4">
            <DsgTable
                ref="dsgTableRef"
                :fields="fields"
                :get-url="route('dsg-table', { tableName: 'users' })"
                :filter-endpoint="
                    route('dsg-table.filters', { tableName: 'users' })
                "
                :has-header="false"
                class="flex-1"
            >
                <template #dsg-table--search-header>
                    <DsgTableSearch
                        name="term"
                        @dsg-table:term-changed="handleTermChanged"
                    />
                </template>

                <template #dsg-field--full_name="slotProps">
                    <span class="font-medium text-gray-900">{{
                        slotProps.rowData.full_name
                    }}</span>
                </template>

                <template #dsg-field--roles="slotProps">
                    <div
                        class="flex flex-wrap items-center justify-start gap-1"
                    >
                        <DsgBadge
                            v-for="role in slotProps.rowData.roles"
                            :key="role.id"
                            :label="role.name"
                            theme="badge-color"
                            theme-color="gray"
                            size="sm"
                        />
                        <span v-if="!slotProps.rowData.roles.length">—</span>
                    </div>
                </template>

                <template #dsg-field--active="slotProps">
                    <DsgBadge
                        :label="
                            slotProps.rowData.active ? 'Active' : 'Inactive'
                        "
                        theme="badge-color"
                        :theme-color="
                            slotProps.rowData.active ? 'green' : 'gray'
                        "
                        size="sm"
                    />
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
import { DsgBadge, DsgButton } from "@dsg";
import { route } from "ziggy-js";
import AppLayout from "@layouts/App.vue";
import { mountedStatusToast } from "@composables/StatusToast";

const props = defineProps<{
    fields: Record<string, unknown>[];
    status?: string;
}>();
const page = usePage<{ auth: { permissions?: string[] }; status?: string }>();
mountedStatusToast(props.status ?? page.props.status);
const permissions = page.props.auth.permissions ?? [];
const fields = props.fields;
const canCreate = permissions.includes("admin-portal-users-create");
const dsgTableRef =
    useTemplateRef<InstanceType<typeof DsgTable>>("dsgTableRef");
const handleTermChanged = (term: string | null): void => {
    dsgTableRef.value?.termChanged?.(term);
};
</script>
