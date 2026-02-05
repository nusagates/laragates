<template>
  <v-sheet
    elevation="0"
    max-width="520"
    class="password-sheet"
  >
    <!-- HEADER -->
    <div class="sheet-header">
      <div class="sheet-title"></div>
      <div class="sheet-subtitle">
       </div>
    </div>

    <!-- FORM -->
    <v-form @submit.prevent="updatePassword">

      <!-- CURRENT -->
      <v-text-field
        v-model="form.current_password"
        :type="showCurrent ? 'text' : 'password'"
        label="Current Password"
        variant="outlined"
        density="comfortable"
        :append-inner-icon="showCurrent ? 'mdi-eye-off' : 'mdi-eye'"
        @click:append-inner="showCurrent = !showCurrent"
        :error-messages="form.errors.current_password"
        autocomplete="current-password"
        class="mb-4"
        required
      />

      <!-- NEW -->
      <v-text-field
        v-model="form.password"
        :type="showNew ? 'text' : 'password'"
        label="New Password"
        variant="outlined"
        density="comfortable"
        :append-inner-icon="showNew ? 'mdi-eye-off' : 'mdi-eye'"
        @click:append-inner="showNew = !showNew"
        :error-messages="form.errors.password"
        autocomplete="new-password"
        class="mb-4"
        required
      />

      <!-- CONFIRM -->
      <v-text-field
        v-model="form.password_confirmation"
        :type="showConfirm ? 'text' : 'password'"
        label="Confirm Password"
        variant="outlined"
        density="comfortable"
        :append-inner-icon="showConfirm ? 'mdi-eye-off' : 'mdi-eye'"
        @click:append-inner="showConfirm = !showConfirm"
        :error-messages="form.errors.password_confirmation"
        autocomplete="new-password"
        class="mb-4"
        required
      />

      <!-- ACTION -->
      <div class="actions">
        <v-btn
          :loading="form.processing"
          :disabled="form.processing"
          color="primary"
          type="submit"
          size="large"
        >
          Save Password
        </v-btn>

        <span
          v-if="form.recentlySuccessful"
          class="saved-text"
        >
          Password updated
        </span>
      </div>

    </v-form>

    <v-divider class="divider" />
  </v-sheet>
</template>

<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'

/* ================= LOGIC ASLI (TIDAK DIUBAH) ================= */

const form = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const showCurrent = ref(false)
const showNew = ref(false)
const showConfirm = ref(false)

const updatePassword = () => {
  form.put(route('password.update'), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
  })
}
</script>

<style scoped>
/* =========================================================
   WABA – UPDATE PASSWORD FORM
========================================================= */

.password-sheet {
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

/* dark inputs */
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
