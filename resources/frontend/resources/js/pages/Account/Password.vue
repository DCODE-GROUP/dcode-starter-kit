<template>
  <AppLayout :title="translate('account.password')">
    <form class="max-w-xl rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
      <div class="flex flex-col gap-5">
        <DsgInput v-model="form.current_password" type="password" name="current_password" :label="translate('account.current_password')" :errors="form.errors.current_password" />
        <DsgInput v-model="form.password" type="password" name="password" :label="translate('account.new_password')" :errors="form.errors.password" />
        <DsgInput v-model="form.password_confirmation" type="password" name="password_confirmation" :label="translate('auth.fields.confirm_password')" :errors="form.errors.password_confirmation" />
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

const { trans: translate } = useLaravelLocale();
const form = useForm({ current_password: "", password: "", password_confirmation: "" });
const submit = (): void => form.put(route("dcode.password.store"));
defineOptions({ layout: AppLayout });
</script>
