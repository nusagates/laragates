<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';

const form = useForm({
  email: '',
});

const submit = () => {
  form.post(route('password.email'));
};
</script>

<template>
  <Head title="Forgot Password" />

  <v-container class="d-flex justify-center align-center" style="min-height: 100vh; background: #f5f7fa;">
    <v-card width="420" class="pa-6" elevation="6" style="border-radius: 14px;">
      
      <div class="text-center mb-4">
        <h2 class="text-h5 font-weight-medium mb-1">Forgot Password?</h2>
        <p class="text-body-2 text-grey">
          Don't worry! We will send a password reset link to your email.
        </p>
      </div>

      <form @submit.prevent="submit">
        <v-text-field
          v-model="form.email"
          label="Email"
          type="email"
          :error-messages="form.errors.email"
          required
          autofocus
          autocomplete="email"
          variant="outlined"
          class="mb-3"
        />

        <v-btn
          :loading="form.processing"
          :disabled="form.processing"
          color="primary"
          type="submit"
          block
          class="mb-2"
        >
          Send Reset Link
        </v-btn>

        <div class="text-center mt-2">
          <Link :href="route('login')" class="text-decoration-underline text-primary text-body-2">
            Back to Login
          </Link>
        </div>

      </form>
    </v-card>
  </v-container>
</template>
