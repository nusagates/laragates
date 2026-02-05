<template>
  <v-app class="admin-dark">

    <!-- ================= TOP BAR ================= -->
    <v-app-bar app flat class="topbar">
      <v-btn icon @click="drawer = !drawer" class="me-2">
        <v-icon>mdi-menu</v-icon>
      </v-btn>

      <v-container class="d-flex align-center">
        <Link :href="route('dashboard')" class="me-4">
          <ApplicationLogo class="h-9 w-auto" />
        </Link>

        <v-spacer />

        <!-- ===== USER DROPDOWN (FIX DARK) ===== -->
        <v-menu
          location="bottom end"
          transition="scale-transition"
          content-class="user-dropdown"
        >
          <template #activator="{ props }">
            <v-btn
              v-bind="props"
              variant="text"
              class="user-btn"
            >
              <v-icon class="mr-1">mdi-account-circle</v-icon>
              {{ $page.props.auth.user.name }}
              <v-icon end>mdi-chevron-down</v-icon>
            </v-btn>
          </template>

          <v-list density="compact">
            <v-list-item>
              <Link
                :href="route('profile.edit')"
                class="dropdown-link"
              >
                <v-icon size="18" class="mr-2">mdi-account</v-icon>
                Profile
              </Link>
            </v-list-item>

            <v-divider />

            <v-list-item @click="showLogoutConfirm = true">
              <span class="logout-btn">
                <v-icon size="18" class="mr-2">mdi-logout</v-icon>
                Logout
              </span>
            </v-list-item>
          </v-list>
        </v-menu>
      </v-container>
    </v-app-bar>

    <!-- ================= SIDEBAR ================= -->
    <v-navigation-drawer v-model="drawer" app class="sidebar">
      <v-list>
        <v-list-item
          link
          :active="route().current('dashboard')"
          v-to="route('dashboard')"
          title="Dashboard"
        />
        <v-divider />
        <v-list-item
          link
          :active="route().current('profile.edit')"
          v-to="route('profile.edit')"
        >
          <v-list-item-title>Profile</v-list-item-title>
        </v-list-item>
      </v-list>
    </v-navigation-drawer>

    <!-- ================= MAIN ================= -->
    <v-main class="main-area">
      <v-container>
        <slot name="header" v-if="$slots.header" />
        <slot />
      </v-container>
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

  </v-app>
</template>

<script setup>
import { ref } from 'vue'
import ApplicationLogo from '@/Components/ApplicationLogo.vue'
import { Link, router } from '@inertiajs/vue3'

const drawer = ref(false)
const showLogoutConfirm = ref(false)

const logoutNow = () => {
  showLogoutConfirm.value = false
  router.post('/logout')
}
</script>

<style scoped>
/* ================= GLOBAL ================= */
.admin-dark {
  background: linear-gradient(180deg, #020617, #0f172a);
  min-height: 100vh;
}

/* ================= TOPBAR ================= */
.topbar {
  background: #020617;
  border-bottom: 1px solid #1e293b;
  color: #e5e7eb;
}

.user-btn {
  color: #e5e7eb !important;
  font-weight: 500;
}

/* ================= SIDEBAR ================= */
.sidebar {
  background: #020617;
  color: #e5e7eb;
}

/* ================= DROPDOWN ================= */
.dropdown-link {
  display: flex;
  align-items: center;
  color: #e5e7eb;
  text-decoration: none;
  width: 100%;
}

/* Logout */
.logout-btn {
  display: flex;
  align-items: center;
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
</style>

<!-- ⚠️ HARUS GLOBAL -->
<style>
.user-dropdown {
  background: linear-gradient(180deg, #020617, #0f172a) !important;
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 12px;
  box-shadow: 0 20px 40px rgba(0,0,0,.6);
}

.user-dropdown .v-list-item {
  color: #e5e7eb;
}

.user-dropdown .v-list-item:hover {
  background: rgba(56, 189, 248, 0.12);
}
</style>
