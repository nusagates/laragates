<template>
  <v-sheet
    elevation="0"
    max-width="520"
    class="delete-sheet"
  >
    <!-- HEADER -->
    <div class="sheet-header danger">
      <div class="sheet-title"></div>
      <div class="sheet-subtitle">
        </div>
    </div>

    <!-- DESCRIPTION -->
    <div class="warning-text">
      Once your account is deleted, all associated data will be permanently
      removed from the system. Please make sure you have backed up any important
      information before continuing.
    </div>

    <!-- ACTION -->
    <v-btn
      color="error"
      variant="outlined"
      size="large"
      class="mt-4"
      :disabled="form.processing"
      @click="confirmUserDeletion"
    >
      Delete Account
    </v-btn>

    <!-- CONFIRM DIALOG -->
    <v-dialog
      v-model="confirmingUserDeletion"
      max-width="460"
    >
      <v-card class="confirm-card">

        <v-card-title class="confirm-title">
          Confirm Account Deletion
        </v-card-title>

        <v-card-text class="confirm-text">
          <p>
            This will permanently delete your account and all associated data.
            Please enter your password to confirm this action.
          </p>

          <v-text-field
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            label="Password"
            variant="outlined"
            density="comfortable"
            :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
            @click:append-inner="showPassword = !showPassword"
            :error-messages="form.errors.password"
            autocomplete="current-password"
            @keyup.enter="deleteUser"
            ref="passwordInput"
            required
          />
        </v-card-text>

        <v-card-actions class="confirm-actions">
          <v-btn
            variant="text"
            @click="closeModal"
            :disabled="form.processing"
          >
            Cancel
          </v-btn>

          <v-btn
            color="error"
            :loading="form.processing"
            :disabled="form.processing"
            @click="deleteUser"
          >
            Delete Permanently
          </v-btn>
        </v-card-actions>

      </v-card>
    </v-dialog>

    <v-divider class="divider" />
  </v-sheet>
</template>

<script setup>
import { ref, nextTick } from 'vue'
import { useForm } from '@inertiajs/vue3'

/* ================= LOGIC ASLI (TIDAK DIUBAH) ================= */

const confirmingUserDeletion = ref(false)
const passwordInput = ref(null)
const showPassword = ref(false)

const form = useForm({
  password: '',
})

const confirmUserDeletion = () => {
  confirmingUserDeletion.value = true
  nextTick(() => passwordInput.value && passwordInput.value.focus())
}

const deleteUser = () => {
  form.delete(route('profile.destroy'), {
    preserveScroll: true,
    onSuccess: () => closeModal(),
    onError: () => passwordInput.value && passwordInput.value.focus(),
    onFinish: () => form.reset(),
  })
}

const closeModal = () => {
  confirmingUserDeletion.value = false
  form.clearErrors()
  form.reset()
}
</script>

<style scoped>
/* =========================================================
   WABA – DELETE ACCOUNT (DANGER ZONE)
========================================================= */

.delete-sheet {
  background: transparent;
  color: #e5e7eb;
}

/* header */
.sheet-header {
  margin-bottom: 12px;
}
.sheet-header.danger .sheet-title {
  color: #f87171;
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

/* warning text */
.warning-text {
  font-size: 13px;
  color: #cbd5f5;
  line-height: 1.6;
  background: rgba(248,113,113,.08);
  border: 1px solid rgba(248,113,113,.2);
  padding: 14px;
  border-radius: 12px;
}

/* dialog card */
.confirm-card {
  background: linear-gradient(180deg, #020617, #0f172a);
  border: 1px solid rgba(255,255,255,.08);
  border-radius: 16px;
  color: #e5e7eb;
}
.confirm-title {
  font-size: 16px;
  font-weight: 600;
}
.confirm-text {
  font-size: 13px;
  color: #cbd5f5;
}

/* actions */
.confirm-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

/* inputs dark */
:deep(.v-field) {
  background: rgba(255,255,255,.03);
}
:deep(.v-label) {
  color: #94a3b8;
}
:deep(input) {
  color: #e5e7eb;
}

/* divider */
.divider {
  margin-top: 32px;
  border-color: rgba(255,255,255,.06);
}
</style>
