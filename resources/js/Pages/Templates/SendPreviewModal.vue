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
  <v-dialog v-model="props.modelValue" max-width="500px">
    <v-card class="pa-4">

      <h3 class="text-h6 font-weight-bold mb-3">
        Send Preview  
      </h3>

      <div class="text-subtitle-2 mb-1">
        Template: <strong>{{ props.template?.name }}</strong>
      </div>

      <!-- PHONE -->
      <v-text-field
        v-model="form.phone"
        label="Phone (e.g. 628123...)"
        density="compact"
        class="mt-3"
        :error-messages="fieldError('phone')"
      />

      <!-- LANGUAGE -->
      <v-select
        v-model="form.language"
        :items="['id', 'en']"
        label="Language"
        density="compact"
        class="mt-3"
      />

      <!-- COMPONENTS -->
      <v-textarea
        v-model="form.components"
        label="Optional components JSON"
        placeholder='[{"type":"body","parameters":[{"type":"text","text":"Hello"}]}]'
        auto-grow
        class="mt-3"
      />

      <!-- BUTTONS -->
      <div class="d-flex justify-end mt-5" style="gap: 12px;">
        <v-btn variant="text" @click="closeModal">Cancel</v-btn>

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
