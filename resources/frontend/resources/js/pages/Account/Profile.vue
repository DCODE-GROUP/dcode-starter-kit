<template>
  <AppLayout :title="translate('account.profile')">
    <form class="max-w-xl rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
      <div class="flex flex-col gap-5">
        <DsgInput v-model="form.name" name="name" :label="translate('account.name')" :errors="form.errors.name" autocomplete="name" />
        <DsgInput v-model="form.email" type="email" name="email" :label="translate('auth.fields.email')" :errors="form.errors.email" autocomplete="email" />
        <DsgButton type="submit" :label="translate('account.update')" />
      </div>
    </form>
  </AppLayout>
</template>

<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import { DsgButton, DsgInput } from "@dsg";
import { route } from "ziggy-js";
import AppLayout from "@layouts/App.vue";
import { useLaravelLocale } from "@composables/useLaravelLocale";
import type { DcodeUser } from "../../types";

const props = defineProps<{ user: DcodeUser }>();
const { trans: translate } = useLaravelLocale();
const form = useForm({ name: props.user.name ?? "", email: props.user.email });
const submit = (): void => form.patch(route("profile.update"));
</script>
