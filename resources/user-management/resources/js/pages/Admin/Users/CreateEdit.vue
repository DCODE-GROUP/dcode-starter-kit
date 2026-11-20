<template>
    <AppLayout :title="title">
        <form
            class="flex min-h-full flex-col justify-between gap-4"
            @submit.prevent="submit"
        >
            <ContentAside class="bg-gray-100">
                <template #content>
                    <SidePanel title="Basic information" theme="white">
                        <div class="flex flex-col gap-6">
                            <div class="grid gap-3 lg:grid-cols-3">
                                <DsgInput
                                    v-model="form.first_name"
                                    name="first_name"
                                    label="First name"
                                    :errors="form.errors.first_name"
                                    required-label
                                    size="sm"
                                />
                                <DsgInput
                                    v-model="form.middle_name"
                                    name="middle_name"
                                    label="Middle name"
                                    :errors="form.errors.middle_name"
                                    size="sm"
                                />
                                <DsgInput
                                    v-model="form.last_name"
                                    name="last_name"
                                    label="Last name"
                                    :errors="form.errors.last_name"
                                    required-label
                                    size="sm"
                                />
                            </div>
                            <DsgInput
                                v-model="form.email"
                                type="email"
                                name="email"
                                label="Email"
                                :errors="form.errors.email"
                                required-label
                                size="sm"
                            />
                            <div class="grid gap-3 lg:grid-cols-2">
                                <DsgInput
                                    v-model="form.password"
                                    type="password"
                                    name="password"
                                    label="Password"
                                    :errors="form.errors.password"
                                    :required-label="!user"
                                    size="sm"
                                />
                                <DsgInput
                                    v-model="form.password_confirmation"
                                    type="password"
                                    name="password_confirmation"
                                    label="Confirm password"
                                    :errors="form.errors.password_confirmation"
                                    :required-label="!user"
                                    size="sm"
                                />
                            </div>
                        </div>
                    </SidePanel>
                </template>

                <template #sidebar>
                    <SidePanel title="Profile photo" theme="white">
                        <ProfilePhotoUploader
                            v-model="form.profile_photo"
                            v-model:remove="form.remove_profile_photo"
                            :full-name="fullName"
                            :initial-url="profilePhotoUrl"
                        />
                        <p v-if="form.errors.profile_photo" class="mt-2 text-sm text-error-600">{{ form.errors.profile_photo }}</p>
                    </SidePanel>

                    <SidePanel title="Status" theme="white">
                        <DsgCheckbox
                            v-model="form.active"
                            label="Active"
                            size="sm"
                        />
                    </SidePanel>

                    <SidePanel
                        v-if="canManageRoles"
                        title="Roles"
                        theme="white"
                        description="Assign roles to control access to the application."
                    >
                        <DsgInputSelect
                            v-model="selectedRoles"
                            :options="roleOptions"
                            :errors="form.errors.role_ids"
                            :multiple="true"
                            :allow-empty="true"
                            searchable
                            size="sm"
                        />
                    </SidePanel>
                </template>
            </ContentAside>

            <PageFooterButtons
                :cancel-url="route('admin.users.index')"
                :save-label="user ? 'Save changes' : 'Create user'"
            />
        </form>
    </AppLayout>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import {
    DsgAlert,
    DsgButton,
    DsgCheckbox,
    DsgInput,
    DsgInputSelect,
} from "@dcodegroup-au/dsg-vue";
import { route } from "ziggy-js";
import AppLayout from "@layouts/App.vue";
import ContentAside from "@components/ContentAside.vue";
import PageFooterButtons from "@components/PageFooterButtons.vue";
import SidePanel from "@components/SidePanel.vue";
import ProfilePhotoUploader from "@components/ProfilePhotoUploader.vue";

interface Role {
    id: number;
    name: string;
}
interface User {
    id: number;
    first_name?: string;
    middle_name?: string;
    last_name?: string;
    email: string;
    active: boolean;
    roles?: Role[];
    profile_photo_path?: string | null;
}
const props = defineProps<{
    user: User | null;
    roles: Role[];
    title: string;
}>();
const page = usePage<{ auth: { permissions?: string[] } }>();
const canManageRoles = (page.props.auth.permissions ?? []).includes(
    "admin-portal-roles-permissions-update",
);
const user = props.user;
const fullName = [user?.first_name, user?.middle_name, user?.last_name].filter(Boolean).join(" ") || user?.email || "New user";
const profilePhotoUrl = user?.profile_photo_path
    ? (user.profile_photo_path.startsWith("http") ? user.profile_photo_path : `/storage/${user.profile_photo_path}`)
    : null;
const roleOptions = props.roles.map((role) => ({
    id: role.id,
    label: role.name,
}));
const form = useForm({
    first_name: user?.first_name ?? "",
    middle_name: user?.middle_name ?? "",
    last_name: user?.last_name ?? "",
    email: user?.email ?? "",
    password: "",
    password_confirmation: "",
    active: user?.active ?? true,
    role_ids: user?.roles?.map((role) => role.id) ?? ([] as number[]),
    profile_photo: null as File | null,
    remove_profile_photo: false,
});
const selectedRoles = computed({
    get: () => roleOptions.filter((role) => form.role_ids.includes(role.id)),
    set: (selected: typeof roleOptions | null) => {
        form.role_ids = selected?.map((role) => role.id) ?? [];
    },
});

const submit = (): void => {
    const options = {
        onError: (): void => {
            DsgAlert.error(
                "Save failed",
                "The user could not be saved. Please review the form and try again.",
            );
        },
    };

    if (user) {
        form.put(route("admin.users.update", user.id), { ...options, forceFormData: true });
    } else {
        form.post(route("admin.users.store"), { ...options, forceFormData: true });
    }
};
</script>
