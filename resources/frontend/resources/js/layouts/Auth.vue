<template>
  <div class="flex min-h-screen items-center justify-center bg-gray-neutral-100 px-4 py-10">
    <Head v-if="title" :title="title" />
    <div class="auth__container w-full max-w-lg rounded-lg bg-white shadow-sm">
      <div class="auth__header flex items-center justify-center border-b border-gray-200 px-8 py-6">
        <span class="text-xl font-semibold text-brand-700">{{ translate("navigation.dashboard") }}</span>
      </div>
      <div class="auth__content px-8 py-8"><slot /></div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import { useLaravelLocale } from "@composables/useLaravelLocale";

defineProps<{ title?: string }>();
const { trans: translate } = useLaravelLocale();
</script>
