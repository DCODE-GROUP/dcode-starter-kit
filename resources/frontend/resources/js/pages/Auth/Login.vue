<script setup lang="ts">
import { Link, useForm } from "@inertiajs/vue3";
import Auth from "../../layouts/Auth.vue";
import { useDcodeTranslations } from "../../composables/useDcodeTranslations";
const { translate } = useDcodeTranslations();
const form = useForm({ email: "", password: "", remember: false });
const submit = () => form.post("/login");
defineOptions({ layout: Auth });
</script>
<template><form class="space-y-5" @submit.prevent="submit"><label class="block text-sm font-medium">{{ translate('auth.email') }}<input v-model="form.email" type="email" autocomplete="email" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2" /></label><label class="block text-sm font-medium">{{ translate('auth.password') }}<input v-model="form.password" type="password" autocomplete="current-password" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2" /></label><label class="flex items-center gap-2 text-sm"><input v-model="form.remember" type="checkbox" />{{ translate('auth.remember') }}</label><p v-if="form.errors.email" class="text-sm text-red-600">{{ form.errors.email }}</p><button class="w-full rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-500">{{ translate('auth.login') }}</button><Link href="/forgot-password" class="block text-center text-sm text-indigo-600">{{ translate('auth.forgot') }}</Link></form></template>
