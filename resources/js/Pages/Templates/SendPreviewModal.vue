<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
  modelValue: Boolean,
  template: Object
})

const emit = defineEmits(['update:modelValue'])

const form = ref({
  phone: '',
  language: 'id',
  components: []
})

const loading = ref(false)
const errors = ref({})

function closeModal() {
  emit('update:modelValue', false)
}

function fieldError(name) {
  return errors.value[name]?.[0] || ''
}

/* -------------------------------------------------------
   🔥 SEND PREVIEW (DUMMY API – SELALU SUKSES)
-------------------------------------------------------- */
async function sendPreview() {
  loading.value = true
  errors.value = {}

  try {
    const res = await axios.post(
      route('templates.send-preview', props.template.id),
      {
        phone: form.value.phone,
        language: form.value.language,
        components: form.value.components
      }
    )

    alert("Preview sent (SIMULATION OK):\n" + res.data.message)
    closeModal()

  } catch (err) {
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors
    } else {
      alert("Send failed: " + (err.response?.data?.message || err.message))
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <!-- ======================
       DARK DIALOG
  ======================= -->
  <v-dialog
    v-model="props.modelValue"
    max-width="500"
    theme="dark"
  >
    <v-card class="pa-5 dark-dialog-card">

      <h3 class="text-h6 mb-2">
        Send Preview
      </h3>

      <p class="text-muted mb-3">
        Template:
        <strong>{{ props.template?.name }}</strong>
      </p>

      <!-- PHONE -->
      <v-text-field
        v-model="form.phone"
        label="Phone (e.g. 628123...)"
        density="compact"
        class="mb-3"
        :error-messages="fieldError('phone')"
      />

      <!-- LANGUAGE -->
      <v-select
        v-model="form.language"
        :items="['id', 'en']"
        label="Language"
        density="compact"
        class="mb-3"
      />

      <!-- COMPONENTS -->
      <v-textarea
        v-model="form.components"
        label="Optional components JSON"
        placeholder='[{"type":"body","parameters":[{"type":"text","text":"Hello"}]}]'
        auto-grow
        class="mb-2"
      />

      <!-- ACTION -->
      <div class="d-flex justify-end gap-3 mt-5">
        <v-btn variant="text" @click="closeModal">
          Cancel
        </v-btn>

        <v-btn
          color="primary"
          :loading="loading"
          @click="sendPreview"
        >
          Send
        </v-btn>
      </div>

    </v-card>
  </v-dialog>
</template>

<style scoped>
/* ===============================
   DARK DIALOG STYLE
================================ */
.dark-dialog-card {
  background: linear-gradient(180deg, #020617, #0f172a);
  border: 1px solid rgba(255,255,255,.08);
  border-radius: 16px;
  color: #e5e7eb;
}

.text-muted {
  color: #94a3b8;
}
</style>
