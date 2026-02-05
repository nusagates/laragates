<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head } from '@inertiajs/vue3'

import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue'
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue'
import DeleteUserForm from './Partials/DeleteUserForm.vue'

defineProps({
  mustVerifyEmail: Boolean,
  status: String,
})
</script>

<template>
  <Head title="Profile" />

  <AdminLayout>
    <template #title>Profile</template>

    <v-row justify="center" class="pt-2">
      <v-col cols="12" md="8" lg="6">

        <!-- ================= PROFILE INFO ================= -->
        <v-card class="waba-card mb-8">
          <div class="card-header">
            <div class="card-title">Profile Information</div>
            <div class="card-subtitle">
              Update your account profile information and email address
            </div>
          </div>

          <UpdateProfileInformationForm
            :must-verify-email="mustVerifyEmail"
            :status="status"
          />
        </v-card>

        <!-- ================= PASSWORD ================= -->
        <v-card class="waba-card mb-8">
          <div class="card-header">
            <div class="card-title">Update Password</div>
            <div class="card-subtitle">
              Use a strong and unique password to keep your account secure
            </div>
          </div>

          <UpdatePasswordForm />
        </v-card>

        <!-- ================= DELETE ================= -->
        <v-card class="waba-card danger">
          <div class="card-header">
            <div class="card-title text-danger">Delete Account</div>
            <div class="card-subtitle">
              Permanently delete your account and all associated data
            </div>
          </div>

          <DeleteUserForm />
        </v-card>

      </v-col>
    </v-row>
  </AdminLayout>
</template>

<style scoped>
/* =========================================================
   WABA PROFILE – DARK PREMIUM
========================================================= */

.waba-card {
  background: linear-gradient(180deg, #020617, #0f172a);
  border: 1px solid rgba(255,255,255,.06);
  border-radius: 16px;
  padding: 24px;
  color: #e5e7eb;
}

/* header */
.card-header {
  margin-bottom: 16px;
}

.card-title {
  font-size: 16px;
  font-weight: 600;
}

.card-subtitle {
  font-size: 12px;
  color: #94a3b8;
  margin-top: 2px;
}

/* danger zone */
.waba-card.danger {
  border-color: rgba(239,68,68,.25);
}

.text-danger {
  color: #ef4444;
}

/* remove vuetify shadow */
:deep(.v-card) {
  box-shadow: none !important;
}
</style>
