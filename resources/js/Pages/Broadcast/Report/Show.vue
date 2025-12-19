<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import axios from 'axios'

const props = usePage().props.value
const campaign = ref(props.campaign)
const targets = ref(props.targets)
const filters = ref(props.filters || { q: '', status: '' })

function applyFilter() {
  router.get(
    route('broadcast.report.show', campaign.value.id),
    { q: filters.value.q, status: filters.value.status },
    { preserveState: true, preserveScroll: true }
  )
}

async function retryTarget(targetId) {
  try {
    await axios.post(`/broadcast/targets/${targetId}/retry`)
    router.reload()
  } catch (e) {
    alert('Retry failed')
  }
}
</script>

<template>
  <Head :title="`Campaign - ${campaign.name}`" />

  <AdminLayout>
    <template #title>Campaign Detail</template>

    <div class="report-dark">

      <!-- SUMMARY -->
      <div class="header-card mb-6">
        <div>
          <h3>{{ campaign.name }}</h3>
          <p>
            Template: {{ campaign.template?.name || '-' }} •
            Status: <strong>{{ campaign.status }}</strong>
          </p>
        </div>

        <div class="stats">
          <div>
            <span>Sent</span>
            <strong class="success">{{ campaign.sent_count }}</strong>
          </div>
          <div>
            <span>Failed</span>
            <strong class="danger">{{ campaign.failed_count }}</strong>
          </div>
        </div>
      </div>

      <!-- TARGETS -->
      <v-card class="card pa-4">
        <div class="targets-header">
          <div>
            <h4>Targets</h4>
            <p class="muted">Per-target delivery log</p>
          </div>

          <div class="filters">
            <v-text-field
              v-model="filters.q"
              placeholder="Search phone / name / error"
              density="compact"
              hide-details
              @keyup.enter="applyFilter"
            />

            <v-select
              v-model="filters.status"
              :items="['pending','sent','failed']"
              placeholder="Status"
              clearable
              density="compact"
              hide-details
            />

            <v-btn color="primary" @click="applyFilter">
              Filter
            </v-btn>
          </div>
        </div>

        <v-table>
          <thead>
            <tr>
              <th>#</th>
              <th>Phone</th>
              <th>Name</th>
              <th>Status</th>
              <th>Error</th>
              <th>Sent At</th>
              <th>Attempts</th>
              <th class="text-right">Action</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="t in targets.data"
              :key="t.id"
              class="row-hover"
            >
              <td>{{ t.id }}</td>
              <td>{{ t.phone }}</td>
              <td>{{ t.name || '-' }}</td>

              <td>
                <v-chip
                  size="small"
                  :color="
                    t.status === 'sent'
                      ? 'green-darken-2'
                      : t.status === 'failed'
                        ? 'red-darken-2'
                        : 'grey-darken-1'
                  "
                >
                  {{ t.status }}
                </v-chip>
              </td>

              <td class="error-cell">
                {{ t.error_message || '-' }}
              </td>

              <td>
                {{ t.sent_at ? new Date(t.sent_at).toLocaleString() : '-' }}
              </td>

              <td>{{ t.attempts ?? 0 }}</td>

              <td class="text-right">
                <v-btn
                  size="small"
                  variant="tonal"
                  color="orange"
                  @click="retryTarget(t.id)"
                  :disabled="t.status === 'sent'"
                >
                  Retry
                </v-btn>
              </td>
            </tr>

            <tr v-if="targets.data.length === 0">
              <td colspan="8" class="text-center muted pa-6">
                No targets found.
              </td>
            </tr>
          </tbody>
        </v-table>

        <div class="d-flex justify-end mt-4">
          <v-pagination
            v-model="targets.current_page"
            :length="targets.last_page"
            @update:modelValue="p =>
              router.get(
                route('broadcast.report.show', campaign.id),
                { page: p },
                { preserveState:true, preserveScroll:true }
              )
            "
          />
        </div>
      </v-card>

    </div>
  </AdminLayout>
</template>

<style scoped>
/* =========================================================
   BROADCAST REPORT DETAIL – DARK WABA
========================================================= */

:global(:root) {
  --bg-main: #020617;
  --bg-soft: #0f172a;
  --border-soft: rgba(255,255,255,.06);

  --text-main: #e5e7eb;
  --text-muted: #94a3b8;

  --blue-soft: rgba(59,130,246,.12);
  --success: #22c55e;
  --danger: #f87171;
}

/* WRAPPER */
.report-dark {
  color: var(--text-main);
}

/* HEADER SUMMARY */
.header-card {
  background: linear-gradient(180deg, var(--bg-main), var(--bg-soft));
  padding: 20px;
  border-radius: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-card p {
  color: var(--text-muted);
}

/* STATS */
.stats {
  display: flex;
  gap: 20px;
}
.stats span {
  display: block;
  font-size: 12px;
  color: var(--text-muted);
}
.stats strong {
  font-size: 20px;
}
.success { color: var(--success); }
.danger  { color: var(--danger); }

/* CARD */
.card {
  background: linear-gradient(180deg, var(--bg-main), var(--bg-soft));
  border: 1px solid var(--border-soft);
  border-radius: 16px;
}

/* HEADER */
.targets-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 14px;
}

.muted {
  color: var(--text-muted);
}

/* FILTERS */
.filters {
  display: flex;
  gap: 10px;
}

/* TABLE DARK FORCE */
:deep(.v-table),
:deep(.v-table__wrapper),
:deep(table) {
  background: transparent !important;
}

:deep(thead th) {
  background: #020617 !important;
  color: var(--text-muted) !important;
  border-bottom: 1px solid var(--border-soft);
}

:deep(tbody td) {
  background: transparent !important;
  color: var(--text-main) !important;
  border-bottom: 1px solid var(--border-soft);
}

.row-hover:hover {
  background: var(--blue-soft);
}

/* ERROR CELL */
.error-cell {
  max-width: 320px;
  white-space: pre-wrap;
  font-size: 13px;
  color: #fca5a5;
}

/* INPUT FIX */
:deep(.v-field__input input),
:deep(.v-field__input textarea),
:deep(.v-select__selection-text) {
  color: var(--text-main) !important;
}

:deep(.v-label) {
  color: var(--text-muted) !important;
}
</style>
