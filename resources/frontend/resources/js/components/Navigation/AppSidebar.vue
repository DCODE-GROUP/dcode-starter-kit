<template>
  <div class="sidebar group/sidebar">
    <div class="sidebar__nav-rail">
      <nav
        class="sidebar__nav"
        :class="isNavOpen ? 'sidebar__nav--open' : 'sidebar__nav--closed'"
        @mouseenter="isNavHovered = true"
        @mouseleave="isNavHovered = false"
      >
        <div class="sidebar__nav-top">
          <div class="sidebar__logo-wrapper">
            <Link :href="dashboardUrl" class="sidebar__logo-link" :title="translate('navigation.dashboard')">
              <span class="sidebar__logo">{{ translate('navigation.brand') }}</span>
            </Link>
          </div>
          <ul class="sidebar__nav-list">
            <li v-for="item in menuItems" :key="item.title" class="sidebar__nav-list-item">
              <Link v-if="!item.submenu" :href="item.url" class="sidebar__nav-list-link" :class="{ 'sidebar__nav-list-link--active': isActive(item) }" :title="item.title">
                <span class="sidebar__nav-list-link-icon"><DsgIcon :icon="item.icon" /></span>
                <span class="sidebar__nav-list-link-label">{{ item.title }}</span>
              </Link>
              <button v-else type="button" class="sidebar__nav-list-link" :class="{ 'sidebar__nav-list-link--active': isActive(item) }" :title="item.title" @click="toggleSubmenu(item)">
                <span class="sidebar__nav-list-link-icon"><DsgIcon :icon="item.icon" /></span>
                <span class="sidebar__nav-list-link-label">{{ item.title }}</span>
              </button>
            </li>
          </ul>
        </div>
        <div class="sidebar__nav-bottom">
          <hr class="sidebar__divider" />
          <DsgDropdown ref="profileDropdownEl" :menu-items="profileMenuItems" placement="top-start" :skidding="-16">
            <template #dsg-dropdown--trigger>
              <DsgAvatar :full-name="userName" :profile-uri="profilePhotoUri" size="md" :title="userName" :sub-text="user.email" @click="toggleProfileDropdown" />
            </template>
          </DsgDropdown>
        </div>
      </nav>
    </div>
    <div v-if="activeSubmenu && submenuOpen" class="sidebar__submenu">
      <h2 class="sidebar__submenu-title">{{ activeSubmenu.title }}</h2>
      <div class="sidebar__submenu-nav-list">
        <Link v-for="item in activeSubmenu.submenu" :key="item.title" :href="item.url" class="sidebar__submenu-link" :class="{ 'sidebar__submenu-link--active': isActive(item) }">
          <DsgIcon :icon="item.icon" class="sidebar__submenu-link-icon" />
          <span>{{ item.title }}</span>
        </Link>
      </div>
    </div>
    <button type="button" class="sidebar__collapse-button" :aria-label="translate('navigation.toggle')" @click="isOpen = !isOpen">
      <DsgIcon icon="align-left-01" size="sm" class="sidebar__collapse-icon" :class="{ 'sidebar__collapse-icon--closed': !isNavOpen }" />
    </button>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, useTemplateRef, watch } from "vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import type { DsgDropdownMenuItem } from "@dsg";
import { DsgAvatar, DsgDropdown, DsgIcon } from "@dsg";
import { useLaravelLocale } from "@composables/useLaravelLocale";

interface MenuItem {
  title: string;
  icon: string;
  url: string;
  isActive?: boolean;
  submenu?: MenuItem[];
}

interface ProfileItem {
  title: string;
  icon: string;
  url: string;
  action?: string;
}

interface User {
  name?: string;
  first_name?: string;
  last_name?: string;
  email: string;
  profile_photo_path?: string | null;
}

interface PageProps {
  auth: { user: User };
  navigation: { menu: MenuItem[]; profile: ProfileItem[] };
}

const { trans: translate } = useLaravelLocale();
const page = usePage<PageProps>();
const isOpen = ref(true);
const isNavHovered = ref(false);
const submenuOpen = ref(false);
const user = computed(() => page.props.auth.user);
const userName = computed(() => user.value.name ?? ([user.value.first_name, user.value.last_name].filter(Boolean).join(" ") || user.value.email));
const profilePhotoUri = computed(() => {
  const path = user.value.profile_photo_path;
  return path ? (path.startsWith("http") ? path : `/storage/${path}`) : undefined;
});
const menuItems = computed(() => page.props.navigation?.menu ?? []);
const dashboardUrl = computed(() => menuItems.value.find((item) => !item.submenu)?.url ?? "/");
const activeSubmenu = computed(() => menuItems.value.find((item) => item.submenu));
const isNavOpen = computed(() => isOpen.value && (!submenuOpen.value || isNavHovered.value));
const isActive = (item: MenuItem): boolean => Boolean(item.isActive || item.submenu?.some((child) => isActive(child)));
const hasActiveSubmenu = (): boolean => Boolean(activeSubmenu.value?.submenu?.some((item) => isActive(item)));

const toggleSubmenu = (item: MenuItem): void => {
  if (!item.submenu) return;
  submenuOpen.value = !submenuOpen.value;
  isNavHovered.value = false;
};

watch(() => page.url, () => {
  if (hasActiveSubmenu()) {
    submenuOpen.value = true;
    isOpen.value = true;
    isNavHovered.value = false;
  }
}, { immediate: true });

const profileMenuItems = computed((): DsgDropdownMenuItem[] => page.props.navigation?.profile.map((item) => item.action === "logout"
  ? { type: "item", label: item.title, icon: item.icon, action: () => router.post(item.url) }
  : { type: "item", label: item.title, icon: item.icon, uri: item.url, uriTarget: "_self" }) ?? []);

const profileDropdownEl = useTemplateRef<typeof DsgDropdown>("profileDropdownEl");
const toggleProfileDropdown = (): void => { profileDropdownEl.value?.handleTriggerClick(); };
</script>
