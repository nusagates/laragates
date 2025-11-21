<script setup>
import { ref } from 'vue'
import { Head, useForm, Link } from '@inertiajs/vue3'

const showPassword = ref(false)

const form = useForm({
  password: '',
})

const submit = () => {
  form.post(route('password.confirm'), {
    onFinish: () => form.reset('password'),
  })
}
</script>

<template>
  <Head title="Confirm Password" />

  <v-container class="d-flex justify-center align-center" style="min-height: 100vh; background:#f5f7fa;">
    <v-card width="420" class="pa-6" elevation="6" style="border-radius:14px;">

      <div class="text-center mb-4">
        <h2 class="text-h5 font-weight-medium mb-1">Confirm Password</h2>
        <p class="text-body-2 text-grey">
          This is a secure area, please confirm your password to continue.
        </p>
      </div>

      <form @submit.prevent="submit">
        <v-text-field
          v-model="form.password"
          :type="showPassword ? 'text' : 'password'"
          label="Password"
          :error-messages="form.errors.password"
          prepend-inner-icon="mdi-lock-outline"
          :append-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
          @click:append="showPassword = !showPassword"
          required
          autocomplete="current-password"
          autofocus
          variant="outlined"
          class="mb-3"
        />

        <v-btn
          :loading="form.processing"
          :disabled="form.processing"
          color="primary"
          type="submit"
          block
        >
          Confirm Password
        </v-btn>

        <div class="text-center mt-2">
          <Link :href="route('password.request')" class="text-body-2 text-decoration-underline text-primary">
            Forgot your password?
          </Link>
        </div>

      </form>
    </v-card>
  </v-container>
</template>
