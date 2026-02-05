<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { router } from '@inertiajs/vue3'

defineProps({
  menus: Array
})

const goCreate = () => {
  router.visit('/menu/create')
}

const goEdit = (id) => {
  router.visit(`/menu/${id}/edit`)
}

const remove = (id) => {
  if (confirm('Yakin ingin menghapus menu ini?')) {
    router.delete(`/menu/${id}`)
  }
}

const actionColor = (type) => {
  switch (type) {
    case 'auto_reply': return 'blue'
    case 'ask_input': return 'purple'
    case 'handover': return 'orange'
    default: return 'grey'
  }
}
</script>

<template>
  <AdminLayout>
    <template #title>WhatsApp Menu</template>

    <!-- ===== HEADER ===== -->
    <div class="menu-header">
      <div>
        <h2 class="menu-title">WhatsApp Menu</h2>
        <p class="menu-subtitle">
          Kelola menu interaksi WhatsApp untuk auto-reply,
          input pelanggan, dan handover ke agent.
        </p>
      </div>

      <v-btn
        color="primary"
        size="large"
        prepend-icon="mdi-plus"
        @click="goCreate"
      >
        Tambah Menu
      </v-btn>
    </div>

    <!-- ===== TABLE CARD ===== -->
    <div class="menu-card">
      <v-table class="menu-table">
        <thead>
          <tr>
            <th width="80">Key</th>
            <th>Judul Menu</th>
            <th width="160">Action</th>
            <th width="180" class="text-right">Aksi</th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="menu in menus"
            :key="menu.id"
          >
            <td class="text-mono">
              {{ menu.key }}
            </td>

            <td>
              <div class="menu-title-cell">
                {{ menu.title }}
              </div>
            </td>

            <td>
              <v-chip
                size="small"
                :color="actionColor(menu.action_type)"
                variant="tonal"
              >
                {{ menu.action_type }}
              </v-chip>
            </td>

            <td class="text-right">
              <v-btn
                size="small"
                variant="outlined"
                class="mr-2"
                @click="goEdit(menu.id)"
              >
                Edit
              </v-btn>

              <v-btn
                size="small"
                color="error"
                variant="outlined"
                @click="remove(menu.id)"
              >
                Hapus
              </v-btn>
            </td>
          </tr>

          <tr v-if="!menus.length">
            <td colspan="4" class="empty-state">
              Belum ada menu WhatsApp.
            </td>
          </tr>
        </tbody>
      </v-table>
    </div>
  </AdminLayout>
</template>

<style scoped>
/* ===== HEADER ===== */
.menu-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 28px;
}

.menu-title {
  font-size: 22px;
  font-weight: 700;
  color: #e5e7eb;
}

.menu-subtitle {
  font-size: 14px;
  color: #94a3b8;
  margin-top: 6px;
  max-width: 560px;
}

/* ===== CARD (GLASS DARK) ===== */
.menu-card {
  background: rgba(255, 255, 255, 0.06);
  backdrop-filter: blur(12px);
  border-radius: 18px;
  padding: 12px;
  border: 1px solid rgba(255,255,255,0.08);
}

/* ===== TABLE ===== */
.menu-table {
  background: transparent;
  color: #e5e7eb;
}

.menu-table thead th {
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: .08em;
  color: #94a3b8;
  background: transparent;
  border-bottom: 1px solid rgba(255,255,255,0.1);
  padding: 14px 12px;
}

.menu-table tbody td {
  border-bottom: 1px solid rgba(255,255,255,0.06);
  padding: 14px 12px;
}

.menu-table tbody tr {
  transition: background .2s ease;
}

.menu-table tbody tr:hover {
  background: rgba(255,255,255,0.04);
}

/* ===== CELL ===== */
.menu-title-cell {
  font-weight: 600;
  color: #f8fafc;
}

.text-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
  color: #cbd5f5;
  font-size: 13px;
}

.empty-state {
  text-align: center;
  padding: 40px;
  color: #94a3b8;
}

/* ===== BUTTONS ===== */
.v-btn {
  text-transform: none;
}
</style>
