<script setup>
import { ref, computed } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import axios from 'axios'

// ==== PROPS DARI LARAVEL ====
const props = defineProps({
  agents: { type: Array, default: () => [] },
  counts: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
})

// ==== ROLE GUARD (HANYA ADMIN / SUPERVISOR) ====
const page = usePage()
const meRole = computed(() => page.props.auth?.user?.role ?? 'agent')
const canManageAgents = computed(() => ['admin', 'supervisor'].includes(meRole.value))

// ==== STATE UI ====
const tab = ref('all')           // all | online | offline | pending
const search = ref('')
const loading = ref(false)

// Dialog form
const dialog = ref(false)
const isEdit = ref(false)
const formLoading = ref(false)
const form = ref({
  id: null,
  name: '',
  email: '',
  role: 'Agent',
})

// ==== FILTERED LIST ====
const filteredAgents = computed(() => {
  let data = props.agents || []

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

// ==== HELPER ====
function openCreate() {
  isEdit.value = false
  form.value = {
    id: null,
    name: '',
    email: '',
    role: 'Agent',
  }
  dialog.value = true
}

function openEdit(agent) {
  isEdit.value = true
  form.value = {
    id: agent.id,
    name: agent.name,
    email: agent.email,
    role: agent.role === 'admin'
      ? 'Admin'
      : agent.role === 'supervisor'
        ? 'Supervisor'
        : 'Agent',
  }
  dialog.value = true
}

function roleBadgeColor(role) {
  if (role === 'admin') return 'deep-purple'
  if (role === 'supervisor') return 'green'
  return 'blue'
}

function statusBadgeColor(status) {
  if (status === 'online') return 'green'
  if (status === 'offline') return 'grey'
  return 'orange'
}

// ==== APPROVE ====
async function approveAgent(id) {
  if (!confirm('Approve this agent?')) return
  try {
    loading.value = true
    await axios.post(`/agents/${id}/approve`)
    window.location.reload()
  } catch (e) {
    console.error(e)
    alert('Failed to approve agent')
  } finally {
    loading.value = false
  }
}

// ==== CREATE / UPDATE ====
async function submitForm() {
  formLoading.value = true

  try {
    if (isEdit.value && form.value.id) {
      await axios.put(`/agents/${form.value.id}`, {
        name: form.value.name,
        email: form.value.email,
        role: form.value.role,
      })
    } else {
      await axios.post('/agents', {
        name: form.value.name,
        email: form.value.email,
        role: form.value.role,
      })
    }

    dialog.value = false
    window.location.reload()
  } catch (e) {
    console.error(e)
    alert('Failed to save agent')
  } finally {
    formLoading.value = false
  }
}

// ==== DELETE ====
async function deleteAgent(agent) {
  if (!confirm(`Delete agent ${agent.name}?`)) return

  try {
    loading.value = true
    await axios.delete(`/agents/${agent.id}`)
    window.location.reload()
  } catch (e) {
    console.error(e)
    alert('Failed to delete agent')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <Head title="Agents" />

  <AdminLayout>
    <template #title>Agents</template>

    <!-- Jika BUKAN admin/supervisor -->
    <v-alert
      v-if="!canManageAgents"
      type="error"
      variant="tonal"
      title="Access Denied"
      class="mb-4"
    >
      You don't have permission to manage agents.
    </v-alert>

    <v-card v-if="canManageAgents" class="pa-4" elevation="2">
      <!-- HEADER -->
      <div class="d-flex flex-wrap align-center justify-space-between mb-4">
        <div>
          <h3 class="text-h6 mb-1">Agents Management</h3>
          <p class="text-body-2 text-grey-darken-1">
            Kelola daftar agent yang bertanggung jawab menjawab pesan pelanggan.
          </p>
        </div>

        <v-btn color="primary" prepend-icon="mdi-account-plus" @click="openCreate">
          Add Agent
        </v-btn>
      </div>

      <!-- FILTER BAR -->
      <div class="d-flex flex-wrap align-center justify-space-between mb-4 gap-3">
        <div class="d-flex flex-wrap gap-2">
          <v-btn
            size="small"
            :variant="tab === 'all' ? 'flat' : 'text'"
            color="primary"
            class="font-weight-medium"
            @click="tab = 'all'"
          >
            All ({{ counts.all ?? 0 }})
          </v-btn>

          <v-btn
            size="small"
            :variant="tab === 'online' ? 'flat' : 'text'"
            color="green"
            class="font-weight-medium"
            @click="tab = 'online'"
          >
            Online ({{ counts.online ?? 0 }})
          </v-btn>

          <v-btn
            size="small"
            :variant="tab === 'offline' ? 'flat' : 'text'"
            color="grey"
            class="font-weight-medium"
            @click="tab = 'offline'"
          >
            Offline ({{ counts.offline ?? 0 }})
          </v-btn>

          <v-btn
            size="small"
            :variant="tab === 'pending' ? 'flat' : 'text'"
            color="orange"
            class="font-weight-medium"
            @click="tab = 'pending'"
          >
            Pending ({{ counts.pending ?? 0 }})
          </v-btn>
        </div>

        <v-text-field
          v-model="search"
          placeholder="Search name or email..."
          density="compact"
          hide-details
          prepend-inner-icon="mdi-magnify"
          style="max-width: 260px"
        />
      </div>

      <!-- TABLE -->
      <v-table fixed-header height="420px">
        <thead>
          <tr>
            <th class="text-left">Name</th>
            <th class="text-left">Email</th>
            <th class="text-left">Role</th>
            <th class="text-left">Status</th>
            <th class="text-center">Approval</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="agent in filteredAgents" :key="agent.id" class="hover-card">
            <td class="font-weight-medium">
              {{ agent.name }}
            </td>
            <td>{{ agent.email }}</td>

            <!-- Role badge -->
            <td>
              <v-chip
                size="small"
                :color="roleBadgeColor(agent.role)"
                class="text-white"
                variant="flat"
              >
                {{ agent.role }}
              </v-chip>
            </td>

            <!-- Status badge -->
            <td>
              <v-chip
                size="small"
                :color="statusBadgeColor(agent.status)"
                class="text-white"
                variant="flat"
              >
                {{ agent.status }}
              </v-chip>
            </td>

            <!-- Approval -->
            <td class="text-center">
              <v-tooltip v-if="agent.approved_at === null" text="Approve agent">
                <template #activator="{ props }">
                  <v-btn
                    v-bind="props"
                    color="success"
                    icon="mdi-check"
                    size="small"
                    @click="approveAgent(agent.id)"
                  />
                </template>
              </v-tooltip>

              <v-chip
                v-else
                size="small"
                color="blue"
                variant="tonal"
              >
                <v-icon start size="16">mdi-shield-check</v-icon>
                Approved
              </v-chip>
            </td>

            <!-- Action button -->
            <td class="text-center">
              <v-btn
                icon="mdi-pencil"
                size="small"
                variant="text"
                @click="openEdit(agent)"
              />
              <v-btn
                icon="mdi-delete"
                size="small"
                variant="text"
                color="red"
                @click="deleteAgent(agent)"
              />
            </td>
          </tr>

          <tr v-if="!filteredAgents.length">
            <td colspan="6" class="text-center text-grey pa-4">
              No agents found.
            </td>
          </tr>
        </tbody>
      </v-table>
    </v-card>

    <!-- DIALOG ADD / EDIT -->
    <v-dialog v-model="dialog" max-width="480">
      <v-card class="pa-4">
        <h3 class="text-h6 mb-2">
          {{ isEdit ? 'Edit Agent' : 'Add Agent' }}
        </h3>
        <p class="text-body-2 text-grey-darken-1 mb-4">
          {{ isEdit ? 'Update agent information.' : 'Create a new customer service agent.' }}
        </p>

        <v-text-field
          v-model="form.name"
          label="Full Name"
          class="mb-3"
          required
        />
        <v-text-field
          v-model="form.email"
          label="Email"
          class="mb-3"
          required
        />
        <v-select
          v-model="form.role"
          :items="['Admin', 'Supervisor', 'Agent']"
          label="Role"
          class="mb-4"
        />

        <div class="d-flex justify-end gap-2 mt-2">
          <v-btn variant="text" @click="dialog = false">
            Cancel
          </v-btn>
          <v-btn color="primary" :loading="formLoading" @click="submitForm">
            Save
          </v-btn>
        </div>
      </v-card>
    </v-dialog>
  </AdminLayout>
</template>

<style scoped>
td {
  vertical-align: middle;
}

.hover-card:hover {
  background-color: rgba(18, 131, 218, 0.07);
  transition: 0.2s ease;
}

.font-weight-medium {
  font-weight: 500;
}
</style>
