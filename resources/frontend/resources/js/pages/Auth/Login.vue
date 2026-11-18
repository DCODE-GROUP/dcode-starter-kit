<template>
  <AuthLayout :title="translate('auth.page_titles.login')">
    <form class="auth__content-body flex flex-col gap-5" @submit.prevent="submit">
      <DsgInput v-model="form.email" type="email" name="email" :label="translate('auth.fields.email')" :placeholder="translate('auth.placeholders.email')" :errors="form.errors.email" autocomplete="email" />
      <DsgInput v-model="form.password" type="password" name="password" :label="translate('auth.fields.password')" :errors="form.errors.password" autocomplete="current-password" />
      <DsgCheckbox v-model="form.remember" name="remember" :label="translate('auth.fields.remember_me')" />
      <DsgButton type="submit" :label="translate('auth.buttons.login')" width="full" />
      <DsgButton :uri="route('dcode.password.request')" :label="translate('auth.buttons.forgot_password')" theme="brand-link" size="sm" />
    </form>
  </AuthLayout>
</template>

<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import { DsgButton, DsgCheckbox, DsgInput } from "@dsg";
import { route } from "ziggy-js";
import AuthLayout from "@layouts/Auth.vue";
import { useLaravelLocale } from "@composables/useLaravelLocale";

const { trans: translate } = useLaravelLocale();
const form = useForm({ email: "", password: "", remember: false });
const submit = (): void => form.post(route("dcode.login.store"));
defineOptions({ layout: AuthLayout });
</script>
