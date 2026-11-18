<template>
  <AuthLayout title="Create account">
    <div class="auth__wrapper"><div class="auth__container">
      <div class="auth__header"><div class="dcode-logo">{{ translate('navigation.brand') }}</div></div>
      <div class="auth__content">
        <div class="auth__content-pre"><h1 class="text-lg font-semibold">Create account</h1></div>
        <form class="auth__content-body" @submit.prevent="submit">
          <div class="auth__content-form">
            <div class="auth__content-form-row"><DsgInput v-model="form.first_name" name="first_name" label="First name" :errors="form.errors.first_name" autocomplete="given-name" /></div>
            <div class="auth__content-form-row"><DsgInput v-model="form.middle_name" name="middle_name" label="Middle name" :errors="form.errors.middle_name" autocomplete="additional-name" /></div>
            <div class="auth__content-form-row"><DsgInput v-model="form.last_name" name="last_name" label="Last name" :errors="form.errors.last_name" autocomplete="family-name" /></div>
            <div class="auth__content-form-row"><DsgInput v-model="form.email" type="email" name="email" label="Email" :errors="form.errors.email" autocomplete="email" /></div>
            <div class="auth__content-form-row"><DsgInput v-model="form.password" type="password" name="password" label="Password" :errors="form.errors.password" autocomplete="new-password" /></div>
            <div class="auth__content-form-row"><DsgInput v-model="form.password_confirmation" type="password" name="password_confirmation" label="Confirm password" :errors="form.errors.password_confirmation" autocomplete="new-password" /></div>
          </div>
        </form>
        <div class="auth__content-actions"><div class="auth-content-actions-left"><DsgButton :uri="route('login')" label="Log in" theme="brand-link" size="sm" /></div><div class="auth-content-actions-right"><DsgButton type="submit" label="Create account" @button-clicked="submit" /></div></div>
      </div>
    </div></div>
  </AuthLayout>
</template>

<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import { DsgButton, DsgInput } from "@dsg";
import { route } from "ziggy-js";
import AuthLayout from "@layouts/Auth.vue";
import { useLaravelLocale } from "@composables/useLaravelLocale";

const { trans: translate } = useLaravelLocale();
const form = useForm({ first_name: "", middle_name: "", last_name: "", email: "", password: "", password_confirmation: "" });
const submit = (): void => form.post(route("register"));
</script>
