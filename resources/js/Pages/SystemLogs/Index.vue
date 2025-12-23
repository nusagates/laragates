<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head } from '@inertiajs/vue3'
</script>

<template>
  <Head title="System Logs" />

  <AdminLayout>
    <template #title>System Logs</template>

    <!-- ===============================
         SYSTEM LOGS PAGE (ISOLATED)
    ================================ -->
    <div class="syslog-page">

      <!-- HEADER -->
      <div class="syslog-header">
        <h3>System Activity Logs</h3>
        <p>Audit trail semua aktivitas sistem</p>
      </div>

      <!-- FILTER -->
      <div class="syslog-filter">
        <v-text-field
          label="User"
          density="compact"
          hide-details
        />

        <v-text-field
          label="Action"
          density="compact"
          hide-details
        />

        <v-btn color="primary">
          FILTER
        </v-btn>

        <v-spacer />

        <v-btn color="success" variant="tonal">
          EXPORT CSV
        </v-btn>
      </div>

      <!-- TABLE -->
      <v-card class="syslog-table">
        <v-table>
          <thead>
            <tr>
              <th>ID</th>
              <th>User</th>
              <th>Action</th>
              <th>Route</th>
              <th>IP</th>
              <th>Time</th>
            </tr>
          </thead>

          <tbody>
            <tr>
              <td colspan="6" class="empty">
                <v-icon size="32">mdi-clipboard-text</v-icon>
                <div>No logs found</div>
              </td>
            </tr>
          </tbody>
        </v-table>
      </v-card>

    </div>
  </AdminLayout>
</template>

<style scoped>
/* =================================================
   SYSTEM LOGS — ISOLATED DARK THEME
   (AMAN, TIDAK SENTUH ADMIN LAYOUT)
================================================= */

.syslog-page {
  color: #e5e7eb;
}

/* HEADER */
.syslog-header {
  background: #0f172a;
  padding: 20px 24px;
  border-radius: 14px;
  margin-bottom: 16px;
}

.syslog-header h3 {
  color: #f8fafc;
  font-weight: 600;
}

.syslog-header p {
  color: #94a3b8;
  margin-top: 4px;
}

/* FILTER */
.syslog-filter {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #020617;
  padding: 16px;
  border-radius: 14px;
  margin-bottom: 16px;
}

/* INPUT FIX (VUETIFY) */
:deep(.v-field),
:deep(.v-input),
:deep(input) {
  color: #e5e7eb !important;
}

:deep(.v-label),
:deep(input::placeholder) {
  color: #94a3b8 !important;
}

/* TABLE */
.syslog-table {
  background: #020617 !important;
  border-radius: 14px;
  overflow: hidden;
}

:deep(.v-table),
:deep(table) {
  background: transparent !important;
}

:deep(thead th) {
  background: #020617 !important;
  color: #94a3b8 !important;
  font-weight: 600;
  border-bottom: 1px solid rgba(255,255,255,.08);
}

:deep(tbody td) {
  color: #e5e7eb !important;
  border-bottom: 1px solid rgba(255,255,255,.06);
}

/* EMPTY STATE */
.empty {
  text-align: center;
  padding: 48px;
  color: #94a3b8;
}

.empty .v-icon {
  display: block;
  margin: 0 auto 6px;
}
</style>
