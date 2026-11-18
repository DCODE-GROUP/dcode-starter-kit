<template>
  <AppLayout :title="translate('dashboard.title')">
    <section class="rounded-lg bg-white p-6 shadow-sm">
      <h2 class="lead-1 font-semibold">{{ translate('dashboard.welcome', { name: user.name || user.email }) }}</h2>
      <p class="mt-2 text-gray-600">{{ translate('dashboard.description') }}</p>
    </section>
  </AppLayout>
</template>

<script setup lang="ts">
import { usePage } from "@inertiajs/vue3";
import AppLayout from "@layouts/App.vue";
import { useLaravelLocale } from "@composables/useLaravelLocale";
import type { DcodeUser } from "../types";

const { trans: translate } = useLaravelLocale();
const page = usePage<{ auth: { user: DcodeUser } }>();
const user = page.props.auth.user;
defineOptions({ layout: AppLayout });
</script>
