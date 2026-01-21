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

  <v-app class="auth-bg">
    <v-container class="fill-height d-flex align-center justify-center">
      <v-card width="440" class="auth-card">
        <!-- HEADER -->
        <div class="text-center mb-8">
          <h2 class="title">Welcome back</h2>
          <p class="subtitle">
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
            :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
            @click:append-inner="showPassword = !showPassword"
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

          <!-- FOOTER -->
          <div class="text-center text-body-2">
            <!-- <Link
              v-if="canResetPassword"
              :href="route('password.request')"
              class="text-primary font-weight-medium text-decoration-none d-block mb-2"
            >
              Forgot password?
            </Link> -->

            <!-- <span class="text-grey-darken-1">
              Don’t have an account?
            </span> -->
          </div>
        </form>
      </v-card>
    </v-container>
  </v-app>
</template>

<style scoped>
/* BACKGROUND */
.auth-bg {
  background: radial-gradient(
    circle at top,
    #eef3fb,
    #f8fbff
  );
}

/* CARD */
.auth-card {
  padding: 40px;
  border-radius: 20px;
  box-shadow: 0 20px 40px rgba(15, 23, 42, 0.12);
}

/* TEXT */
.title {
  font-size: 24px;
  font-weight: 700;
}
.subtitle {
  font-size: 14px;
  color: #64748b;
}
</style>
