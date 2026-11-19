<template>
    <AppLayout :title="title">
        <form
            class="flex min-h-full flex-col justify-between gap-4"
            @submit.prevent="submit"
        >
            <ContentAside class="bg-gray-100">
                <template #content>
                    <SidePanel title="Role details" theme="white">
                        <DsgInput
                            v-model="form.name"
                            name="name"
                            label="Name"
                            :errors="form.errors.name"
                            required-label
                            size="sm"
                        />
                    </SidePanel>
                </template>

                <template #sidebar>
                    <SidePanel
                        title="Permissions"
                        description="Choose the permissions granted to this role."
                        theme="white"
                    >
                        <div class="flex flex-col gap-3">
                            <DsgCheckbox
                                v-for="permission in permissions"
                                :key="permission"
                                :model-value="
                                    form.permissions.includes(permission)
                                "
                                :label="permission"
                                size="sm"
                                @update:model-value="
                                    togglePermission(permission, $event)
                                "
                            />
                        </div>
                    </SidePanel>
                </template>
            </ContentAside>

            <PageFooterButtons
                :cancel-url="route('admin.roles.index')"
                :save-label="role ? 'Save changes' : 'Create role'"
            />
        </form>
    </AppLayout>
</template>

<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import { DsgAlert, DsgCheckbox, DsgInput } from "@dcodegroup-au/dsg-vue";
import { route } from "ziggy-js";
import AppLayout from "@layouts/App.vue";
import ContentAside from "@components/ContentAside.vue";
import PageFooterButtons from "@components/PageFooterButtons.vue";
import SidePanel from "@components/SidePanel.vue";

interface Role {
    id: number;
    name: string;
    permissions?: string[];
}
const props = defineProps<{
    role: Role | null;
    permissions: string[];
    title: string;
}>();
const role = props.role;
const permissions = props.permissions;
const form = useForm({
    name: role?.name ?? "",
    permissions: role?.permissions ?? ([] as string[]),
});

const togglePermission = (permission: string, selected: boolean): void => {
    form.permissions = selected
        ? [...new Set([...form.permissions, permission])]
        : form.permissions.filter((item) => item !== permission);
};

const submit = (): void => {
    const options = {
        onError: (): void => {
            DsgAlert.error(
                "Save failed",
                "The role could not be saved. Please review the form and try again.",
            );
        },
    };

    if (role) {
        form.put(route("admin.roles.update", role.id), options);
    } else {
        form.post(route("admin.roles.store"), options);
    }
};
</script>
