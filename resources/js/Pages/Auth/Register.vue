<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: '',
})

const showPassword = ref(false)
const showPasswordConfirmation = ref(false)

const submit = () => {
  form.post(route('register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>

<template>
  <Head title="Register – WABA" />

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
        width="460"
        elevation="12"
        class="pa-10 rounded-xl"
      >
        <!-- HEADER -->
        <div class="text-center mb-8">
          <h2 class="text-h5 font-weight-bold mb-1">
            Create your account
          </h2>
          <p class="text-body-2 text-grey-darken-1">
            Start managing WhatsApp customer service professionally
          </p>
        </div>

        <form @submit.prevent="submit">

          <!-- FULL NAME -->
          <v-text-field
            v-model="form.name"
            label="Full Name"
            variant="outlined"
            density="comfortable"
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

          <!-- ROLE -->
          <v-select
            v-model="form.role"
            :items="[
              { title: 'Super Admin', value: 'superadmin' },
              { title: 'Admin', value: 'admin' },
              { title: 'Supervisor', value: 'supervisor' },
              { title: 'Agent', value: 'agent' },
            ]"
            label="Role"
            item-title="title"
            item-value="value"
            variant="outlined"
            density="comfortable"
            :error-messages="form.errors.role"
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

          <!-- ACTIONS -->
          <v-btn
            block
            size="large"
            color="primary"
            type="submit"
            :loading="form.processing"
            :disabled="form.processing"
            class="mb-4"
          >
            Create Account
          </v-btn>

          <div class="text-center">
            <span class="text-body-2 text-grey-darken-1">
              Already have an account?
            </span>
            <Link
              :href="route('login')"
              class="ml-1 text-primary text-decoration-none font-weight-medium"
            >
              Sign in
            </Link>
          </div>

        </form>
      </v-card>
    </v-container>
  </v-app>
</template>
