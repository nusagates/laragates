<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Form from './Form.vue'

defineProps({
  menu: Object
})
</script>

<template>
  <AdminLayout>
    <template #title>Edit Menu</template>
    <Form :menu="menu" />
  </AdminLayout>
</template>
