<template>
  <v-sheet
    elevation="0"
    max-width="520"
    class="profile-sheet"
  >
    <!-- HEADER -->
    <div class="sheet-header">
      <div class="sheet-title"></div>
      <div class="sheet-subtitle">
        </div>
    </div>

    <!-- FORM -->
    <v-form @submit.prevent="form.patch(route('profile.update'))">

      <v-text-field
        v-model="form.name"
        label="Name"
        variant="outlined"
        density="comfortable"
        :error-messages="form.errors.name"
        required
        autofocus
        autocomplete="name"
        class="mb-4"
      />

      <v-text-field
        v-model="form.email"
        label="Email"
        type="email"
        variant="outlined"
        density="comfortable"
        :error-messages="form.errors.email"
        required
        autocomplete="username"
        class="mb-4"
      />

      <!-- EMAIL VERIFICATION -->
      <div
        v-if="mustVerifyEmail && user.email_verified_at === null"
        class="verify-box mb-4"
      >
        <div class="verify-text">
          Your email address is unverified.
        </div>

        <v-btn
          variant="text"
          size="small"
          class="verify-action"
          @click="$inertia.post(route('verification.send'))"
        >
          Resend verification email
        </v-btn>

        <div
          v-show="status === 'verification-link-sent'"
          class="verify-success"
        >
          A new verification link has been sent to your email.
        </div>
      </div>

      <!-- ACTION -->
      <div class="actions">
        <v-btn
          :loading="form.processing"
          :disabled="form.processing"
          color="primary"
          type="submit"
          size="large"
        >
          Save Changes
        </v-btn>

        <span
          v-if="form.recentlySuccessful"
          class="saved-text"
        >
          Saved
        </span>
      </div>

    </v-form>

    <v-divider class="divider" />
  </v-sheet>
</template>

<script setup>
import { useForm, usePage } from '@inertiajs/vue3'

defineProps({
  mustVerifyEmail: Boolean,
  status: String,
})

const user = usePage().props.auth.user

const form = useForm({
  name: user.name,
  email: user.email,
})
</script>

<style scoped>
/* =========================================================
   WABA – PROFILE FORM
========================================================= */

.profile-sheet {
  background: transparent;
  color: #e5e7eb;
}

/* header */
.sheet-header {
  margin-bottom: 20px;
}
.sheet-title {
  font-size: 16px;
  font-weight: 600;
}
.sheet-subtitle {
  font-size: 12px;
  color: #94a3b8;
  margin-top: 4px;
}

/* input dark polish */
:deep(.v-field) {
  background: rgba(255,255,255,.03);
}
:deep(.v-field__outline) {
  border-color: rgba(255,255,255,.08);
}
:deep(.v-label) {
  color: #94a3b8;
}
:deep(input) {
  color: #e5e7eb;
}

/* verify email box */
.verify-box {
  background: rgba(59,130,246,.08);
  border: 1px solid rgba(59,130,246,.25);
  border-radius: 12px;
  padding: 12px 14px;
}
.verify-text {
  font-size: 12px;
  color: #c7d2fe;
}
.verify-action {
  padding: 0;
  margin-top: 4px;
  font-size: 12px;
}
.verify-success {
  font-size: 12px;
  color: #22c55e;
  margin-top: 6px;
}

/* actions */
.actions {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 8px;
}
.saved-text {
  font-size: 12px;
  color: #94a3b8;
}

/* divider */
.divider {
  margin-top: 32px;
  border-color: rgba(255,255,255,.06);
}
</style>
