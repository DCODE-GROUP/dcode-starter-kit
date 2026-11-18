<template>
  <AuthLayout :title="translate('auth.page_titles.reset_password')">
    <form class="auth__content-body flex flex-col gap-5" @submit.prevent="submit">
      <DsgInput v-model="form.email" type="email" name="email" :label="translate('auth.fields.email')" :errors="form.errors.email" />
      <DsgInput v-model="form.password" type="password" name="password" :label="translate('auth.fields.password')" :errors="form.errors.password" />
      <DsgInput v-model="form.password_confirmation" type="password" name="password_confirmation" :label="translate('auth.fields.confirm_password')" :errors="form.errors.password_confirmation" />
      <DsgButton type="submit" :label="translate('auth.buttons.reset_password')" width="full" />
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
import type { ResetPasswordPageProps } from "../../types";

const props = defineProps<ResetPasswordPageProps>();
const { trans: translate } = useLaravelLocale();
const form = useForm({ token: props.token, email: props.email ?? "", password: "", password_confirmation: "" });
const submit = (): void => form.post(route("dcode.password.update"));
defineOptions({ layout: AuthLayout });
</script>
