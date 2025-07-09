<template>
  <v-sheet elevation="0" max-width="500">
    <div class="text-h6 mb-1">Update Password</div>
    <div class="mb-4 text-body-2">
      Ensure your account is using a long, random password to stay secure.
    </div>
    <v-form @submit.prevent="updatePassword">
      <v-text-field
          v-model="form.current_password"
          :type="showCurrent ? 'text' : 'password'"
          label="Current Password"
          :append-icon="showCurrent ? 'mdi-eye-off' : 'mdi-eye'"
          @click:append="showCurrent = !showCurrent"
          :error-messages="form.errors.current_password"
          autocomplete="current-password"
          class="mb-4"
          required
      />
      <v-text-field
          v-model="form.password"
          :type="showNew ? 'text' : 'password'"
          label="New Password"
          :append-icon="showNew ? 'mdi-eye-off' : 'mdi-eye'"
          @click:append="showNew = !showNew"
          :error-messages="form.errors.password"
          autocomplete="new-password"
          class="mb-4"
          required
      />
      <v-text-field
          v-model="form.password_confirmation"
          :type="showConfirm ? 'text' : 'password'"
          label="Confirm Password"
          :append-icon="showConfirm ? 'mdi-eye-off' : 'mdi-eye'"
          @click:append="showConfirm = !showConfirm"
          :error-messages="form.errors.password_confirmation"
          autocomplete="new-password"
          class="mb-4"
          required
      />
      <v-btn
          :loading="form.processing"
          :disabled="form.processing"
          color="primary"
          type="submit"
      >
        Save
      </v-btn>
      <span v-if="form.recentlySuccessful" class="text-body-2 text-grey-darken-1 ms-4">
          Saved.
        </span>
    </v-form>
    <v-divider thickness="2" class="mt-12"/>
  </v-sheet>
</template>

<script setup>
import {ref} from 'vue';
import {useForm} from '@inertiajs/vue3';

const form = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const showCurrent = ref(false);
const showNew = ref(false);
const showConfirm = ref(false);

const updatePassword = () => {
  form.put(route('password.update'), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
  });
};
</script>