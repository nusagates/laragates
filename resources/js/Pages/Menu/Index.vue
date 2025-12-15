<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { router } from '@inertiajs/vue3'

defineProps({
  menus: Array
})
</script>

<template>
  <AdminLayout>
    <template #title>WhatsApp Menu</template>

    <v-btn color="primary" class="mb-4" @click="router.visit('/menu/create')">
      Tambah Menu
    </v-btn>

    <v-table>
      <thead>
        <tr>
          <th>Key</th>
          <th>Title</th>
          <th>Action</th>
          <th>Aksi</th>
        </tr>
      </thead>

      <tbody>
        <tr v-for="menu in menus" :key="menu.id">
          <td>{{ menu.key }}</td>
          <td>{{ menu.title }}</td>
          <td>{{ menu.action_type }}</td>
          <td>
            <v-btn size="small" @click="router.visit(`/menu/${menu.id}/edit`)">
              Edit
            </v-btn>
            <v-btn
              size="small"
              color="error"
              @click="router.delete(`/menu/${menu.id}`)"
            >
              Hapus
            </v-btn>
          </td>
        </tr>
      </tbody>
    </v-table>
  </AdminLayout>
</template>
