<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
});

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const submit = () => {
  form.post(route('register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
};
</script>

<template>
  <v-app
    class="register-bg"
    style="
      background: linear-gradient(rgba(0,0,0,0.55), rgba(0,0,0,0.55)),
      url('/images/landing-bg.jpg');
      background-size: cover;
      background-position: center;
      min-height: 100vh;
    "
  >
    <v-container class="d-flex justify-center align-center" style="min-height: 100vh;">
      <Head title="Register" />

      <v-card
        width="430"
        elevation="12"
        class="rounded-lg pa-8"
        style="background-color: rgba(255,255,255,0.92);"
      >
        <div class="text-center mb-6">
          <h2 class="text-h5 font-weight-bold" style="color:#333">Create Account</h2>
          <p class="text-body-2" style="color:#666">
            Fill the form to register your new account
          </p>
        </div>

        <form @submit.prevent="submit">

          <!-- FULL NAME -->
          <v-text-field
            v-model="form.name"
            label="Full Name"
            variant="outlined"
            density="comfortable"
            autofocus
            :error-messages="form.errors.name"
            class="mb-4"
            required
          />

          <!-- EMAIL -->
          <v-text-field
            v-model="form.email"
            label="Email"
            type="email"
            variant="outlined"
            density="comfortable"
            :error-messages="form.errors.email"
            class="mb-4"
            required
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
            class="mb-4"
            required
          />

          <!-- CONFIRM PASSWORD -->
          <v-text-field
            v-model="form.password_confirmation"
            :type="showPasswordConfirmation ? 'text' : 'password'"
            label="Confirm Password"
            variant="outlined"
            density="comfortable"
            :append-icon="showPasswordConfirmation ? 'mdi-eye-off' : 'mdi-eye'"
            @click:append="showPasswordConfirmation = !showPasswordConfirmation"
            :error-messages="form.errors.password_confirmation"
            class="mb-6"
            required
          />

          <!-- Footer buttons -->
          <div class="d-flex justify-space-between align-center mt-2">
            <Link
              :href="route('login')"
              class="text-blue text-decoration-underline"
            >
              Already registered?
            </Link>

            <v-btn
              :loading="form.processing"
              :disabled="form.processing"
              color="primary"
              type="submit"
            >
              Register
            </v-btn>
          </div>

        </form>
      </v-card>
    </v-container>
  </v-app>
</template>
