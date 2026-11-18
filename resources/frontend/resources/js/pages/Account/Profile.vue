<template>
  <AppLayout :title="translate('account.profile')">
    <form class="max-w-xl rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
      <div class="flex flex-col gap-5">
        <DsgInput v-model="form.first_name" name="first_name" label="First name" :errors="form.errors.first_name" autocomplete="given-name" />
        <DsgInput v-model="form.middle_name" name="middle_name" label="Middle name" :errors="form.errors.middle_name" autocomplete="additional-name" />
        <DsgInput v-model="form.last_name" name="last_name" label="Last name" :errors="form.errors.last_name" autocomplete="family-name" />
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
const form = useForm({ first_name: props.user.first_name ?? "", middle_name: props.user.middle_name ?? "", last_name: props.user.last_name ?? "", email: props.user.email });
const submit = (): void => form.patch(route("profile.update"));
</script>
