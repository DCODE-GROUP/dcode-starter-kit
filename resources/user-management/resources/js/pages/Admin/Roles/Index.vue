<template>
  <AppLayout title="Roles">
    <template #actions><DsgButton v-if="canCreate" :uri="route('admin.roles.create')" label="Add role" /></template>
    <div class="rounded-lg bg-white p-4 shadow-sm">
      <table class="w-full text-left text-sm">
        <thead class="border-b border-gray-200 text-xs uppercase text-gray-500"><tr><th class="px-3 py-3">Name</th><th class="px-3 py-3">Users</th><th class="px-3 py-3" /></tr></thead>
        <tbody>
          <tr v-for="role in roles" :key="role.id" class="border-b border-gray-100 last:border-0"><td class="px-3 py-3 font-medium">{{ role.name }}</td><td class="px-3 py-3">{{ role.users_count }}</td><td class="px-3 py-3 text-right"><DsgButton v-if="canUpdate" :uri="route('admin.roles.edit', role.id)" label="Edit" theme="brand-link" size="sm" /><DsgButton v-if="canDelete" type="button" label="Delete" theme="danger-link" size="sm" @button-clicked="remove(role.id)" /></td></tr>
          <tr v-if="roles.length === 0"><td colspan="3" class="px-3 py-8 text-center text-gray-500">No roles found.</td></tr>
        </tbody>
      </table>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { router, usePage } from "@inertiajs/vue3";
import { DsgButton } from "@dcodegroup-au/dsg-vue";
import { route } from "ziggy-js";
import AppLayout from "@layouts/App.vue";
interface Role { id: number; name: string; users_count: number }
const props = defineProps<{ roles: Role[] }>();
const permissions = usePage<{ auth: { permissions?: string[] } }>().props.auth.permissions ?? [];
const roles = props.roles;
const canCreate = permissions.includes("admin-portal-roles-permissions-create");
const canUpdate = permissions.includes("admin-portal-roles-permissions-update");
const canDelete = permissions.includes("admin-portal-roles-permissions-delete");
const remove = (id: number): void => { if (window.confirm("Delete this role?")) router.delete(route("admin.roles.destroy", id)); };
</script>
