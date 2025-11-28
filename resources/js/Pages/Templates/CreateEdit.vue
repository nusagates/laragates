<script setup>
import { ref } from 'vue'

const props = defineProps({
  mode: { type: String, default: 'create' }, // create | edit
  form: { type: Object, required: true },
  loading: { type: Boolean, default: false }
})

const emit = defineEmits(['save', 'cancel'])
</script>

<template>
  <div>
    <h3 class="text-h6 mb-4 font-weight-bold">
      {{ mode === 'create' ? 'Add New Template' : 'Edit Template' }}
    </h3>

    <v-text-field
      v-model="form.name"
      label="Template Name"
      class="mb-3"
    />

    <v-select
      v-model="form.category"
      :items="['Utility','Marketing','Authentication']"
      label="Category"
      class="mb-3"
    />

    <v-select
      v-model="form.language"
      :items="['id','en']"
      label="Language"
      class="mb-3"
    />

    <v-textarea
      v-model="form.header"
      label="Header (Optional)"
      rows="2"
      class="mb-3"
    />

    <v-textarea
      v-model="form.body"
      label="Body Message"
      rows="4"
      class="mb-3"
      variant="outlined"
    />

    <v-textarea
      v-model="form.footer"
      label="Footer (Optional)"
      rows="2"
      class="mb-3"
    />

    <div class="d-flex justify-end gap-2">
      <v-btn variant="text" @click="$emit('cancel')">Cancel</v-btn>
      <v-btn color="primary" :loading="loading" @click="$emit('save')">
        {{ mode === 'create' ? 'Save' : 'Update' }}
      </v-btn>
    </div>
  </div>
</template>

<style scoped>
</style>
