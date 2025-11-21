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

        <!-- Profile Info -->
        <v-card elevation="2" class="pa-6 mb-8">
          <h3 class="text-h6 font-weight-bold mb-1">Profile Information</h3>
          <p class="text-body-2 mb-5 text-grey-darken-1">
            Update your account's profile information and email address.
          </p>

          <UpdateProfileInformationForm
            :must-verify-email="mustVerifyEmail"
            :status="status"
          />
        </v-card>

        <!-- Password Update -->
        <v-card elevation="2" class="pa-6 mb-8">
          <h3 class="text-h6 font-weight-bold mb-1">Update Password</h3>
          <p class="text-body-2 mb-5 text-grey-darken-1">
            Ensure your account is using a strong password for security.
          </p>

          <UpdatePasswordForm />
        </v-card>

        <!-- Delete Account -->
        <v-card elevation="2" class="pa-6">
          <h3 class="text-h6 font-weight-bold mb-1 text-red">Delete Account</h3>
          <p class="text-body-2 mb-5 text-grey-darken-1">
            Permanently delete your account.
          </p>

          <DeleteUserForm />
        </v-card>

      </v-col>
    </v-row>
  </AdminLayout>
</template>

<style scoped>
.text-red { color: #d32f2f; }
</style>
