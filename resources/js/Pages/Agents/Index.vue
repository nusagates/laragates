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
})

/* ======================
   LOCAL STATE (KEY FIX)
====================== */
const agents = ref([...props.agents])

/* ======================
   AUTH
====================== */
const page = usePage()
const meRole = computed(() => page.props.auth?.user?.role ?? 'agent')
const canManageAgents = computed(() =>
  ['superadmin', 'admin'].includes(meRole.value)
)

/* ======================
   STATE
====================== */
const tab = ref('all')
const search = ref('')
const dialog = ref(false)
const isEdit = ref(false)
const formLoading = ref(false)

const form = ref({
  id: null,
  name: '',
  email: '',
  role: 'Agent',
})

/* ======================
   FILTER (PAKAI STATE LOKAL)
====================== */
const filteredAgents = computed(() => {
  let data = agents.value || []

  if (tab.value !== 'all') {
    data = data.filter(a => a.status === tab.value)
  }

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
  form.value = { id: null, name: '', email: '', role: 'Agent' }
  dialog.value = true
}

function openEdit(agent) {
  isEdit.value = true
  form.value = {
    id: agent.id,
    name: agent.name,
    email: agent.email,
    role:
      agent.role === 'admin'
        ? 'Admin'
        : agent.role === 'supervisor'
        ? 'Supervisor'
        : 'Agent',
  }
  dialog.value = true
}

function roleBadgeColor(role) {
  if (role === 'admin') return 'indigo-darken-3'
  if (role === 'supervisor') return 'teal-darken-2'
  return 'blue-darken-2'
}

function statusBadgeColor(status) {
  if (status === 'online') return 'green-darken-2'
  if (status === 'offline') return 'grey-darken-1'
  return 'amber-darken-2'
}

async function approveAgent(id) {
  if (!confirm('Approve this agent?')) return
  await axios.post(`/agents/${id}/approve`)

  // update lokal (optional, tapi rapi)
  const agent = agents.value.find(a => a.id === id)
  if (agent) agent.approved_at = new Date()
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
    } else {
      const res = await axios.post('/agents', form.value)
      if (res?.data) agents.value.push(res.data)
    }

    dialog.value = false
  } catch (e) {
    console.error('Submit agent failed:', e)
    alert(e.response?.data?.message ?? 'Failed to save agent')
  } finally {
    formLoading.value = false
  }
}


async function deleteAgent(agent) {
  if (!confirm(`Delete agent ${agent.name}?`)) return

  await axios.delete(`/agents/${agent.id}`)

  // 🔥 REAL-TIME UI (TANPA RELOAD)
  agents.value = agents.value.filter(a => a.id !== agent.id)
}
</script>


<template>
  <Head title="Agents" />

  <AdminLayout>
    <template #title>Agents</template>

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
      </div>

      <!-- MAIN -->
      <v-card class="main-card pa-5">
        <div class="d-flex justify-space-between align-center mb-4">
          <div>
            <h3 class="text-white">Agents Management</h3>
            <p class="text-muted">Kelola agent dan supervisor customer service</p>
          </div>

          <v-btn color="primary" prepend-icon="mdi-account-plus" @click="openCreate">
            Add Agent
          </v-btn>
        </div>

        <!-- FILTER -->
        <div class="d-flex justify-space-between mb-4">
          <div class="d-flex gap-2">
            <v-btn size="small" color="primary" :variant="tab==='all'?'flat':'text'" @click="tab='all'">All</v-btn>
            <v-btn size="small" color="primary" :variant="tab==='online'?'flat':'text'" @click="tab='online'">Online</v-btn>
            <v-btn size="small" color="primary" :variant="tab==='offline'?'flat':'text'" @click="tab='offline'">Offline</v-btn>
            <v-btn size="small" color="primary" :variant="tab==='pending'?'flat':'text'" @click="tab='pending'">Pending</v-btn>
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

        <!-- TABLE -->
        <v-table>
          <thead>
            <tr>
              <th>Agent</th>
              <th>Role</th>
              <th>Status</th>
              <th class="text-center">Approval</th>
              <th class="text-center">Action</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="agent in filteredAgents" :key="agent.id" class="row-hover">
              <td>
                <strong>{{ agent.name }}</strong><br>
                <small class="text-muted">{{ agent.email }}</small>
              </td>

              <td>
                <v-chip size="small" :color="roleBadgeColor(agent.role)">
                  {{ agent.role }}
                </v-chip>
              </td>

              <td>
                <v-chip size="small" :color="statusBadgeColor(agent.status)">
                  {{ agent.status }}
                </v-chip>
              </td>

              <td class="text-center">
                <v-btn
                  v-if="!agent.approved_at"
                  size="small"
                  icon="mdi-check"
                  color="green"
                  @click="approveAgent(agent.id)"
                ></v-btn>

                <v-chip v-else size="small" color="blue-darken-2" variant="tonal">
                  Approved
                </v-chip>
              </td>

              <td class="text-center">
                <v-btn
                  icon="mdi-pencil"
                  size="small"
                  variant="text"
                  @click="openEdit(agent)"
                ></v-btn>

                <v-btn
                  icon="mdi-delete"
                  size="small"
                  color="red"
                  variant="text"
                  @click="deleteAgent(agent)"
                ></v-btn>
              </td>
            </tr>
          </tbody>
        </v-table>
      </v-card>
    </div>

    <!-- DIALOG -->
    <v-dialog v-model="dialog" max-width="480" content-class="agents-dialog-dark">
      <v-card class="pa-4">
        <h3 class="mb-4 text-white">{{ isEdit ? 'Edit Agent' : 'Add Agent' }}</h3>

        <v-text-field v-model="form.name" label="Full Name" />
        <v-text-field v-model="form.email" label="Email" />
        <v-select v-model="form.role" :items="['Admin','Supervisor','Agent']" label="Role" />

        <div class="d-flex justify-end mt-4 gap-2">
          <v-btn variant="text" @click="dialog=false">Cancel</v-btn>
          <v-btn color="primary" :loading="formLoading" @click="submitForm">Save</v-btn>
        </div>
      </v-card>
    </v-dialog>
  </AdminLayout>
</template>

<style scoped>
.agents-dark {
  color: #e5e7eb;
}

.main-card {
  background: linear-gradient(180deg, #020617, #0f172a);
  border: 1px solid rgba(255,255,255,0.06);
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px,1fr));
  gap: 16px;
}

.summary-card {
  padding: 16px;
  border-radius: 14px;
  background: #020617;
  border-left: 4px solid #2563eb;
}

.summary-card.online { border-color: #22c55e; }
.summary-card.offline { border-color: #64748b; }
.summary-card.pending { border-color: #f59e0b; }

.text-muted {
  color: #94a3b8;
}

.row-hover:hover {
  background: rgba(59,130,246,0.08);
}

/* DIALOG (TELEPORT SAFE) */
:deep(.agents-dialog-dark) {
  background: linear-gradient(180deg, #020617, #0f172a);
  color: #e5e7eb;
}

/* ===============================
   FORCE DARK TABLE (VUETIFY)
================================ */

/* wrapper */
.agents-dark :deep(.v-table),
.agents-dark :deep(.v-table__wrapper),
.agents-dark :deep(.v-table__wrapper table) {
  background: transparent !important;
}

/* thead */
.agents-dark :deep(.v-table thead),
.agents-dark :deep(.v-table thead tr),
.agents-dark :deep(.v-table thead th) {
  background: #020617 !important;
  color: #94a3b8 !important;
  border-bottom: 1px solid rgba(255,255,255,0.08);
}

/* tbody rows */
.agents-dark :deep(.v-table tbody),
.agents-dark :deep(.v-table tbody tr) {
  background: transparent !important;
  color: #e5e7eb !important;
}

/* tbody cells */
.agents-dark :deep(.v-table tbody td) {
  background: transparent !important;
  color: #e5e7eb !important;
  border-bottom: 1px solid rgba(255,255,255,0.06);
}

/* hover */
.agents-dark :deep(.v-table tbody tr:hover) {
  background: rgba(59,130,246,0.08) !important;
}


</style>
