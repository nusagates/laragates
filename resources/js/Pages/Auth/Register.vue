<script setup>
import { ref, computed } from 'vue'
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

/* ===============================
   PASSWORD STRENGTH
=============================== */
const strengthScore = computed(() => {
  let score = 0
  const v = form.password

  if (v.length >= 8) score++
  if (/[A-Z]/.test(v)) score++
  if (/[a-z]/.test(v)) score++
  if (/[0-9]/.test(v)) score++
  if (/[^A-Za-z0-9]/.test(v)) score++

  return score
})

const strengthLabel = computed(() => {
  if (strengthScore.value <= 2) return 'Weak'
  if (strengthScore.value <= 4) return 'Medium'
  return 'Strong'
})

const strengthColor = computed(() => {
  if (strengthScore.value <= 2) return 'error'
  if (strengthScore.value <= 4) return 'warning'
  return 'success'
})

const strengthPercent = computed(() => (strengthScore.value / 5) * 100)
const canSubmit = computed(() => strengthScore.value >= 4)

const submit = () => {
  form.post(route('register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>

<template>
  <Head title="Register – WABA" />

  <v-app class="register-bg">
    <v-container class="fill-height d-flex align-center justify-center">
      <v-card width="460" class="register-card">
        <!-- HEADER -->
        <div class="text-center mb-8">
          <h2 class="title">Create your account</h2>
          <p class="subtitle">
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
            label="Role"
            :items="[
              { title: 'Super Admin', value: 'superadmin' },
              { title: 'Admin', value: 'admin' },
              { title: 'Supervisor', value: 'supervisor' },
              { title: 'Agent', value: 'agent' },
            ]"
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
            :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
            @click:append-inner="showPassword = !showPassword"
            :error-messages="form.errors.password"
            class="mb-2"
            required
          />

          <!-- STRENGTH BAR -->
          <v-progress-linear
            :model-value="strengthPercent"
            :color="strengthColor"
            height="6"
            rounded
            class="mb-2"
          />

          <div class="d-flex justify-space-between text-caption mb-3">
            <span>Password strength</span>
            <span
              :class="{
                'text-error': strengthLabel === 'Weak',
                'text-warning': strengthLabel === 'Medium',
                'text-success': strengthLabel === 'Strong',
              }"
            >
              {{ strengthLabel }}
            </span>
          </div>

          <!-- RULES -->
          <ul class="password-rules mb-4">
            <li :class="{ ok: form.password.length >= 8 }">Min. 8 characters</li>
            <li :class="{ ok: /[A-Z]/.test(form.password) }">Uppercase letter</li>
            <li :class="{ ok: /[a-z]/.test(form.password) }">Lowercase letter</li>
            <li :class="{ ok: /[0-9]/.test(form.password) }">Number</li>
            <li :class="{ ok: /[^A-Za-z0-9]/.test(form.password) }">Symbol</li>
          </ul>

          <!-- CONFIRM PASSWORD -->
          <v-text-field
            v-model="form.password_confirmation"
            :type="showPasswordConfirmation ? 'text' : 'password'"
            label="Confirm Password"
            variant="outlined"
            density="comfortable"
            :append-inner-icon="showPasswordConfirmation ? 'mdi-eye-off' : 'mdi-eye'"
            @click:append-inner="showPasswordConfirmation = !showPasswordConfirmation"
            :error-messages="form.errors.password_confirmation"
            class="mb-6"
            required
          />

          <!-- SUBMIT -->
          <v-btn
            block
            size="large"
            color="primary"
            type="submit"
            :disabled="!canSubmit || form.processing"
            :loading="form.processing"
            class="mb-4"
          >
            Create Account
          </v-btn>

          <!-- FOOTER -->
          <div class="text-center text-body-2">
            <span class="text-grey-darken-1">Already have an account?</span>
            <Link
              :href="route('login')"
              class="ml-1 text-primary font-weight-medium text-decoration-none"
            >
              Sign in
            </Link>
          </div>
        </form>
      </v-card>
    </v-container>
  </v-app>
</template>

<style scoped>
/* BACKGROUND */
.register-bg {
  background: radial-gradient(circle at top, #eef3fb, #f8fbff);
}

/* CARD */
.register-card {
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

/* PASSWORD RULES */
.password-rules {
  list-style: none;
  padding: 0;
  margin: 0;
  font-size: 12px;
  color: #94a3b8;
}
.password-rules li {
  margin-bottom: 4px;
}
.password-rules li.ok {
  color: #16a34a;
  font-weight: 600;
}
</style>
