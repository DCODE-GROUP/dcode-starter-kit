<template>
    <div class="relative flex flex-col items-center gap-4 rounded-xl border border-gray-200 bg-white p-5">
        <DsgAvatar
            :full-name="fullName"
            :profile-uri="previewUrl ?? undefined"
            :theme="previewUrl ? 'profile-photo' : undefined"
            size="lg"
        />

        <div class="flex flex-col items-center gap-1 text-center">
            <span class="text-sm font-semibold text-brand-700">Upload profile photo</span>
            <span class="text-xs text-gray-600">JPG or PNG, up to 1 MB</span>
        </div>

        <div class="flex items-center gap-2">
            <DsgButton label="Choose photo" theme="gray-secondary" size="sm" type="button" @button-clicked="triggerUpload" />
            <DsgButton
                v-if="previewUrl"
                label="Remove"
                theme="gray-tertiary"
                size="sm"
                type="button"
                @button-clicked="removePhoto"
            />
        </div>

        <input ref="uploadInput" accept="image/jpeg,image/png" class="sr-only" type="file" @change="selectPhoto" />
        <p v-if="error" class="text-sm text-error-600">{{ error }}</p>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, useTemplateRef, onBeforeUnmount } from "vue";
import { DsgAvatar, DsgButton } from "@dcodegroup-au/dsg-vue";

const props = defineProps<{
    fullName: string;
    initialUrl?: string | null;
}>();
const file = defineModel<File | null>({ default: null });
const remove = defineModel<boolean>("remove", { default: false });
const uploadInput = useTemplateRef<HTMLInputElement>("uploadInput");
const previewObjectUrl = ref<string | null>(null);
const error = ref("");

const previewUrl = computed(() => previewObjectUrl.value ?? (remove.value ? null : props.initialUrl ?? null));

const triggerUpload = (): void => uploadInput.value?.click();

const selectPhoto = (event: Event): void => {
    const selected = (event.target as HTMLInputElement).files?.[0] ?? null;
    (event.target as HTMLInputElement).value = "";
    error.value = "";

    if (!selected) return;
    if (!(["image/jpeg", "image/png"] as string[]).includes(selected.type)) {
        error.value = "Please select a JPG or PNG image.";
        return;
    }
    if (selected.size > 1024 * 1024) {
        error.value = "The profile photo must be no larger than 1 MB.";
        return;
    }

    if (previewObjectUrl.value) URL.revokeObjectURL(previewObjectUrl.value);
    previewObjectUrl.value = URL.createObjectURL(selected);
    file.value = selected;
    remove.value = false;
};

const removePhoto = (): void => {
    if (previewObjectUrl.value) {
        URL.revokeObjectURL(previewObjectUrl.value);
        previewObjectUrl.value = null;
    }
    file.value = null;
    remove.value = true;
};

onBeforeUnmount(() => {
    if (previewObjectUrl.value) URL.revokeObjectURL(previewObjectUrl.value);
});
</script>
