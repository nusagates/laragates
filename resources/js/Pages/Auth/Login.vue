<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

defineProps({
  canResetPassword: Boolean,
  status: String,
})

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

const showPassword = ref(false)

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  })
}
</script>

<template>
  <Head title="Login – WABA" />

  <v-app
    style="
      background: linear-gradient(135deg, #eaf1fb, #f8fbff);
      min-height: 100vh;
    "
  >
    <v-container
      class="d-flex justify-center align-center"
      style="min-height:100vh;"
    >
      <v-card
        width="440"
        elevation="12"
        class="pa-10 rounded-xl"
      >
        <!-- HEADER -->
        <div class="text-center mb-8">
          <h2 class="text-h5 font-weight-bold mb-1">
            Welcome back
          </h2>
          <p class="text-body-2 text-grey-darken-1">
            Sign in to continue to WABA
          </p>
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
            class="mb-4"
            autofocus
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
            class="mb-2"
            required
          />

          <!-- REMEMBER -->
          <v-checkbox
            v-model="form.remember"
            label="Remember me"
            density="compact"
            class="mt-1 mb-6"
          />

          <!-- LOGIN BUTTON -->
          <v-btn
            block
            size="large"
            color="primary"
            type="submit"
            :loading="form.processing"
            :disabled="form.processing"
            class="mb-4"
          >
            Sign In
          </v-btn>

          <!-- FOOTER LINKS -->
          <div class="text-center">
            <Link
              v-if="canResetPassword"
              :href="route('password.request')"
              class="text-primary text-decoration-none font-weight-medium d-block mb-2"
            >
              Forgot password?
            </Link>

            <span class="text-body-2 text-grey-darken-1">
              Don’t have an account?
            </span>
            <Link
              :href="route('register')"
              class="ml-1 text-primary text-decoration-none font-weight-medium"
            >
              Create one
            </Link>
          </div>

        </form>
      </v-card>
    </v-container>
  </v-app>
</template>
