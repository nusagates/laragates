<script setup>
import { ref, onMounted, onUnmounted } from "vue"
import { Link, usePage } from "@inertiajs/vue3"
import axios from "axios"

// ===============================
// AUTH & ROLE
// ===============================
const page = usePage()
const userRole = page.props.auth.user.role
const drawer = ref(true)

// ===============================
// SIDEBAR MENU (ROLE BASED)
// ===============================
const menu = [
  { label: "Dashboard", icon: "mdi-view-dashboard", href: "/dashboard", roles: ["admin","superadmin","supervisor","agent"] },
  { label: "Chat", icon: "mdi-whatsapp", href: "/chat", roles: ["admin","superadmin","supervisor","agent"] },
  { label: "WhatsApp Menu", icon: "mdi-format-list-bulleted", href: "/menu", roles: ["admin","superadmin"] },
  { label: "Tickets", icon: "mdi-ticket-confirmation-outline", href: "/tickets", roles: ["admin","superadmin","supervisor","agent"] },
  { label: "Agents", icon: "mdi-account-group", href: "/agents", roles: ["admin","superadmin"] },
  { label: "Templates", icon: "mdi-file-document-multiple", href: "/templates", roles: ["admin","superadmin","supervisor"] },
  { label: "Broadcast", icon: "mdi-send", href: "/broadcast", roles: ["admin","superadmin","supervisor"] },
  { label: "Analytics", icon: "mdi-finance", href: "/analytics", roles: ["admin","superadmin"] },
  { label: "Settings", icon: "mdi-cog", href: "/settings", roles: ["superadmin"] },
]

// ===============================
// HEARTBEAT (AMAN)
// ===============================
let heartbeatInterval = null
const sendHeartbeat = () => {
  axios.post("/agent/heartbeat").catch(() => {})
}

onMounted(() => {
  heartbeatInterval = setInterval(sendHeartbeat, 5000)
})

onUnmounted(() => {
  clearInterval(heartbeatInterval)
})
</script>

<template>
  <v-app class="admin-dark">

    <!-- ================= SIDEBAR ================= -->
    <v-navigation-drawer
      v-model="drawer"
      width="260"
      permanent
      class="sidebar"
    >
      <div class="logo">
        WABA
      </div>

      <v-list density="compact" nav>
        <v-list-item
          v-for="item in menu.filter(m => m.roles.includes(userRole))"
          :key="item.href"
          :class="[$page.url.startsWith(item.href) ? 'active-menu' : 'menu-item']"
        >
          <Link :href="item.href" class="menu-link">
            <v-icon class="mr-3">{{ item.icon }}</v-icon>
            {{ item.label }}
          </Link>
        </v-list-item>
      </v-list>
    </v-navigation-drawer>

    <!-- ================= MAIN ================= -->
    <v-main class="main-area">

      <!-- TOPBAR -->
      <v-app-bar flat height="64" class="topbar">
        <v-btn icon @click="drawer = !drawer">
          <v-icon>mdi-menu</v-icon>
        </v-btn>

        <v-toolbar-title class="font-weight-bold">
          <slot name="title" />
        </v-toolbar-title>

        <v-spacer />

        <v-menu>
          <template #activator="{ props }">
            <v-btn variant="text" v-bind="props" class="user-btn">
              <v-icon>mdi-account-circle</v-icon>
              {{ page.props.auth.user.name }}
              <v-icon size="18">mdi-chevron-down</v-icon>
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

      <!-- CONTENT -->
      <div class="content">
        <slot />
      </div>

    </v-main>
  </v-app>
</template>

<style scoped>
/* ================= GLOBAL ================= */
.admin-dark {
  background: linear-gradient(180deg, #020617, #0f172a);
  min-height: 100vh;
}

/* ================= SIDEBAR ================= */
.sidebar {
  background: #020617;
  border-right: 1px solid #1e293b;
  color: #e5e7eb;
}

.logo {
  font-size: 20px;
  font-weight: 700;
  text-align: center;
  padding: 20px;
  color: #38bdf8;
  letter-spacing: 1px;
}

/* ================= MENU ================= */
.menu-link {
  display: flex;
  align-items: center;
  width: 100%;
  color: #cbd5f5;
  text-decoration: none;
}

.menu-item:hover {
  background: rgba(56, 189, 248, 0.1);
}

.active-menu {
  background: linear-gradient(90deg, rgba(56,189,248,0.25), transparent);
  border-left: 4px solid #38bdf8;
  font-weight: 600;
}

/* ================= TOPBAR ================= */
.topbar {
  background: #020617;
  border-bottom: 1px solid #1e293b;
  color: #e5e7eb;
}

.user-btn {
  color: #e5e7eb;
}

/* ================= MAIN ================= */
.main-area {
  background: #0f172a;
}

.content {
  padding: 24px;
}
</style>
