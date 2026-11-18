<template>
  <div class="sidebar group/sidebar">
    <div class="sidebar__nav-rail">
      <nav class="sidebar__nav" :class="isOpen ? 'sidebar__nav--open' : 'sidebar__nav--closed'">
        <div class="sidebar__nav-top">
          <div class="sidebar__logo-wrapper">
            <Link :href="route('dcode.dashboard')" class="sidebar__logo-link" :title="translate('navigation.dashboard')">
              <span class="sidebar__logo">{{ translate('navigation.brand') }}</span>
            </Link>
          </div>

          <ul class="sidebar__nav-list">
            <li v-for="item in menuItems" :key="item.label" class="sidebar__nav-list-item">
              <Link :href="route(item.route)" class="sidebar__nav-list-link" :class="{ 'sidebar__nav-list-link--active': route().current(item.route) }" :title="item.label">
                <span class="sidebar__nav-list-link-icon"><DsgIcon :icon="item.icon" /></span>
                <span class="sidebar__nav-list-link-label">{{ item.label }}</span>
              </Link>
            </li>
          </ul>
        </div>

        <div class="sidebar__nav-bottom">
          <hr class="sidebar__divider" />
          <Link :href="route('dcode.profile.edit')" class="sidebar__account-link">
            <DsgIcon icon="user-01" />
            <span class="sidebar__nav-list-link-label">{{ translate('account.profile') }}</span>
          </Link>
          <Link :href="route('dcode.logout')" method="post" as="button" class="sidebar__account-link">
            <DsgIcon icon="log-out-01" />
            <span class="sidebar__nav-list-link-label">{{ translate('navigation.logout') }}</span>
          </Link>
        </div>
      </nav>
    </div>

    <button type="button" class="sidebar__collapse-button" :aria-label="translate('navigation.toggle')" @click="isOpen = !isOpen">
      <DsgIcon icon="align-left-01" size="sm" class="sidebar__collapse-icon" :class="{ 'sidebar__collapse-icon--closed': !isOpen }" />
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { Link } from "@inertiajs/vue3";
import { DsgIcon } from "@dsg";
import { route } from "ziggy-js";
import { useLaravelLocale } from "@composables/useLaravelLocale";

const { trans: translate } = useLaravelLocale();
const isOpen = ref(true);
const menuItems = [
  { label: translate("navigation.dashboard"), route: "dcode.dashboard", icon: "home-01" },
  { label: translate("account.profile"), route: "dcode.profile.edit", icon: "user-01" },
  { label: translate("account.password"), route: "dcode.password.edit", icon: "lock-01" },
];
</script>
