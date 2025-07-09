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
  <v-container class="d-flex justify-center align-center" style="min-height: 100vh;">
    <Head title="Register" />
    <v-card width="400">
      <v-card-title class="text-h5">Register</v-card-title>
      <v-card-text>
        <form @submit.prevent="submit">
          <v-text-field
              v-model="form.name"
              label="Name"
              :error-messages="form.errors.name"
              required
              autofocus
              autocomplete="name"
          />
          <v-text-field
              v-model="form.email"
              label="Email"
              type="email"
              :error-messages="form.errors.email"
              required
              autocomplete="username"
          />
          <v-text-field
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              label="Password"
              :append-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
              @click:append="showPassword = !showPassword"
              :error-messages="form.errors.password"
              required
              autocomplete="new-password"
          />
          <v-text-field
              v-model="form.password_confirmation"
              :type="showPasswordConfirmation ? 'text' : 'password'"
              label="Confirm Password"
              :append-icon="showPasswordConfirmation ? 'mdi-eye-off' : 'mdi-eye'"
              @click:append="showPasswordConfirmation = !showPasswordConfirmation"
              :error-messages="form.errors.password_confirmation"
              required
              autocomplete="new-password"
          />
          <div class="d-flex justify-end align-center mt-4">
            <Link
                v-to="route('login')"
                class="me-4 text-decoration-underline"
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
      </v-card-text>
    </v-card>
  </v-container>
</template>