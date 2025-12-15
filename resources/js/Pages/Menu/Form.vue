<script setup>
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
  menu: Object
})

const form = useForm({
  key: props.menu?.key ?? '',
  title: props.menu?.title ?? '',
  action_type: props.menu?.action_type ?? 'auto_reply',
  reply_text: props.menu?.reply_text ?? '',
  order: props.menu?.order ?? 0,
})

const submit = () => {
  if (props.menu) {
    form.put(`/menu/${props.menu.id}`)
  } else {
    form.post('/menu')
  }
}
</script>

<template>
  <v-form @submit.prevent="submit" class="mt-4">
    <v-text-field label="Key" v-model="form.key" />
    <v-text-field label="Judul" v-model="form.title" />

    <v-select
      label="Action Type"
      :items="['auto_reply','ask_input','handover']"
      v-model="form.action_type"
    />

    <v-textarea
      label="Reply Text"
      v-model="form.reply_text"
      rows="4"
    />

    <v-btn type="submit" color="primary" class="mt-3">
      Simpan
    </v-btn>
  </v-form>
</template>
