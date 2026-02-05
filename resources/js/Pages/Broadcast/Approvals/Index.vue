<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { usePage, router } from '@inertiajs/vue3'
import { ref, reactive } from 'vue'
import axios from 'axios'

const props = usePage().props.value

const approvals = ref(props.approvals || { data: [], last_page: 1 })
const page = ref(props.approvals?.current_page || 1)

const filters = reactive({
  action: props.filters?.action || null
})

const actions = [
  { title: 'Requested', value: 'requested' },
  { title: 'Approved', value: 'approved' },
  { title: 'Rejected', value: 'rejected' }
]

const showSnapshot = ref(false)
const snapshotText = ref('')

function applyFilter() {
  router.get(
    route('broadcast.approvals.index'),
    { action: filters.action },
    { preserveState: true, preserveScroll: true }
  )
}

function gotoPage(p) {
  router.get(
    route('broadcast.approvals.index'),
    { page: p, action: filters.action },
    { preserveState: true, preserveScroll: true }
  )
}

function viewSnapshot(a) {
  snapshotText.value = JSON.stringify(a.snapshot || a.campaign, null, 2)
  showSnapshot.value = true
}

async function approve(a) {
  if (!confirm(`Approve campaign "${a.campaign.name}" ?`)) return
  try {
    await axios.post(route('broadcast.approvals.approve', { approval: a.id }))
    gotoPage(page.value)
  } catch {
    alert('Error approving')
  }
}

async function reject(a) {
  const reason = prompt('Reason for rejection:')
  if (reason === null) return
  try {
    await axios.post(
      route('broadcast.approvals.reject', { approval: a.id }),
      { note: reason }
    )
    gotoPage(page.value)
  } catch {
    alert('Error rejecting')
  }
}
</script>

<template>
  <AdminLayout>
    <template #title>Broadcast Approvals</template>

    <div class="approvals-dark">

      <!-- HEADER -->
      <div class="header-card mb-6">
        <div>
          <h3>Broadcast Approval Requests</h3>
          <p>Pending approvals for broadcast campaigns</p>
        </div>

        <v-select
          v-model="filters.action"
          :items="actions"
          label="Filter Status"
          clearable
          density="compact"
          style="width:200px"
          @update:modelValue="applyFilter"
        />
      </div>

      <!-- TABLE -->
      <v-card class="card pa-0">
        <v-table>
          <thead>
            <tr>
              <th>Campaign</th>
              <th>Requested By</th>
              <th>Notes</th>
              <th>Snapshot</th>
              <th>Requested At</th>
              <th class="text-center">Action</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="a in approvals.data"
              :key="a.id"
              class="row-hover"
            >
              <td>
                <strong>{{ a.campaign.name }}</strong>
                <div class="text-caption muted">
                  Template: {{ a.campaign.template?.name || '-' }}
                </div>
              </td>

              <td>{{ a.requester?.name || '—' }}</td>
              <td>{{ a.request_notes || '-' }}</td>

              <td>
                <v-btn
                  size="small"
                  variant="text"
                  class="link-btn"
                  @click="viewSnapshot(a)"
                >
                  View
                </v-btn>
              </td>

              <td>{{ new Date(a.created_at).toLocaleString() }}</td>

              <td class="text-center">
                <div class="action-group">
                  <v-btn
                    size="small"
                    class="approve-btn"
                    @click="approve(a)"
                  >
                    Approve
                  </v-btn>

                  <v-btn
                    size="small"
                    class="reject-btn"
                    @click="reject(a)"
                  >
                    Reject
                  </v-btn>
                </div>
              </td>
            </tr>

            <tr v-if="approvals.data.length === 0">
              <td colspan="6" class="text-center muted pa-6">
                No approval requests.
              </td>
            </tr>
          </tbody>
        </v-table>
      </v-card>

      <!-- PAGINATION -->
      <div class="d-flex justify-end mt-4">
        <v-pagination
          v-model="page"
          :length="approvals.last_page"
          @update:modelValue="gotoPage"
        />
      </div>

      <!-- SNAPSHOT DIALOG -->
      <v-dialog v-model="showSnapshot" max-width="820">
        <v-card class="card pa-4">
          <h3 class="mb-3">Snapshot</h3>

          <pre class="snapshot">
{{ snapshotText }}
          </pre>

          <div class="d-flex justify-end mt-4">
            <v-btn variant="text" @click="showSnapshot=false">
              Close
            </v-btn>
          </div>
        </v-card>
      </v-dialog>

    </div>
  </AdminLayout>
</template>

<style scoped>
/* =========================================================
   BROADCAST APPROVALS – DARK WABA
========================================================= */

:root {
  --bg-main: #020617;
  --bg-soft: #0f172a;
  --border-soft: rgba(255,255,255,.06);

  --text-main: #e5e7eb;
  --text-muted: #94a3b8;

  --blue-soft: rgba(59,130,246,.12);
  --green-soft: rgba(34,197,94,.18);
  --red-soft: rgba(248,113,113,.18);
}

/* WRAPPER */
.approvals-dark {
  color: var(--text-main);
}

/* HEADER */
.header-card {
  background: linear-gradient(180deg,var(--bg-main),var(--bg-soft));
  padding: 20px;
  border-radius: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-card p {
  color: var(--text-muted);
}

/* CARD */
.card {
  background: linear-gradient(180deg,var(--bg-main),var(--bg-soft));
  border: 1px solid var(--border-soft);
  border-radius: 16px;
}

/* TABLE FORCE DARK */
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

/* MUTED */
.muted {
  color: var(--text-muted);
}

/* ACTION BUTTONS */
.action-group {
  display: inline-flex;
  gap: 8px;
}

.approve-btn {
  background: var(--green-soft);
  color: #86efac;
}

.reject-btn {
  background: var(--red-soft);
  color: #fca5a5;
}

.link-btn {
  color: #93c5fd;
}

/* SNAPSHOT */
.snapshot {
  background: rgba(255,255,255,.04);
  border: 1px solid var(--border-soft);
  border-radius: 12px;
  padding: 14px;
  font-size: 13px;
  white-space: pre-wrap;
  color: var(--text-main);
}

/* INPUT VISIBILITY FIX */
:deep(.v-field__input input),
:deep(.v-select__selection-text),
:deep(.v-label) {
  color: var(--text-main) !important;
}
</style>
