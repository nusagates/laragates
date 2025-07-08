<template>
  <v-sheet elevation="0" max-width="500">
    <div class="text-h6 mb-1">Delete Account</div>
    <div class="mb-4 text-body-2">
      Once your account is deleted, all of its resources and data will be permanently deleted.
      Before deleting your account, please download any data or information that you wish to retain.
    </div>
    <v-btn color="error" @click="confirmUserDeletion" :disabled="form.processing">
      Delete Account
    </v-btn>

    <v-dialog v-model="confirmingUserDeletion" max-width="500">
      <v-card>
        <v-card-title class="text-h6">
          Are you sure you want to delete your account?
        </v-card-title>
        <v-card-text>
          <div class="mb-4">
            Once your account is deleted, all of its resources and data will be permanently deleted.
            Please enter your password to confirm you would like to permanently delete your account.
          </div>
          <v-text-field
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              label="Password"
              :append-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
              @click:append="showPassword = !showPassword"
              :error-messages="form.errors.password"
              autocomplete="current-password"
              @keyup.enter="deleteUser"
              ref="passwordInput"
              required
          />
        </v-card-text>
        <v-card-actions class="justify-end">
          <v-btn variant="text" @click="closeModal" :disabled="form.processing">
            Cancel
          </v-btn>
          <v-btn color="error" @click="deleteUser" :loading="form.processing" :disabled="form.processing">
            Delete Account
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-sheet>
</template>

<script setup>
import { ref, nextTick } from 'vue';
import { useForm } from '@inertiajs/vue3';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);
const showPassword = ref(false);

const form = useForm({
  password: '',
});

const confirmUserDeletion = () => {
  confirmingUserDeletion.value = true;
  nextTick(() => passwordInput.value && passwordInput.value.focus());
};

const deleteUser = () => {
  form.delete(route('profile.destroy'), {
    preserveScroll: true,
    onSuccess: () => closeModal(),
    onError: () => passwordInput.value && passwordInput.value.focus(),
    onFinish: () => form.reset(),
  });
};

const closeModal = () => {
  confirmingUserDeletion.value = false;
  form.clearErrors();
  form.reset();
};
</script>