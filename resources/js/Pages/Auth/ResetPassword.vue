<script setup>
import { ref } from 'vue'
import { Head, useForm, Link } from '@inertiajs/vue3'

const props = defineProps({
  email: {
    type: String,
    required: true,
  },
  token: {
    type: String,
    required: true,
  },
})

const showPass = ref(false)
const showConfirm = ref(false)

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
})

const submit = () => {
  form.post(route('password.store'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>

<template>
  <Head title="Reset Password" />

  <v-container class="d-flex justify-center align-center" style="min-height:100vh; background:#f5f7fa;">
    <v-card width="420" class="pa-6" elevation="6" style="border-radius:14px;">

      <div class="text-center mb-4">
        <h2 class="text-h5 font-weight-medium mb-1">Reset Password</h2>
        <p class="text-body-2 text-grey">
          Set a strong and secure password for your account.
        </p>
      </div>

      <form @submit.prevent="submit">

        <!-- Email (read-only) -->
        <v-text-field
          v-model="form.email"
          label="Email"
          type="email"
          prepend-inner-icon="mdi-email-outline"
          disabled
          variant="outlined"
          class="mb-3"
        />

        <!-- New Password -->
        <v-text-field
          v-model="form.password"
          :type="showPass ? 'text' : 'password'"
          label="New Password"
          prepend-inner-icon="mdi-lock-outline"
          :append-icon="showPass ? 'mdi-eye-off' : 'mdi-eye'"
          @click:append="showPass = !showPass"
          :error-messages="form.errors.password"
          autocomplete="new-password"
          required
          variant="outlined"
          class="mb-3"
        />

        <!-- Confirm Password -->
        <v-text-field
          v-model="form.password_confirmation"
          :type="showConfirm ? 'text' : 'password'"
          label="Confirm Password"
          prepend-inner-icon="mdi-lock-check-outline"
          :append-icon="showConfirm ? 'mdi-eye-off' : 'mdi-eye'"
          @click:append="showConfirm = !showConfirm"
          :error-messages="form.errors.password_confirmation"
          autocomplete="new-password"
          required
          variant="outlined"
          class="mb-4"
        />

        <v-btn
          color="primary"
          type="submit"
          :loading="form.processing"
          :disabled="form.processing"
          block
        >
          Reset Password
        </v-btn>

        <div class="text-center mt-3">
          <Link :href="route('login')" class="text-body-2 text-decoration-underline text-primary">
            Return to Login
          </Link>
        </div>

      </form>
    </v-card>
  </v-container>
</template>
