<script setup lang="ts">
withDefaults(defineProps<{
  title?: string;
  description?: string;
  theme?: "gray" | "white";
}>(), {
  title: "",
  description: "",
  theme: "gray",
});
</script>

<template>
  <section class="flex flex-col gap-5 rounded-xl p-4 shadow-xs" :class="theme === 'white' ? 'bg-white' : 'bg-gray-50'">
    <div v-if="title || description" class="flex flex-col gap-1">
      <h2 v-if="title" class="text-base font-semibold leading-6 text-gray-900">{{ title }}</h2>
      <p v-if="description" class="text-sm text-gray-600">{{ description }}</p>
    </div>
    <div class="flex flex-col">
      <slot />
    </div>
  </section>
</template>
