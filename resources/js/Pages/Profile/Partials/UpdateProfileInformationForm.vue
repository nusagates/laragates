<template>
  <v-sheet elevation="0"  max-width="500">
    <div class="text-h6 mb-1">Profile Information</div>
    <div class="mb-4 text-body-2">
      Update your account's profile information and email address.
    </div>
    <v-form @submit.prevent="form.patch(route('profile.update'))">
      <v-text-field
          v-model="form.name"
          label="Name"
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
          :error-messages="form.errors.email"
          required
          autocomplete="username"
          class="mb-4"
      />
      <div v-if="mustVerifyEmail && user.email_verified_at === null" class="mb-4">
        <div class="text-body-2 mb-2">
          Your email address is unverified.
          <v-btn
              variant="text"
              size="small"
              @click="$inertia.post(route('verification.send'))"
          >
            Click here to re-send the verification email.
          </v-btn>
        </div>
        <div
            v-show="status === 'verification-link-sent'"
            class="text-success text-body-2"
        >
          A new verification link has been sent to your email address.
        </div>
      </div>
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
import { useForm, usePage } from '@inertiajs/vue3';

defineProps({
  mustVerifyEmail: Boolean,
  status: String,
});

const user = usePage().props.auth.user;

const form = useForm({
  name: user.name,
  email: user.email,
});
</script>