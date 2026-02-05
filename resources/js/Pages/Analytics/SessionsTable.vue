<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";

/* LOGIC ASLI — TIDAK DIUBAH */
const sessions = ref([]);

onMounted(async () => {
  try {
    const res = await axios.get("/analytics/sessions");
    sessions.value = res.data;
  } catch (e) {
    console.error("Sessions Error:", e);
  }
});
</script>

<template>
  <v-card class="analytics-card sessions-card">

    <!-- HEADER -->
    <div class="card-header">
      <div class="card-title">Active Sessions</div>
      <div class="card-subtitle">Currently open chat sessions</div>
    </div>

    <!-- SCROLL AREA -->
    <div class="table-scroll">
      <v-table density="compact" class="dark-table">
        <thead>
          <tr>
            <th>Session</th>
            <th>Customer</th>
            <th>Last Updated</th>
          </tr>
        </thead>

        <tbody>
          <tr v-if="sessions.length === 0">
            <td colspan="3" class="empty">
              No active sessions
            </td>
          </tr>

          <tr v-for="s in sessions" :key="s.id">
            <td class="mono">#{{ s.id }}</td>
            <td>{{ s.customer_id }}</td>
            <td class="muted">{{ s.updated_at }}</td>
          </tr>
        </tbody>
      </v-table>
    </div>

  </v-card>
</template>

<style scoped>
/* =========================================================
   CARD
========================================================= */
.analytics-card {
  background: linear-gradient(180deg, #020617, #0f172a);
  border: 1px solid rgba(255,255,255,.06);
  border-radius: 16px;
  padding: 16px;
  color: #e5e7eb;
  height: 100%;
}

/* header */
.card-header {
  margin-bottom: 10px;
}
.card-title {
  font-size: 16px;
  font-weight: 600;
}
.card-subtitle {
  font-size: 12px;
  color: #94a3b8;
}

/* =========================================================
   SCROLL FIX
========================================================= */
.sessions-card {
  height: 380px; /* FIXED HEIGHT */
  display: flex;
  flex-direction: column;
}

.table-scroll {
  overflow-y: auto;
  flex: 1;
  margin-top: 6px;
}

/* table */
.dark-table {
  background: transparent !important;
}

:deep(thead th) {
  color: #94a3b8 !important;
  font-size: 12px;
  border-bottom: 1px solid rgba(255,255,255,.08);
}

:deep(tbody td) {
  color: #e5e7eb !important;
  font-size: 13px;
  border-bottom: 1px solid rgba(255,255,255,.05);
}

/* hover */
:deep(tbody tr:hover) {
  background: rgba(59,130,246,.08) !important;
}

/* misc */
.empty {
  text-align: center;
  color: #94a3b8;
  padding: 20px 0;
}
.muted {
  color: #94a3b8;
}
.mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

/* no shadow for pdf */
:deep(.v-card) {
  box-shadow: none !important;
}
</style>
