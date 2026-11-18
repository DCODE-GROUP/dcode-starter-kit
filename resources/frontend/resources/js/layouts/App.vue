<template>
  <Head v-if="title" :title="title" />
  <div class="flex flex-row flex-1 h-screen overflow-x-hidden bg-gray-neutral-100">
    <AppSidebar />
    <main class="flex-1 flex flex-col gap-4 relative h-screen overflow-hidden">
      <div v-if="title || $slots.actions" class="flex flex-col gap-2 px-4 pt-4">
        <div v-if="title" class="flex items-center">
          <h1 class="display-sm font-semibold flex-1">{{ title }}</h1>
          <div v-if="$slots.actions" class="flex items-center gap-3"><slot name="actions" /></div>
        </div>
      </div>
      <div class="flex-1 min-h-0 overflow-y-auto px-4 pb-4"><slot /></div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import AppSidebar from "@components/Navigation/AppSidebar.vue";

defineProps<{ title?: string }>();
</script>
