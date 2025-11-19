<template>
  <v-app class="login-bg"
    style="
      background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)),
      url('/images/landing-bg.jpg');
      background-size: cover;
      background-position: center;
      min-height: 100vh;
    "
  >
    <v-container class="d-flex justify-center align-center" style="min-height: 100vh;">
      <Head title="Log in" />

      <v-card
        width="420"
        elevation="12"
        class="rounded-lg pa-8"
        style="background-color: rgba(255,255,255,0.92);"
      >
        <div class="text-center mb-6">
          <h2 class="text-h5 font-weight-bold" style="color:#333">Welcome Back</h2>
          <p class="text-body-2" style="color:#666">Please enter your account</p>
        </div>

        <form @submit.prevent="submit">

          <!-- EMAIL -->
          <v-text-field
            v-model="form.email"
            label="Email"
            type="email"
            variant="outlined"
            density="comfortable"
            :error-messages="form.errors.email"
            required
            autofocus
            autocomplete="email"
            class="mb-4"
          />

          <!-- PASSWORD -->
          <v-text-field
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            label="Password"
            variant="outlined"
            density="comfortable"
            :append-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
            @click:append="showPassword = !showPassword"
            :error-messages="form.errors.password"
            required
            autocomplete="current-password"
            class="mb-2"
          />

          <!-- Remember -->
          <v-checkbox
            v-model="form.remember"
            label="Remember me"
            density="compact"
            class="mt-0"
          />

          <!-- Forgot + Login -->
          <div class="d-flex justify-space-between align-center mt-4">
            <Link
              v-if="canResetPassword"
              :href="route('password.request')"
              class="text-blue text-decoration-underline"
            >
              Forgot password?
            </Link>

            <v-btn
              :loading="form.processing"
              :disabled="form.processing"
              color="primary"
              type="submit"
            >
              Log in
            </v-btn>
          </div>

        </form>

      </v-card>
    </v-container>
  </v-app>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
  canResetPassword: Boolean,
  status: String,
});

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const showPassword = ref(false);

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  });
};
</script>
