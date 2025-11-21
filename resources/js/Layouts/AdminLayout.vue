<script setup>
import { ref } from "vue";
import { Link } from "@inertiajs/vue3";

const drawer = ref(true);

const menu = [
  { label: "Dashboard", icon: "mdi-view-dashboard", href: "/dashboard" },
  { label: "Chat", icon: "mdi-whatsapp", href: "/chat" },
  { label: "Agents", icon: "mdi-account-group", href: "/agents" },
  { label: "Tickets", icon: "mdi-ticket-confirmation-outline", href: "/tickets" },
  { label: "Templates", icon: "mdi-file-document-multiple", href: "/templates" },
  { label: "Broadcast", icon: "mdi-send", href: "/broadcast" },
  { label: "Settings", icon: "mdi-cog", href: "/settings" },
];
</script>

<template>
  <v-app>

    <!-- SIDEBAR -->
    <v-navigation-drawer
      v-model="drawer"
      elevation="10"
      class="pt-4"
      width="250"
    >
      <div class="text-center mb-6">
        <h2 class="font-weight-bold text-h6">WABA CS Panel</h2>
      </div>

      <v-list density="compact">
        <v-list-item
          v-for="item in menu"
          :key="item.href"
          link
          :class="[$page.url.startsWith(item.href) ? 'active-menu' : '']"
        >
          <Link :href="item.href" class="w-100 d-flex align-center" style="text-decoration:none; color:inherit;">
            <v-list-item-avatar size="32" class="mr-3">
              <v-icon>{{ item.icon }}</v-icon>
            </v-list-item-avatar>

            <v-list-item-title>
              {{ item.label }}
            </v-list-item-title>
          </Link>
        </v-list-item>
      </v-list>
    </v-navigation-drawer>

    <!-- NAVBAR -->
    <v-app-bar flat elevation="2">
      <v-btn icon @click="drawer = !drawer">
        <v-icon>mdi-menu</v-icon>
      </v-btn>

      <v-toolbar-title class="font-weight-bold">
        <slot name="title" />
      </v-toolbar-title>

      <v-spacer />

      <!-- USER DROPDOWN -->
      <v-menu>
        <template #activator="{ props }">
          <v-btn v-bind="props" variant="text" class="text-capitalize">
            <v-icon left>mdi-account-circle</v-icon>
            {{ $page.props.auth.user.name }}
            <v-icon right>mdi-chevron-down</v-icon>
          </v-btn>
        </template>

        <v-list>
          <v-list-item>
            <Link href="/profile">Profile</Link>
          </v-list-item>

          <v-divider />

          <v-list-item>
            <Link
              href="/logout"
              method="post"
              as="button"
              class="text-red"
            >
              Logout
            </Link>
          </v-list-item>
        </v-list>
      </v-menu>
    </v-app-bar>

    <!-- MAIN CONTENT -->
    <v-main>
      <v-container class="pt-8">
        <slot />
      </v-container>
    </v-main>

  </v-app>
</template>

<style scoped>
.active-menu {
  background-color: rgba(25, 118, 210, 0.15) !important;
  border-left: 4px solid #1976d2;
  font-weight: 600;
}

.v-list-item:hover {
  background-color: rgba(0, 0, 0, 0.05) !important;
}

a {
  text-decoration: none;
  color: inherit;
}
</style>
