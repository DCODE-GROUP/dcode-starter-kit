<script setup lang="ts">
import { useDcodeTranslations } from "../composables/useDcodeTranslations";
const { translate } = useDcodeTranslations();
</script>
<template>
  <main class="flex min-h-screen items-center justify-center bg-slate-100 px-6 py-12">
    <section class="w-full max-w-md rounded-2xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
      <div class="mb-8"><p class="text-sm font-semibold uppercase tracking-[0.2em] text-indigo-600">{{ translate('navigation.dashboard') }}</p><h1 class="mt-2 text-2xl font-semibold">{{ translate('auth.login') }}</h1></div>
      <slot />
    </section>
  </main>
</template>
