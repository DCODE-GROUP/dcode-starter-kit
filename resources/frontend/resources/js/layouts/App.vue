<template>
  <Head v-if="title" :title="title" />
  <div class="flex min-h-screen flex-row bg-gray-neutral-100">
    <aside class="flex w-64 flex-col border-r border-gray-200 bg-white p-4">
      <div class="border-b border-gray-200 px-3 py-4 text-lg font-semibold text-brand-700">{{ translate("navigation.dashboard") }}</div>
      <nav class="flex flex-1 flex-col gap-2 py-6">
        <DsgButton :uri="route('dcode.dashboard')" :label="translate('navigation.dashboard')" theme="gray-link" />
        <DsgButton :uri="route('dcode.profile.edit')" :label="translate('account.profile')" theme="gray-link" />
        <DsgButton :uri="route('dcode.password.edit')" :label="translate('account.password')" theme="gray-link" />
      </nav>
      <Link :href="route('dcode.logout')" method="post" as="button" class="dsg-button dsg-button--gray-secondary w-full">{{ translate('navigation.logout') }}</Link>
    </aside>
    <main class="flex min-w-0 flex-1 flex-col overflow-hidden">
      <div v-if="title" class="flex items-center px-8 pt-8"><h1 class="display-sm flex-1 font-semibold">{{ title }}</h1></div>
      <div class="flex-1 px-8 py-6"><slot /></div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { DsgButton } from "@dsg";
import { useLaravelLocale } from "@composables/useLaravelLocale";

defineProps<{ title?: string }>();
const { trans: translate } = useLaravelLocale();
</script>
