<template>
  <AuthLayout :title="translate('auth.page_titles.login')">
    <div class="auth__wrapper"><div class="auth__container">
      <div class="auth__header"><div class="dcode-logo">{{ translate('navigation.brand') }}</div></div>
      <div class="auth__content">
        <div class="auth__content-pre"><h1 class="text-lg font-semibold">{{ translate('auth.page_titles.login') }}</h1></div>
        <div v-if="isLocal" class="auth__content-pre">
          <LoginLink :email="developerEmail" :label="translate('auth.login_link')" class="text-sm underline" />
        </div>
        <form class="auth__content-body" @submit.prevent="submit">
          <div class="auth__content-form">
            <div class="auth__content-form-row"><DsgInput v-model="form.email" type="email" name="email" :label="translate('auth.fields.email')" :placeholder="translate('auth.placeholders.email')" :errors="form.errors.email" autocomplete="email" /></div>
            <div class="auth__content-form-row"><DsgInput v-model="form.password" type="password" name="password" :label="translate('auth.fields.password')" :errors="form.errors.password" autocomplete="current-password" /></div>
            <div class="auth__content-form-row"><DsgCheckbox v-model="form.remember" name="remember" size="sm" :label="translate('auth.fields.remember_me')" /></div>
          </div>
        <div class="auth__content-actions gap-2"><div class="auth-content-actions-left"><DsgButton :uri="route('password.request')" :label="translate('auth.buttons.forgot_password')" theme="brand-link" size="sm" /><DsgButton :uri="route('register')" label="Register" theme="brand-link" size="sm" /></div><div class="auth-content-actions-right"><DsgButton type="submit" :label="translate('auth.buttons.login')" /></div></div>
        </form>
      </div>
    </div></div>
  </AuthLayout>
</template>

<script setup lang="ts">
import { useForm, usePage } from "@inertiajs/vue3";
import { DsgButton, DsgCheckbox, DsgInput } from "@dsg";
import { route } from "ziggy-js";
import AuthLayout from "@layouts/Auth.vue";
import { useLaravelLocale } from "@composables/useLaravelLocale";
import LoginLink from "../../../../vendor/spatie/laravel-login-link/resources/js/login-link.vue";

const { trans: translate } = useLaravelLocale();
const page = usePage<{ environment?: string }>();
const isLocal = page.props.environment === "local";
const developerEmail = "forge@dcodegroup.com";
const form = useForm({ email: "", password: "", remember: false });
const submit = (): void => form.post(route("login"));
</script>
