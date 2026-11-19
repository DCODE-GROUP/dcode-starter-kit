<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import { DsgButton } from "@dsg";

withDefaults(
    defineProps<{
        cancelUrl: string;
        canSave?: boolean;
        saveLabel?: string;
    }>(),
    {
        canSave: true,
        saveLabel: "Save",
    },
);

const redirectBack = (cancelUrl: string): void => {
    router.visit(cancelUrl);
};
</script>

<template>
    <div class="sticky bottom-0 inset-x-0 flex items-center gap-3 px-4 py-2">
        <DsgButton
            label="Cancel"
            theme="gray-secondary"
            size="md"
            :width="canSave ? 'auto' : 'full'"
            type="button"
            @button-clicked="redirectBack(cancelUrl)"
        />
        <DsgButton
            v-if="canSave"
            :label="saveLabel"
            type="submit"
            width="full"
            size="md"
        />
    </div>
</template>
