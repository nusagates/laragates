<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import axios from "axios";

// === GET USER ROLE ===
const page = usePage();
const userRole = page.props.auth.user.role;

const drawer = ref(true);

// ===============================
// 📌 SIDEBAR MENU (Role-Based)
// ===============================
const menu = [
  {
    label: "Dashboard",
    icon: "mdi-view-dashboard",
    href: "/dashboard",
    roles: ["admin", "superadmin", "supervisor", "agent"],
  },
  {
    label: "Chat",
    icon: "mdi-whatsapp",
    href: "/chat",
    roles: ["admin", "superadmin", "supervisor", "agent"],
  },

  // 🔥 WHATSAPP MENU (BARU)
  {
    label: "WhatsApp Menu",
    icon: "mdi-format-list-bulleted",
    href: "/menu",
    roles: ["admin", "superadmin"],
  },

  {
    label: "Tickets",
    icon: "mdi-ticket-confirmation-outline",
    href: "/tickets",
    roles: ["admin", "superadmin", "supervisor", "agent"],
  },

  {
    label: "Agents",
    icon: "mdi-account-group",
    href: "/agents",
    roles: ["admin", "superadmin"],
  },

  {
    label: "Templates",
    icon: "mdi-file-document-multiple",
    href: "/templates",
    roles: ["admin", "superadmin", "supervisor"],
  },

  {
    label: "Broadcast",
    icon: "mdi-send",
    href: "/broadcast",
    roles: ["admin", "superadmin", "supervisor"],
  },

  {
    label: "Analytics",
    icon: "mdi-finance",
    href: "/analytics",
    roles: ["admin", "superadmin"],
  },

  {
    label: "Settings",
    icon: "mdi-cog",
    href: "/settings",
    roles: ["superadmin"],
  },
];

// ===============================
// ❤️ HEARTBEAT (Online Checking)
// ===============================
let heartbeatInterval = null;
let idleTimer = null;

const sendHeartbeat = () => {
  axios.post("/agent/heartbeat").catch(() => {});
};

const startIdleTimer = () => {
  clearTimeout(idleTimer);
  idleTimer = setTimeout(() => {
    axios.post("/agent/offline").catch(() => {});
  }, 15000);
};

const handleVisibility = () => {
  if (document.hidden) startIdleTimer();
  else {
    sendHeartbeat();
    clearTimeout(idleTimer);
  }
};

let lastMove = 0;
const throttledMove = () => {
  const now = Date.now();
  if (now - lastMove > 3000) {
    sendHeartbeat();
    lastMove = now;
  }
};

onMounted(() => {
  heartbeatInterval = setInterval(sendHeartbeat, 5000);

  document.addEventListener("visibilitychange", handleVisibility);
  window.addEventListener("focus", sendHeartbeat);
  window.addEventListener("mousemove", throttledMove);
  window.addEventListener("keydown", sendHeartbeat);
});

onUnmounted(() => {
  clearInterval(heartbeatInterval);
  clearTimeout(idleTimer);

  document.removeEventListener("visibilitychange", handleVisibility);
  window.removeEventListener("focus", sendHeartbeat);
  window.removeEventListener("mousemove", throttledMove);
  window.removeEventListener("keydown", sendHeartbeat);
});
</script>

<template>
  <v-app>
    <!-- ============= SIDEBAR ============= -->
    <v-navigation-drawer
      v-model="drawer"
      elevation="10"
      width="250"
      class="pt-4"
    >
      <div class="text-center mb-6">
        <h2 class="font-weight-bold text-h6">WABA</h2>
      </div>

      <v-list density="compact" nav>
        <v-list-item
          v-for="item in menu.filter(m => m.roles.includes(userRole))"
          :key="item.href"
          link
          :class="[$page.url.startsWith(item.href) ? 'active-menu' : '']"
        >
          <Link
            :href="item.href"
            style="color: inherit; text-decoration: none"
            class="w-100 d-flex align-center"
          >
            <v-icon class="mr-4">{{ item.icon }}</v-icon>
            <v-list-item-title>{{ item.label }}</v-list-item-title>
          </Link>
        </v-list-item>
      </v-list>
    </v-navigation-drawer>

    <!-- ============= NAVBAR ============= -->
    <v-app-bar flat elevation="2">
      <v-btn icon @click="drawer = !drawer">
        <v-icon>mdi-menu</v-icon>
      </v-btn>

      <v-toolbar-title class="font-weight-bold">
        <slot name="title" />
      </v-toolbar-title>

      <v-spacer />

      <v-menu>
        <template #activator="{ props }">
          <v-btn variant="text" v-bind="props" class="text-capitalize">
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
            <Link href="/logout" method="post" as="button" class="text-red">
              Logout
            </Link>
          </v-list-item>
        </v-list>
      </v-menu>
    </v-app-bar>

    <!-- ============= CONTENT ============= -->
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
  color: inherit;
  text-decoration: none;
}
</style>
