<template>
  <AppLayout :title="title">
    <div class="max-w-3xl rounded-lg bg-white p-6 shadow-sm">
      <form class="space-y-5" @submit.prevent="submit">
        <DsgInput v-model="form.name" name="name" label="Name" :errors="form.errors.name" />
        <fieldset class="space-y-2"><legend class="font-semibold">Permissions</legend><label v-for="permission in permissions" :key="permission" class="flex items-center gap-2 text-sm"><input v-model="form.permissions" type="checkbox" :value="permission" /> {{ permission }}</label></fieldset>
        <div class="flex justify-end gap-3"><DsgButton :uri="route('admin.roles.index')" label="Cancel" theme="neutral-link" /><DsgButton type="submit" :label="role ? 'Save changes' : 'Create role'" :disabled="form.processing" /></div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import { DsgButton, DsgInput } from "@dcodegroup-au/dsg-vue";
import { route } from "ziggy-js";
import AppLayout from "@layouts/App.vue";
interface Role { id: number; name: string; permissions?: string[] }
const props = defineProps<{ role: Role | null; permissions: string[]; title: string }>();
const role = props.role;
const permissions = props.permissions;
const form = useForm({ name: role?.name ?? "", permissions: role?.permissions ?? [] as string[] });
const submit = (): void => role ? form.put(route("admin.roles.update", role.id)) : form.post(route("admin.roles.store"));
</script>
