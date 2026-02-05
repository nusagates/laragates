<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Form from './Form.vue'
</script>

<template>
  <AdminLayout>
    <template #title>Tambah WhatsApp Menu</template>

    <div class="page-header">
      <div>
        <h2 class="page-title">Tambah Menu Baru</h2>
        <p class="page-desc">
          Buat menu interaksi WhatsApp untuk auto-reply,
          input pelanggan, atau handover ke agent.
        </p>
      </div>
    </div>

    <div class="form-wrapper">
      <Form />
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
