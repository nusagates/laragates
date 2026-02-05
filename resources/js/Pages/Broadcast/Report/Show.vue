<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import axios from 'axios'
import { useToast } from 'vue-toastification'

const toast = useToast()
const page = usePage()
const campaign = computed(() => page.props.campaign)
const targets = computed(() => page.props.targets)
const filters = ref(page.props.filters || { q: '', status: '' })
const retryingTargets = ref(new Set())

const successRate = computed(() => {
  const total = campaign.value.total_targets || 0
  const sent = campaign.value.sent_count || 0
  if (total === 0) return 0
  return Math.round((sent / total) * 100)
})

function applyFilter() {
  router.get(
    route('broadcast.report.show', campaign.value.id),
    { q: filters.value.q, status: filters.value.status },
    { preserveState: true, preserveScroll: true }
  )
}

async function retryTarget(targetId) {
  if (retryingTargets.value.has(targetId)) return

  try {
    retryingTargets.value.add(targetId)
    await axios.post(`/broadcast/targets/${targetId}/retry`)
    toast.success('Target queued for retry')
    router.reload({ only: ['targets'] })
  } catch (e) {
    const msg = e.response?.data?.message || 'Failed to retry target'
    toast.error(msg)
  } finally {
    retryingTargets.value.delete(targetId)
  }
}

function goBack() {
  router.visit(route('broadcast.reports'))
}
</script>

<template>
  <Head :title="`Campaign - ${campaign.name}`" />

  <AdminLayout>
    <template #title>
      <div class="d-flex align-center" style="gap: 12px">
        <v-btn
          icon="mdi-arrow-left"
          variant="text"
          @click="goBack"
        />
        <span>Campaign Detail</span>
      </div>
    </template>

    <div class="report-dark">

      <!-- SUMMARY -->
      <div class="header-card mb-6">
        <div class="summary-left">
          <h3>{{ campaign.name }}</h3>
          <p class="template-info">
            <v-icon size="small">mdi-message-text</v-icon>
            {{ campaign.template?.name || '-' }}
          </p>
          <v-chip
            size="small"
            :color="campaign.status === 'completed' ? 'green' : campaign.status === 'failed' ? 'red' : 'blue'"
            class="mt-2"
          >
            {{ campaign.status }}
          </v-chip>
        </div>

        <div class="stats">
          <div class="stat-box">
            <span>Total Targets</span>
            <strong>{{ campaign.total_targets || 0 }}</strong>
          </div>
          <div class="stat-box">
            <span>Sent</span>
            <strong class="success">{{ campaign.sent_count || 0 }}</strong>
          </div>
          <div class="stat-box">
            <span>Failed</span>
            <strong class="danger">{{ campaign.failed_count || 0 }}</strong>
          </div>
          <div class="stat-box">
            <span>Success Rate</span>
            <strong :class="successRate >= 80 ? 'success' : successRate >= 50 ? 'warning' : 'danger'">
              {{ successRate }}%
            </strong>
          </div>
        </div>
      </div>

      <!-- TARGETS -->
      <v-card class="card pa-4">
        <div class="targets-header">
          <div>
            <h4>Delivery Targets</h4>
            <p class="muted">{{ targets.total || 0 }} total records</p>
          </div>

          <div class="filters">
            <v-text-field
              v-model="filters.q"
              placeholder="Search phone / name / error"
              prepend-inner-icon="mdi-magnify"
              density="compact"
              hide-details
              class="filter-input"
              @keyup.enter="applyFilter"
            />

            <v-select
              v-model="filters.status"
              :items="[
                { title: 'Pending', value: 'pending' },
                { title: 'Sent', value: 'sent' },
                { title: 'Failed', value: 'failed' }
              ]"
              placeholder="All Status"
              clearable
              density="compact"
              hide-details
              class="filter-select"
              @update:modelValue="applyFilter"
            />

            <v-btn
              color="primary"
              prepend-icon="mdi-filter"
              @click="applyFilter"
            >
              Apply
            </v-btn>

            <v-btn
              v-if="filters.q || filters.status"
              variant="text"
              @click="filters = { q: '', status: '' }; applyFilter()"
            >
              Clear
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
                  prepend-icon="mdi-refresh"
                  @click="retryTarget(t.id)"
                  :disabled="t.status === 'sent' || retryingTargets.has(t.id)"
                  :loading="retryingTargets.has(t.id)"
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

        <div class="pagination-wrapper">
          <div class="pagination-info">
            Showing {{ targets.from || 0 }} to {{ targets.to || 0 }} of {{ targets.total || 0 }}
          </div>
          <v-pagination
            v-model="targets.current_page"
            :length="targets.last_page"
            :total-visible="7"
            @update:modelValue="p =>
              router.get(
                route('broadcast.report.show', campaign.id),
                { page: p, ...filters },
                { preserveState:true, preserveScroll:false }
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
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
  padding: 24px;
  border-radius: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border: 1px solid rgba(59, 130, 246, 0.1);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
}

.summary-left h3 {
  font-size: 24px;
  font-weight: 600;
  margin-bottom: 8px;
}

.template-info {
  color: var(--text-muted);
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 4px;
}

/* STATS */
.stats {
  display: flex;
  gap: 24px;
}
.stat-box {
  text-align: center;
  padding: 12px 16px;
  background: rgba(255, 255, 255, 0.03);
  border-radius: 12px;
  min-width: 100px;
  border: 1px solid var(--border-soft);
}
.stat-box span {
  display: block;
  font-size: 11px;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 4px;
}
.stat-box strong {
  font-size: 24px;
  display: block;
}
.success { color: var(--success); }
.danger { color: var(--danger); }
.warning { color: #facc15; }

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
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 1px solid var(--border-soft);
}

.targets-header h4 {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 4px;
}

.muted {
  color: var(--text-muted);
  font-size: 13px;
}

/* FILTERS */
.filters {
  display: flex;
  gap: 10px;
  align-items: center;
}

.filter-input {
  min-width: 250px;
}

.filter-select {
  min-width: 150px;
}

/* PAGINATION */
.pagination-wrapper {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 24px;
  padding-top: 16px;
  border-top: 1px solid var(--border-soft);
}

.pagination-info {
  color: var(--text-muted);
  font-size: 13px;
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
