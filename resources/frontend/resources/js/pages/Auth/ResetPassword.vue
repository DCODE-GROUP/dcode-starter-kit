<template>
  <AuthLayout :title="translate('auth.page_titles.reset_password')">
    <div class="auth__wrapper"><div class="auth__container"><div class="auth__header"><div class="dcode-logo">{{ translate('navigation.brand') }}</div></div><div class="auth__content">
      <div class="auth__content-pre"><h1 class="text-lg font-semibold">{{ translate('auth.page_titles.reset_password') }}</h1></div>
      <form class="auth__content-body" @submit.prevent="submit"><div class="auth__content-form"><div class="auth__content-form-row"><DsgInput v-model="form.email" type="email" name="email" :label="translate('auth.fields.email')" :errors="form.errors.email" /></div><div class="auth__content-form-row"><DsgInput v-model="form.password" type="password" name="password" :label="translate('auth.fields.password')" :errors="form.errors.password" /></div><div class="auth__content-form-row"><DsgInput v-model="form.password_confirmation" type="password" name="password_confirmation" :label="translate('auth.fields.confirm_password')" :errors="form.errors.password_confirmation" /></div></div></form>
      <div class="auth__content-actions"><div class="auth-content-actions-left"><DsgButton :uri="route('dcode.login')" :label="translate('auth.buttons.login')" theme="brand-link" size="sm" /></div><div class="auth-content-actions-right"><DsgButton type="submit" :label="translate('auth.buttons.reset_password')" @button-clicked="submit" /></div></div>
    </div></div></div>
  </AuthLayout>
</template>

<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import { DsgButton, DsgInput } from "@dsg";
import { route } from "ziggy-js";
import AuthLayout from "@layouts/Auth.vue";
import { useLaravelLocale } from "@composables/useLaravelLocale";
import type { ResetPasswordPageProps } from "../../types";

const props = defineProps<ResetPasswordPageProps>();
const { trans: translate } = useLaravelLocale();
const form = useForm({ token: props.token, email: props.email ?? "", password: "", password_confirmation: "" });
const submit = (): void => form.post(route("dcode.password.update"));
</script>
