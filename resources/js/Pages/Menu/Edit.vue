<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Form from './Form.vue'

defineProps({
  menu: Object
})
</script>

<template>
  <AdminLayout>
    <template #title>Edit WhatsApp Menu</template>

    <div class="page-header">
      <div>
        <h2 class="page-title">Edit Menu</h2>
        <p class="page-desc">
          Perbarui konfigurasi menu WhatsApp
          yang digunakan untuk auto-reply atau routing pelanggan.
        </p>
      </div>
    </div>

    <div class="form-wrapper">
      <Form :menu="menu" />
    </div>
  </AdminLayout>
</template>

<style scoped>
.page-header {
  margin-bottom: 28px;
}

.page-title {
  font-size: 22px;
  font-weight: 700;
  color: #0f172a;
}

.page-desc {
  font-size: 14px;
  color: #64748b;
  margin-top: 6px;
  max-width: 640px;
}

.form-wrapper {
  max-width: 960px;
}
</style>
