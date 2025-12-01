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
  {
    label: "Tickets",
    icon: "mdi-ticket-confirmation-outline",
    href: "/tickets",
    roles: ["admin", "superadmin", "supervisor", "agent"],
  },

  // Supervisor & Admin & Superadmin
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

  // SETTINGS → only superadmin
  {
    label: "Settings",
    icon: "mdi-cog",
    href: "/settings",
    roles: ["superadmin"],
  },
];

// ===============================
// ❤️ HEARTBEAT (Online Tracking)
// ===============================
let heartbeatInterval = null;
let offlineTimer = null;

const sendHeartbeat = () => {
  axios.post("/agent/heartbeat").catch(() => {});
};

const startIdleTimer = () => {
  clearTimeout(offlineTimer);
  offlineTimer = setTimeout(() => {
    axios.post("/agent/offline").catch(() => {});
  }, 15000);
};

const handleVisibility = () => {
  if (document.hidden) {
    startIdleTimer();
  } else {
    sendHeartbeat();
    clearTimeout(offlineTimer);
  }
};

let lastMove = 0;
const sendMoveHeartbeat = () => {
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
  window.addEventListener("mousemove", sendMoveHeartbeat);
  window.addEventListener("keydown", sendHeartbeat);
});

onUnmounted(() => {
  clearInterval(heartbeatInterval);
  clearTimeout(offlineTimer);

  document.removeEventListener("visibilitychange", handleVisibility);
  window.removeEventListener("focus", sendHeartbeat);
  window.removeEventListener("mousemove", sendMoveHeartbeat);
  window.removeEventListener("keydown", sendHeartbeat);
});
</script>

<template>
  <v-app>
    <!-- ============= SIDEBAR ============= -->
    <v-navigation-drawer v-model="drawer" elevation="10" width="250" class="pt-4">
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
            class="w-100 d-flex align-center"
            style="color: inherit; text-decoration: none"
          >
            <v-icon class="mr-4">{{ item.icon }}</v-icon>
            <v-list-item-title>{{ item.label }}</v-list-item-title>
          </Link>
        </v-list-item>
      </v-list>
    </v-navigation-drawer>

    <!-- ============= TOP NAVBAR ============= -->
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

    <!-- ============= CONTENT WRAPPER ============= -->
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
