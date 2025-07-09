<template>
  <v-container class="d-flex justify-center align-center" style="min-height: 100vh;">
    <Head title="Log in" />
    <v-card width="400">
      <v-card-title class="text-h5">Log in</v-card-title>
      <v-card-text>
        <form @submit.prevent="submit">
          <v-text-field
              v-model="form.email"
              label="Email"
              type="email"
              :error-messages="form.errors.email"
              required
              autofocus
              autocomplete="email"
          />
          <v-text-field
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              label="Password"
              :append-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
              @click:append="showPassword = !showPassword"
              :error-messages="form.errors.password"
              required
              autocomplete="current-password"
          />
          <v-checkbox
              v-model="form.remember"
              label="Remember me"
              class="mt-2"
          />
          <div class="d-flex justify-end align-center mt-4">
            <Link
                v-if="canResetPassword"
                :href="route('password.request')"
                class="me-4 text-decoration-underline"
            >
              Forgot your password?
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
      </v-card-text>
    </v-card>
  </v-container>
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