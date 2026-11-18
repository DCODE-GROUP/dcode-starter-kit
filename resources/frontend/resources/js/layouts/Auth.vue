<template>
  <div class="flex items-center justify-center min-h-screen bg-gray-neutral-100 px-4 py-10">
    <Head v-if="title" :title="title" />
    <slot />
  </div>
</template>

<script setup lang="ts">
import { Head } from "@inertiajs/vue3";

defineProps<{ title?: string }>();
</script>
