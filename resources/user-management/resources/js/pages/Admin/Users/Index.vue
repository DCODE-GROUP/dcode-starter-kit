<template>
  <AppLayout title="Users">
    <template #actions>
      <DsgButton v-if="canCreate" :uri="route('admin.users.create')" label="Add user" />
    </template>

    <div class="rounded-lg bg-white p-4 shadow-sm">
      <table class="w-full text-left text-sm">
        <thead class="border-b border-gray-200 text-xs uppercase text-gray-500">
          <tr>
            <th class="px-3 py-3">Name</th>
            <th class="px-3 py-3">Email</th>
            <th class="px-3 py-3">Roles</th>
            <th class="px-3 py-3">Status</th>
            <th class="px-3 py-3" />
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in users.data" :key="user.id" class="border-b border-gray-100 last:border-0">
            <td class="px-3 py-3 font-medium">{{ displayName(user) }}</td>
            <td class="px-3 py-3">{{ user.email }}</td>
            <td class="px-3 py-3">{{ user.roles.map((role) => role.name).join(', ') || '—' }}</td>
            <td class="px-3 py-3">{{ user.active ? 'Active' : 'Inactive' }}</td>
            <td class="px-3 py-3 text-right">
              <DsgButton v-if="canUpdate || user.id === currentUserId" :uri="route('admin.users.edit', user.id)" label="Edit" theme="brand-link" size="sm" />
              <DsgButton v-if="canDelete && user.id !== currentUserId" type="button" label="Delete" theme="danger-link" size="sm" @button-clicked="remove(user.id)" />
            </td>
          </tr>
          <tr v-if="users.data.length === 0">
            <td colspan="5" class="px-3 py-8 text-center text-gray-500">No users found.</td>
          </tr>
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

interface Role { id: number; name: string }
interface User { id: number; first_name?: string; middle_name?: string; last_name?: string; email: string; active: boolean; roles: Role[] }
interface PaginatedUsers { data: User[] }

const props = defineProps<{ users: PaginatedUsers }>();
const page = usePage<{ auth: { user: { id: number }; permissions?: string[] } }>();
const permissions = page.props.auth.permissions ?? [];
const currentUserId = page.props.auth.user.id;
const canCreate = permissions.includes("admin-portal-users-create");
const canUpdate = permissions.includes("admin-portal-users-update");
const canDelete = permissions.includes("admin-portal-users-delete");
const users = props.users;

const displayName = (user: User): string => [user.first_name, user.middle_name, user.last_name].filter(Boolean).join(" ") || user.email;
const remove = (id: number): void => {
  if (window.confirm("Delete this user?")) router.delete(route("admin.users.destroy", id));
};
</script>
