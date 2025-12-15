<script setup>
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
  status: {
    type: String,
  },
})

const form = useForm({})

// Resend Email
const submit = () => {
  form.post(route('verification.send'))
}

const verificationLinkSent = computed(() => props.status === 'verification-link-sent')
</script>

<template>
  <Head title="Verify Email" />

  <v-container class="d-flex justify-center align-center" style="min-height:100vh; background:#f5f7fa;">
    <v-card width="420" class="pa-6" elevation="6" style="border-radius:14px;">

      <div class="text-center mb-4">
        <v-icon size="60" color="primary">mdi-email-check-outline</v-icon>
        <h2 class="text-h5 font-weight-medium mt-2 mb-1">Verify Your Email</h2>
        <p class="text-body-2 text-grey">
          Before continuing, please check your inbox and verify your email address.
        </p>
      </div>

      <v-alert
        v-if="verificationLinkSent"
        type="success"
        variant="tonal"
        class="mb-4"
        border="start"
      >
        A new verification link has been sent to your email address.
      </v-alert>

      <form @submit.prevent="submit" class="mt-4">

        <v-btn
          color="primary"
          type="submit"
          :disabled="form.processing"
          :loading="form.processing"
          block
          class="mb-3"
        >
          Resend Verification Email
        </v-btn>

        <div class="text-center mt-2">
          <Link :href="route('logout')" method="post" as="button" class="text-body-2 text-primary text-decoration-underline">
            Log Out
          </Link>
        </div>

      </form>
    </v-card>
  </v-container>
</template>
