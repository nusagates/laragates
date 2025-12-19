<script setup>
import { ref, onMounted, onUnmounted } from "vue"
import { Link, usePage, router } from "@inertiajs/vue3"
import axios from "axios"

/* ================= AUTH ================= */
const page = usePage()
const userRole = page.props.auth.user.role
const drawer = ref(true)

/* ================= MENU ================= */
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

/* ================= HEARTBEAT ================= */
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

/* ================= LOGOUT & IDLE ================= */
const showLogoutConfirm = ref(false)
const showIdleConfirm = ref(false)

/* ================= TOAST ================= */
const toast = ref(false)
const toastText = ref("")

/* idle config (15 menit) */
const IDLE_LIMIT = 15 * 60 * 1000
let idleTimer = null

const resetIdleTimer = () => {
  clearTimeout(idleTimer)
  idleTimer = setTimeout(() => {
    showIdleConfirm.value = true
  }, IDLE_LIMIT)
}

const activityEvents = ["mousemove", "keydown", "mousedown", "scroll", "touchstart"]

onMounted(() => {
  activityEvents.forEach(e => window.addEventListener(e, resetIdleTimer))
  resetIdleTimer()
})

onUnmounted(() => {
  activityEvents.forEach(e => window.removeEventListener(e, resetIdleTimer))
  clearTimeout(idleTimer)
})

/* ================= ACTIONS ================= */
const confirmLogout = () => {
  showLogoutConfirm.value = true
}

const logoutNow = () => {
  showLogoutConfirm.value = false
  showIdleConfirm.value = false

  router.post("/logout", {}, {
    onFinish: () => {
      toastText.value = "You have been logged out"
      toast.value = true
    }
  })
}

const stayLoggedIn = () => {
  showIdleConfirm.value = false
  resetIdleTimer()
}
</script>

<template>
  <v-app class="admin-dark">

    <!-- ================= SIDEBAR ================= -->
    <v-navigation-drawer v-model="drawer" width="260" permanent class="sidebar">
      <div class="logo">WABA</div>

      <v-list density="compact" nav>
        <v-list-item
          v-for="item in menu.filter(m => m.roles.includes(userRole))"
          :key="item.href"
          :class="$page.url.startsWith(item.href) ? 'active-menu' : ''"
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

        <v-toolbar-title class="title">
          <slot name="title" />
        </v-toolbar-title>

        <v-spacer />

        <!-- USER MENU -->
        <v-menu location="bottom end">
          <template #activator="{ props }">
            <v-btn v-bind="props" variant="text" class="user-btn">
              <v-icon class="mr-1">mdi-account-circle</v-icon>
              {{ page.props.auth.user.name }}
              <v-icon size="18">mdi-chevron-down</v-icon>
            </v-btn>
          </template>

          <!-- ⬇️ FIX PUTIH DI SINI -->
          <v-theme-provider theme="dark">
            <v-list class="user-dropdown">
              <v-list-item>
                <Link href="/profile" class="dropdown-link">
                  <v-icon size="18" class="mr-2">mdi-account</v-icon>
                  Profile
                </Link>
              </v-list-item>

              <v-divider />

              <v-list-item @click="confirmLogout">
                <span class="logout-btn">
                  <v-icon size="18" class="mr-2">mdi-logout</v-icon>
                  Logout
                </span>
              </v-list-item>
            </v-list>
          </v-theme-provider>
        </v-menu>
      </v-app-bar>

      <!-- CONTENT -->
      <div class="content">
        <slot />
      </div>
    </v-main>

    <!-- ================= LOGOUT CONFIRM ================= -->
    <v-dialog v-model="showLogoutConfirm" max-width="420">
      <v-card class="dialog-dark">
        <v-card-title>Confirm Logout</v-card-title>
        <v-card-text>
          Are you sure you want to log out?
        </v-card-text>
        <v-card-actions class="justify-end">
          <v-btn variant="text" @click="showLogoutConfirm = false">
            Cancel
          </v-btn>
          <v-btn color="error" @click="logoutNow">
            Logout
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ================= IDLE CONFIRM ================= -->
    <v-dialog v-model="showIdleConfirm" max-width="420" persistent>
      <v-card class="dialog-dark">
        <v-card-title>Session Expiring</v-card-title>
        <v-card-text>
          You have been inactive for a while.<br />
          Do you want to stay logged in?
        </v-card-text>
        <v-card-actions class="justify-end">
          <v-btn variant="text" color="error" @click="logoutNow">
            Logout
          </v-btn>
          <v-btn color="primary" @click="stayLoggedIn">
            Stay
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ================= TOAST ================= -->
    <v-snackbar
      v-model="toast"
      location="top right"
      timeout="3000"
      class="toast-dark"
    >
      <v-icon class="mr-2" color="green">mdi-check-circle</v-icon>
      {{ toastText }}
    </v-snackbar>

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
}

/* ================= MENU ================= */
.menu-link {
  display: flex;
  align-items: center;
  width: 100%;
  color: #cbd5f5;
  text-decoration: none;
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

.title {
  color: #e5e7eb;
  font-weight: 600;
}

.user-btn {
  color: #e5e7eb !important;
}

/* ================= MAIN ================= */
.main-area {
  background: #0f172a;
}

.content {
  padding: 24px;
}

/* ================= DROPDOWN ================= */
.user-dropdown {
  background: linear-gradient(180deg, #020617, #0f172a);
  border: 1px solid rgba(255,255,255,.08);
  border-radius: 12px;
  padding: 6px 0;
}

.dropdown-link {
  display: flex;
  align-items: center;
  width: 100%;
  color: #e5e7eb;
  text-decoration: none;
}

.logout-btn {
  color: #f87171;
  cursor: pointer;
}

/* ================= DIALOG ================= */
.dialog-dark {
  background: linear-gradient(180deg, #020617, #0f172a);
  color: #e5e7eb;
  border-radius: 14px;
  border: 1px solid rgba(255,255,255,.08);
}

/* ================= TOAST ================= */
.toast-dark {
  background: #020617 !important;
  color: #e5e7eb !important;
  border: 1px solid rgba(255,255,255,.08);
  border-radius: 12px;
}
</style>
