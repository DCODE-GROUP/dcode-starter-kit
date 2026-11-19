<template>
    <AppLayout title="Roles">
        <template #actions>
            <DsgButton
                v-if="canCreate"
                :uri="route('admin.roles.create')"
                label="Add role"
            />
        </template>

        <div class="flex flex-1 flex-col overflow-hidden pb-4">
            <DsgTable
                ref="dsgTableRef"
                :fields="fields"
                :get-url="route('dsg-table', { tableName: 'roles' })"
                :filter-endpoint="
                    route('dsg-table.filters', { tableName: 'roles' })
                "
                :has-header="false"
                class="flex-1"
                @dsg-table:action-delete="deleteAction"
            >
                <template #dsg-table--search-header>
                    <DsgTableSearch
                        name="term"
                        @dsg-table:term-changed="handleTermChanged"
                    />
                </template>

                <template #dsg-table--footer-left-extras="slotProps">
                    <DsgTablePerPage :per-page="slotProps.perPage" />
                </template>
            </DsgTable>
        </div>

        <DsgModal
            v-if="selectedRoleToDelete"
            ref="deleteRoleModalRef"
            :title="`Delete role ${selectedRoleToDelete.name}`"
            description="Are you sure you want to delete this role?"
            :primary-button="{ label: 'Delete', theme: 'destructive' }"
            :secondary-button="{ label: 'Cancel', theme: 'gray-secondary' }"
            :has-closed-button="false"
            footer-type="horizontal-right"
            @primary-button-clicked="handleDeleteRole"
            @secondary-button-clicked="handleCancelDeleteRole"
        />
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, useTemplateRef } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { DsgTable, DsgTablePerPage, DsgTableSearch } from "@dsg/table";
import { DsgAlert, DsgButton, DsgModal } from "@dsg";
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
const canCreate = permissions.includes("admin-portal-roles-permissions-create");
const dsgTableRef =
    useTemplateRef<InstanceType<typeof DsgTable>>("dsgTableRef");
const selectedRoleToDelete = ref<{ id: number; name: string } | null>(null);
const deleteRoleModalRef = useTemplateRef<typeof DsgModal>("deleteRoleModalRef");

const deleteAction = (event: Event, data: { id: number; name: string }): void => {
    event.preventDefault();
    selectedRoleToDelete.value = data;

    setTimeout(() => deleteRoleModalRef.value?.open(), 1);
};

const handleCancelDeleteRole = (): void => {
    deleteRoleModalRef.value?.close();
    selectedRoleToDelete.value = null;
};

const handleDeleteRole = (): void => {
    if (! selectedRoleToDelete.value) {
        return;
    }

    const role = selectedRoleToDelete.value;

    router.delete(route("admin.roles.destroy", role.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            deleteRoleModalRef.value?.close();
            selectedRoleToDelete.value = null;
            dsgTableRef.value?.deleteRowTable(role);
            DsgAlert.success("Success", "Role deleted successfully.");
        },
        onError: () => DsgAlert.error("Error", "The role could not be deleted."),
    });
};

const handleTermChanged = (term: string | null): void => {
    dsgTableRef.value?.termChanged?.(term);
};
</script>
