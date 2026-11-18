<template>
  <AppLayout :title="title">
    <div class="max-w-3xl rounded-lg bg-white p-6 shadow-sm">
      <form class="space-y-5" @submit.prevent="submit">
        <div class="grid gap-4 md:grid-cols-3">
          <DsgInput v-model="form.first_name" name="first_name" label="First name" :errors="form.errors.first_name" />
          <DsgInput v-model="form.middle_name" name="middle_name" label="Middle name" :errors="form.errors.middle_name" />
          <DsgInput v-model="form.last_name" name="last_name" label="Last name" :errors="form.errors.last_name" />
        </div>
        <DsgInput v-model="form.email" type="email" name="email" label="Email" :errors="form.errors.email" />
        <div class="grid gap-4 md:grid-cols-2">
          <DsgInput v-model="form.password" type="password" name="password" label="Password" :errors="form.errors.password" :required="!user" />
          <DsgInput v-model="form.password_confirmation" type="password" name="password_confirmation" label="Confirm password" :errors="form.errors.password_confirmation" :required="!user" />
        </div>
        <label class="flex items-center gap-2 text-sm"><input v-model="form.active" type="checkbox" /> Active</label>
        <fieldset v-if="canManageRoles" class="space-y-2">
          <legend class="font-semibold">Roles</legend>
          <label v-for="role in roles" :key="role.id" class="flex items-center gap-2 text-sm">
            <input v-model="form.role_ids" type="checkbox" :value="role.id" /> {{ role.name }}
          </label>
        </fieldset>
        <div class="flex justify-end gap-3"><DsgButton :uri="route('admin.users.index')" label="Cancel" theme="neutral-link" /><DsgButton type="submit" :label="user ? 'Save changes' : 'Create user'" :disabled="form.processing" /></div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { useForm, usePage } from "@inertiajs/vue3";
import { DsgButton, DsgInput } from "@dcodegroup-au/dsg-vue";
import { route } from "ziggy-js";
import AppLayout from "@layouts/App.vue";

interface Role { id: number; name: string }
interface User { id: number; first_name?: string; middle_name?: string; last_name?: string; email: string; active: boolean; roles?: Role[] }
const props = defineProps<{ user: User | null; roles: Role[]; title: string }>();
const page = usePage<{ auth: { permissions?: string[] } }>();
const canManageRoles = (page.props.auth.permissions ?? []).includes("admin-portal-roles-permissions-update");
const user = props.user;
const form = useForm({
  first_name: user?.first_name ?? "",
  middle_name: user?.middle_name ?? "",
  last_name: user?.last_name ?? "",
  email: user?.email ?? "",
  password: "",
  password_confirmation: "",
  active: user?.active ?? true,
  role_ids: user?.roles?.map((role) => role.id) ?? [] as number[],
});

const submit = (): void => user ? form.put(route("admin.users.update", user.id)) : form.post(route("admin.users.store"));
</script>
