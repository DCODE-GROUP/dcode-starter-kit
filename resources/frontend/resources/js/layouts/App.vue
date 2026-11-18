<script setup lang="ts">
import { Link, usePage } from "@inertiajs/vue3";
import { useDcodeTranslations } from "../composables/useDcodeTranslations";
const { translate } = useDcodeTranslations();
const page = usePage<{ auth: { user: { name?: string; email: string } } }>();
</script>
<template>
  <div class="min-h-screen bg-slate-50 md:flex">
    <aside class="w-full border-b border-slate-200 bg-white p-5 md:min-h-screen md:w-64 md:border-b-0 md:border-r"><div class="text-lg font-semibold text-indigo-700">{{ translate('navigation.dashboard') }}</div><nav class="mt-8 space-y-1"><Link href="/dashboard" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100">{{ translate('navigation.dashboard') }}</Link><Link href="/account/profile" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100">{{ translate('navigation.account') }}</Link></nav></aside>
    <main class="flex-1"><header class="flex items-center justify-between border-b border-slate-200 bg-white px-6 py-4"><span class="text-sm text-slate-500">{{ page.props.auth.user?.name || page.props.auth.user?.email }}</span><Link href="/logout" method="post" as="button" class="text-sm font-medium text-slate-600 hover:text-slate-900">{{ translate('navigation.logout') }}</Link></header><div class="mx-auto max-w-6xl p-6"><slot /></div></main>
  </div>
</template>
