<script setup>
import { ref, computed } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import axios from 'axios'

/* ======================
   PROPS
====================== */
const props = defineProps({
  agents: Array,
  counts: Object,
  filters: Object,
})

/* ======================
   LOCAL STATE
====================== */
const agents = ref([...props.agents])

/* ======================
   AUTH
====================== */
const page = usePage()
const meRole = computed(() => page.props.auth?.user?.role ?? 'agent')
const myUserId = computed(() => page.props.auth?.user?.id)
const canManageAgents = computed(() =>
  ['superadmin', 'admin'].includes(meRole.value)
)

/* ======================
   STATE
====================== */
const tab = ref('all')
const search = ref(props.filters?.search || '')
const showDeleted = ref(props.filters?.show_deleted || false)
const dialog = ref(false)
const isEdit = ref(false)
const formLoading = ref(false)

const form = ref({
  id: null,
  name: '',
  email: '',
  company_id: null,
})

// Confirmation Dialog State
const confirmDialog = ref(false)
const confirmData = ref({
  title: '',
  message: '',
  action: null,
  color: 'primary',
})

// Notification Snackbar State
const snackbar = ref(false)
const snackbarData = ref({
  message: '',
  color: 'success',
  timeout: 5000,
})

/* ======================
   DIALOG HELPERS
====================== */
function showConfirm(title, message, action, color = 'primary') {
  confirmData.value = { title, message, action, color }
  confirmDialog.value = true
}

function confirmAction() {
  if (confirmData.value.action) {
    confirmData.value.action()
  }
  confirmDialog.value = false
}

function showNotification(message, color = 'success', timeout = 5000) {
  snackbarData.value = { message, color, timeout }
  snackbar.value = true
}

/* ======================
   LOCK HELPERS
====================== */
function isLocked(agent) {
  if (!agent.locked_until) return false
  return new Date(agent.locked_until) > new Date()
}

/* ======================
   FILTER
====================== */
const filteredAgents = computed(() => {
  let data = agents.value || []

  // Filter by deleted status
  if (!showDeleted.value) {
    data = data.filter(a => !a.deleted_at)
  }

  // Filter by status tab
  if (tab.value !== 'all') {
    if (tab.value === 'pending') {
      data = data.filter(a => !a.approved_at && !a.deleted_at)
    } else if (tab.value === 'deleted') {
      data = data.filter(a => a.deleted_at)
    } else {
      data = data.filter(a => a.status === tab.value && !a.deleted_at)
    }
  }

  // Search filter
  if (search.value) {
    const q = search.value.toLowerCase()
    data = data.filter(a =>
      a.name.toLowerCase().includes(q) ||
      a.email.toLowerCase().includes(q)
    )
  }

  return data
})

/* ======================
   ACTIONS
====================== */
function openCreate() {
  isEdit.value = false
  form.value = { id: null, name: '', email: '', company_id: null }
  dialog.value = true
}

function openEdit(agent) {
  isEdit.value = true
  form.value = {
    id: agent.id,
    name: agent.name,
    email: agent.email,
    company_id: agent.company_id,
  }
  dialog.value = true
}

function statusBadgeColor(status) {
  if (status === 'online') return 'green-darken-2'
  if (status === 'offline') return 'grey-darken-1'
  return 'amber-darken-2'
}

async function approveAgent(id) {
  showConfirm(
    'Approve Agent',
    'Are you sure you want to approve this agent?',
    async () => {
      try {
        await axios.post(`/agents/${id}/approve`)
        const agent = agents.value.find(a => a.id === id)
        if (agent) agent.approved_at = new Date()
        showNotification('Agent approved successfully', 'success')
      } catch (e) {
        showNotification(e.response?.data?.message ?? 'Failed to approve agent', 'error')
      }
    },
    'green'
  )
}

async function lockAgent(agent) {
  showConfirm(
    'Lock Account',
    `Lock account for ${agent.name}? They will not be able to login until unlocked.`,
    async () => {
      try {
        await axios.post(`/agents/${agent.id}/lock`)
        agent.locked_until = new Date(Date.now() + 24 * 60 * 60 * 1000) // 24 hours from now
        agent.failed_login_attempts = 6
        showNotification('Account locked successfully', 'success')
      } catch (e) {
        showNotification(e.response?.data?.message ?? 'Failed to lock account', 'error')
      }
    },
    'orange'
  )
}

async function unlockAgent(agent) {
  showConfirm(
    'Unlock Account',
    `Unlock account for ${agent.name}?`,
    async () => {
      try {
        await axios.post(`/agents/${agent.id}/unlock`)
        agent.failed_login_attempts = 0
        agent.locked_until = null
        showNotification('Account unlocked successfully', 'success')
      } catch (e) {
        showNotification(e.response?.data?.message ?? 'Failed to unlock account', 'error')
      }
    },
    'orange'
  )
}

async function submitForm() {
  formLoading.value = true
  try {
    if (isEdit.value) {
      await axios.put(`/agents/${form.value.id}`, form.value)
      const idx = agents.value.findIndex(a => a.id === form.value.id)
      if (idx !== -1) {
        agents.value[idx] = { ...agents.value[idx], ...form.value }
      }
      showNotification('Agent updated successfully', 'success')
    } else {
      const res = await axios.post('/agents', form.value)
      if (res?.data?.agent) {
        agents.value.push(res.data.agent)
      }
      showNotification('Agent created successfully', 'success')
    }
    dialog.value = false
  } catch (e) {
    showNotification(e.response?.data?.message ?? 'Failed to save agent', 'error')
  } finally {
    formLoading.value = false
  }
}

async function deleteAgent(agent) {
  if (agent.id === myUserId.value) {
    showNotification('Cannot delete yourself', 'warning')
    return
  }

  showConfirm(
    'Delete Agent',
    `Are you sure you want to delete agent ${agent.name}? This will soft delete the agent.`,
    async () => {
      try {
        await axios.delete(`/agents/${agent.id}`)
        const agentToDelete = agents.value.find(a => a.id === agent.id)
        if (agentToDelete) {
          agentToDelete.deleted_at = new Date()
        }
        showNotification('Agent deleted successfully', 'success')
      } catch (e) {
        showNotification(e.response?.data?.message ?? 'Failed to delete agent', 'error')
      }
    },
    'red'
  )
}

async function permanentDelete(agent) {
  if (agent.id === myUserId.value) {
    showNotification('Cannot permanently delete yourself', 'warning')
    return
  }

  showConfirm(
    'Permanent Delete',
    `Are you sure you want to PERMANENTLY delete agent ${agent.name}? This action CANNOT be undone and will remove all data associated with this agent.`,
    async () => {
      try {
        await axios.delete(`/agents/${agent.id}/force`)
        agents.value = agents.value.filter(a => a.id !== agent.id)
        showNotification('Agent permanently deleted', 'success')
      } catch (e) {
        showNotification(e.response?.data?.message ?? 'Failed to permanently delete agent', 'error')
      }
    },
    'red'
  )
}

async function restoreAgent(agent) {
  showConfirm(
    'Restore Agent',
    `Are you sure you want to restore agent ${agent.name}?`,
    async () => {
      try {
        await axios.post(`/agents/${agent.id}/restore`)
        const agentToRestore = agents.value.find(a => a.id === agent.id)
        if (agentToRestore) {
          agentToRestore.deleted_at = null
        }
        showNotification('Agent restored successfully', 'success')
      } catch (e) {
        showNotification(e.response?.data?.message ?? 'Failed to restore agent', 'error')
      }
    },
    'green'
  )
}
</script>

<template>
  <Head title="Agents Management" />

  <AdminLayout>
    <template #title>Agents Management</template>

    <div v-if="canManageAgents" class="agents-dark">

      <!-- SUMMARY -->
      <div class="summary-grid mb-6">
        <div class="summary-card">
          <span>Total Agents</span>
          <h2>{{ counts.all }}</h2>
        </div>
        <div class="summary-card online">
          <span>Online</span>
          <h2>{{ counts.online }}</h2>
        </div>
        <div class="summary-card offline">
          <span>Offline</span>
          <h2>{{ counts.offline }}</h2>
        </div>
        <div class="summary-card pending">
          <span>Pending</span>
          <h2>{{ counts.pending }}</h2>
        </div>
        <div class="summary-card locked">
          <span>Locked</span>
          <h2>{{ counts.locked }}</h2>
        </div>
        <div class="summary-card deleted">
          <span>Deleted</span>
          <h2>{{ counts.deleted }}</h2>
        </div>
      </div>

      <!-- MAIN -->
      <v-card class="main-card pa-5">
        <div class="d-flex justify-space-between align-center mb-4">
          <div>
            <h3 class="text-white">Agents Management</h3>
            <p class="text-muted">Manage all agents in the system</p>
          </div>
          <v-btn color="primary" prepend-icon="mdi-account-plus" @click="openCreate">
            Add Agent
          </v-btn>
        </div>

        <!-- FILTER -->
        <div class="mb-4">
          <div class="d-flex justify-space-between mb-3">
            <div class="d-flex gap-2">
              <v-btn size="small" color="primary" :variant="tab==='all'?'flat':'text'" @click="tab='all'">All</v-btn>
              <v-btn size="small" color="primary" :variant="tab==='online'?'flat':'text'" @click="tab='online'">Online</v-btn>
              <v-btn size="small" color="primary" :variant="tab==='offline'?'flat':'text'" @click="tab='offline'">Offline</v-btn>
              <v-btn size="small" color="primary" :variant="tab==='pending'?'flat':'text'" @click="tab='pending'">Pending</v-btn>
              <v-btn size="small" color="red" :variant="tab==='deleted'?'flat':'text'" @click="tab='deleted'">Deleted</v-btn>
            </div>

            <v-text-field
              v-model="search"
              density="compact"
              prepend-inner-icon="mdi-magnify"
              placeholder="Search agent..."
              hide-details
              style="max-width:260px"
            />
          </div>

          <div class="d-flex gap-2 align-center">
            <v-switch
              v-model="showDeleted"
              label="Show Deleted Agents"
              class="text-grey"
              color="red"
              hide-details
              density="compact"
            />
          </div>
        </div>

        <!-- TABLE -->
        <v-table>
          <thead>
            <tr>
              <th>Agent</th>
              <th>Status</th>
              <th class="text-center">Approval</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="agent in filteredAgents" :key="agent.id" :class="{'row-hover': !agent.deleted_at, 'row-deleted': agent.deleted_at}">
              <td>
                <strong>{{ agent.name }}</strong>
                <v-chip v-if="agent.deleted_at" size="x-small" color="red" class="ml-2">DELETED</v-chip>
                <br />
                <small class="text-muted">{{ agent.email }}</small>
              </td>

              <td>
                <v-chip
                  v-if="isLocked(agent)"
                  size="small"
                  class="locked"
                >
                  🔒 Locked
                </v-chip>

                <v-chip
                  v-else
                  size="small"
                  :color="statusBadgeColor(agent.status)"
                >
                  {{ agent.status }}
                </v-chip>
              </td>

              <td class="text-center">
                <v-btn
                  v-if="!agent.approved_at && !agent.deleted_at"
                  size="small"
                  icon="mdi-check"
                  color="green"
                  @click="approveAgent(agent.id)"
                />
                <v-chip v-else-if="agent.approved_at" size="small" color="blue-darken-2" variant="tonal">
                  Approved
                </v-chip>
                <span v-else class="text-muted">-</span>
              </td>

              <td class="text-center">
                <!-- Deleted Agent Actions -->
                <template v-if="agent.deleted_at">
                  <v-btn
                    icon="mdi-restore"
                    size="small"
                    variant="text"
                    color="green"
                    @click="restoreAgent(agent)"
                    title="Restore Agent"
                  />
                  <v-btn
                    icon="mdi-delete-forever"
                    size="small"
                    color="red"
                    variant="text"
                    @click="permanentDelete(agent)"
                    title="Permanently Delete Agent"
                  />
                </template>

                <!-- Active Agent Actions -->
                <template v-else>
                  <v-btn
                    v-if="isLocked(agent)"
                    icon="mdi-lock-open-variant"
                    size="small"
                    class="unlock"
                    variant="text"
                    @click="unlockAgent(agent)"
                    title="Unlock Account"
                  />
                  <v-btn
                    v-else
                    icon="mdi-lock"
                    size="small"
                    variant="text"
                    color="orange"
                    @click="lockAgent(agent)"
                    title="Lock Account"
                  />
                  <v-btn
                    icon="mdi-pencil"
                    size="small"
                    variant="text"
                    @click="openEdit(agent)"
                    title="Edit Agent"
                  />
                  <v-btn
                    v-if="agent.id !== myUserId"
                    icon="mdi-delete"
                    size="small"
                    color="red"
                    variant="text"
                    @click="deleteAgent(agent)"
                    title="Delete Agent"
                  />
                </template>
              </td>
            </tr>
          </tbody>
        </v-table>
      </v-card>
    </div>

    <!-- FORM DIALOG -->
    <v-dialog v-model="dialog" max-width="480" content-class="agents-dialog-dark">
      <v-card class="pa-4">
        <h3 class="mb-4 text-white">{{ isEdit ? 'Edit Agent' : 'Add Agent' }}</h3>
        <v-text-field v-model="form.name" label="Full Name" />
        <v-text-field v-model="form.email" label="Email" />
        <div class="d-flex justify-end mt-4 gap-2">
          <v-btn variant="text" @click="dialog=false">Cancel</v-btn>
          <v-btn color="primary" :loading="formLoading" @click="submitForm">Save</v-btn>
        </div>
      </v-card>
    </v-dialog>

    <!-- CONFIRMATION DIALOG -->
    <v-dialog v-model="confirmDialog" max-width="500">
      <v-card>
        <v-card-title class="text-h5 d-flex align-center">
          <v-icon
            :color="confirmData.color"
            class="mr-2"
            :icon="confirmData.color === 'red' ? 'mdi-alert-circle' : confirmData.color === 'orange' ? 'mdi-information' : 'mdi-help-circle'"
          />
          {{ confirmData.title }}
        </v-card-title>
        <v-card-text class="pt-4">
          {{ confirmData.message }}
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="confirmDialog = false">
            Cancel
          </v-btn>
          <v-btn
            :color="confirmData.color"
            variant="flat"
            @click="confirmAction"
          >
            Confirm
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- SNACKBAR NOTIFICATION -->
    <v-snackbar
      v-model="snackbar"
      :color="snackbarData.color"
      :timeout="snackbarData.timeout"
      location="top right"
      multi-line
    >
      <div class="d-flex align-center">
        <v-icon class="mr-2">
          {{ snackbarData.color === 'success' ? 'mdi-check-circle' :
             snackbarData.color === 'error' ? 'mdi-alert-circle' :
             'mdi-information' }}
        </v-icon>
        {{ snackbarData.message }}
      </div>
      <template #actions>
        <v-btn
          variant="text"
          icon="mdi-close"
          @click="snackbar = false"
        />
      </template>
    </v-snackbar>
  </AdminLayout>
</template>

<style scoped>
.agents-dark { color:#e5e7eb; }
.main-card {
  background: linear-gradient(180deg,#020617,#0f172a);
  border:1px solid rgba(255,255,255,.06);
}
.summary-grid {
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
  gap:16px;
}
.summary-card {
  padding:16px;
  border-radius:14px;
  background:#020617;
  border-left:4px solid #2563eb;
}
.summary-card.online{border-color:#22c55e;}
.summary-card.offline{border-color:#64748b;}
.summary-card.pending{border-color:#f59e0b;}
.summary-card.locked{border-color:#ef4444;}
.summary-card.deleted{border-color:#dc2626;}
.text-muted{color:#94a3b8;}
.row-hover:hover{background:rgba(59,130,246,.08);}
.row-deleted{
  opacity:0.6;
  background:rgba(220,38,38,.05);
}
.locked{
  background:rgba(239,68,68,.15);
  color:#fecaca;
  border:1px solid rgba(239,68,68,.35);
  font-weight:600;
}
.unlock{color:#fb923c;}
:deep(.agents-dialog-dark){
  background:linear-gradient(180deg,#020617,#0f172a);
  color:#e5e7eb;
}

/* Password field styling */
.password-field :deep(.v-field__input) {
  font-family: 'Courier New', monospace;
  font-size: 1.1rem;
  font-weight: bold;
  letter-spacing: 0.1em;
}

/* ===============================
   FORCE DARK TABLE (OVERRIDE PUTIH)
================================ */

/* wrapper tabel */
.agents-dark :deep(.v-table),
.agents-dark :deep(.v-table__wrapper),
.agents-dark :deep(.v-table__wrapper table) {
  background: transparent !important;
}

/* header */
.agents-dark :deep(.v-table thead),
.agents-dark :deep(.v-table thead tr),
.agents-dark :deep(.v-table thead th) {
  background: #020617 !important;
  color: #94a3b8 !important;
  border-bottom: 1px solid rgba(255,255,255,0.08);
}

/* body rows */
.agents-dark :deep(.v-table tbody),
.agents-dark :deep(.v-table tbody tr) {
  background: #020617 !important;
  color: #e5e7eb !important;
}

/* body cells */
.agents-dark :deep(.v-table tbody td) {
  background: transparent !important;
  color: #e5e7eb !important;
  border-bottom: 1px solid rgba(255,255,255,0.06);
}

/* hover */
.agents-dark :deep(.v-table tbody tr:hover) {
  background: rgba(59,130,246,0.08) !important;
}

/* approval badge (Approved) */
.agents-dark :deep(.v-chip) {
  background-color: rgba(59,130,246,0.15) !important;
  color: #93c5fd !important;
}

/* offline badge */
.agents-dark :deep(.v-chip.bg-grey-lighten-3),
.agents-dark :deep(.v-chip.bg-grey) {
  background-color: rgba(148,163,184,0.15) !important;
  color: #cbd5f5 !important;
}

/* row selected / active */
.agents-dark tr {
  transition: background 0.2s ease;
}

</style>
