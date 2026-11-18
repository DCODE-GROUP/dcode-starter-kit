<template>
  <AuthLayout :title="translate('auth.page_titles.forgot_password')">
    <form class="auth__content-body flex flex-col gap-5" @submit.prevent="submit">
      <DsgInput v-model="form.email" type="email" name="email" :label="translate('auth.fields.email')" :placeholder="translate('auth.placeholders.email')" :errors="form.errors.email" autocomplete="email" />
      <DsgButton type="submit" :label="translate('auth.buttons.send_reset')" width="full" />
      <DsgButton :uri="route('dcode.login')" :label="translate('auth.buttons.login')" theme="brand-link" size="sm" />
    </form>
  </AuthLayout>
</template>

<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import { DsgButton, DsgInput } from "@dsg";
import { route } from "ziggy-js";
import AuthLayout from "@layouts/Auth.vue";
import { useLaravelLocale } from "@composables/useLaravelLocale";

const { trans: translate } = useLaravelLocale();
const form = useForm({ email: "" });
const submit = (): void => form.post(route("dcode.password.email"));
defineOptions({ layout: AuthLayout });
</script>
