<script setup>
import { ref, computed } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import axios from 'axios'

/* ======================
   PROPS
====================== */
const props = defineProps({
  users: Array,
  counts: Object,
})

/* ======================
   LOCAL STATE
====================== */
const users = ref([...props.users])

/* ======================
   AUTH
====================== */
const page = usePage()
const meRole = computed(() => page.props.auth?.user?.role ?? 'agent')
const myUserId = computed(() => page.props.auth?.user?.id)
const canManageUsers = computed(() =>
  ['superadmin', 'admin'].includes(meRole.value)
)

/* ======================
   STATE
====================== */
const tab = ref('all')
const roleTab = ref('all')
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

// Password Display Dialog State
const passwordDialog = ref(false)
const temporaryPassword = ref('')

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

function showPassword(password) {
  temporaryPassword.value = password
  passwordDialog.value = true
}

function copyToClipboard(text) {
  navigator.clipboard.writeText(text).then(() => {
    showNotification('Password copied to clipboard', 'success', 2000)
  }).catch(() => {
    showNotification('Failed to copy password', 'error', 2000)
  })
}

/* ======================
   LOCK HELPERS
====================== */
function isLocked(user) {
  if (!user.locked_until) return false
  return new Date(user.locked_until) > new Date()
}

/* ======================
   FILTER
====================== */
const filteredUsers = computed(() => {
  let data = users.value || []

  // Filter by status tab
  if (tab.value !== 'all') {
    data = data.filter(u => u.status === tab.value)
  }

  // Filter by role tab
  if (roleTab.value !== 'all') {
    data = data.filter(u => u.role === roleTab.value)
  }

  // Search filter
  if (search.value) {
    const q = search.value.toLowerCase()
    data = data.filter(u =>
      u.name.toLowerCase().includes(q) ||
      u.email.toLowerCase().includes(q)
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

function openEdit(user) {
  isEdit.value = true
  form.value = {
    id: user.id,
    name: user.name,
    email: user.email,
    role:
      user.role === 'superadmin'
        ? 'Superadmin'
        : user.role === 'admin'
        ? 'Admin'
        : user.role === 'supervisor'
        ? 'Supervisor'
        : 'Agent',
  }
  dialog.value = true
}

function roleBadgeColor(role) {
  if (role === 'superadmin') return 'red-darken-3'
  if (role === 'admin') return 'indigo-darken-3'
  if (role === 'supervisor') return 'teal-darken-2'
  return 'blue-darken-2'
}

function statusBadgeColor(status) {
  if (status === 'online') return 'green-darken-2'
  if (status === 'offline') return 'grey-darken-1'
  return 'amber-darken-2'
}

async function approveUser(id) {
  showConfirm(
    'Approve User',
    'Are you sure you want to approve this user?',
    async () => {
      try {
        await axios.post(`/users/${id}/approve`)
        const user = users.value.find(u => u.id === id)
        if (user) user.approved_at = new Date()
        showNotification('User approved successfully', 'success')
      } catch (e) {
        showNotification(e.response?.data?.message ?? 'Failed to approve user', 'error')
      }
    },
    'green'
  )
}

async function unlockUser(user) {
  showConfirm(
    'Unlock Account',
    `Unlock account for ${user.name}?`,
    async () => {
      try {
        await axios.post(`/users/${user.id}/unlock`)
        user.failed_login_attempts = 0
        user.locked_until = null
        showNotification('Account unlocked successfully', 'success')
      } catch (e) {
        showNotification(e.response?.data?.message ?? 'Failed to unlock account', 'error')
      }
    },
    'orange'
  )
}

async function resetPassword(user) {
  showConfirm(
    'Reset Password',
    `Reset password for ${user.name}? A new temporary password will be generated.`,
    async () => {
      try {
        const res = await axios.post(`/users/${user.id}/reset-password`)
        showPassword(res.data.temp_password)
      } catch (e) {
        showNotification(e.response?.data?.message ?? 'Failed to reset password', 'error')
      }
    },
    'orange'
  )
}

async function submitForm() {
  formLoading.value = true
  try {
    if (isEdit.value) {
      await axios.put(`/users/${form.value.id}`, form.value)
      const idx = users.value.findIndex(u => u.id === form.value.id)
      if (idx !== -1) {
        users.value[idx] = { ...users.value[idx], ...form.value }
      }
      showNotification('User updated successfully', 'success')
    } else {
      const res = await axios.post('/users', form.value)
      if (res?.data?.user) {
        users.value.push(res.data.user)
        // Show temporary password
        if (res.data.temp_password) {
          dialog.value = false
          showPassword(res.data.temp_password)
          showNotification('User created successfully', 'success')
          return
        }
      }
      showNotification('User created successfully', 'success')
    }
    dialog.value = false
  } catch (e) {
    showNotification(e.response?.data?.message ?? 'Failed to save user', 'error')
  } finally {
    formLoading.value = false
  }
}

async function deleteUser(user) {
  if (user.id === myUserId.value) {
    showNotification('Cannot delete yourself', 'warning')
    return
  }

  showConfirm(
    'Delete User',
    `Are you sure you want to delete user ${user.name}? This action cannot be undone.`,
    async () => {
      try {
        await axios.delete(`/users/${user.id}`)
        users.value = users.value.filter(u => u.id !== user.id)
        showNotification('User deleted successfully', 'success')
      } catch (e) {
        showNotification(e.response?.data?.message ?? 'Failed to delete user', 'error')
      }
    },
    'red'
  )
}
</script>

<template>
  <Head title="Users Management" />

  <AdminLayout>
    <template #title>Users Management</template>

    <div v-if="canManageUsers" class="agents-dark">

      <!-- SUMMARY -->
      <div class="summary-grid mb-6">
        <div class="summary-card">
          <span>Total Users</span>
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
      </div>

      <!-- MAIN -->
      <v-card class="main-card pa-5">
        <div class="d-flex justify-space-between align-center mb-4">
          <div>
            <h3 class="text-white">Users Management</h3>
            <p class="text-muted">Manage all system users (agents, supervisors, admins, superadmins)</p>
          </div>
          <v-btn color="primary" prepend-icon="mdi-account-plus" @click="openCreate">
            Add User
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
            </div>

            <v-text-field
              v-model="search"
              density="compact"
              prepend-inner-icon="mdi-magnify"
              placeholder="Search user..."
              hide-details
              style="max-width:260px"
            />
          </div>

          <div class="d-flex gap-2">
            <v-chip size="small" :color="roleTab==='all'?'primary':''" @click="roleTab='all'">All Roles</v-chip>
            <v-chip size="small" :color="roleTab==='agent'?'primary':''" @click="roleTab='agent'">Agents</v-chip>
            <v-chip size="small" :color="roleTab==='supervisor'?'primary':''" @click="roleTab='supervisor'">Supervisors</v-chip>
            <v-chip size="small" :color="roleTab==='admin'?'primary':''" @click="roleTab='admin'">Admins</v-chip>
            <v-chip size="small" :color="roleTab==='superadmin'?'primary':''" @click="roleTab='superadmin'">Superadmins</v-chip>
          </div>
        </div>

        <!-- TABLE -->
        <v-table>
          <thead>
            <tr>
              <th>User</th>
              <th>Role</th>
              <th>Status</th>
              <th class="text-center">Approval</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="user in filteredUsers" :key="user.id" class="row-hover">
              <td>
                <strong>{{ user.name }}</strong><br />
                <small class="text-muted">{{ user.email }}</small>
              </td>

              <td>
                <v-chip size="small" :color="roleBadgeColor(user.role)">
                  {{ user.role }}
                </v-chip>
              </td>

              <td>
                <v-chip
                  v-if="isLocked(user)"
                  size="small"
                  class="locked"
                >
                  🔒 Locked
                </v-chip>

                <v-chip
                  v-else
                  size="small"
                  :color="statusBadgeColor(user.status)"
                >
                  {{ user.status }}
                </v-chip>
              </td>

              <td class="text-center">
                <v-btn
                  v-if="!user.approved_at"
                  size="small"
                  icon="mdi-check"
                  color="green"
                  @click="approveUser(user.id)"
                />
                <v-chip v-else size="small" color="blue-darken-2" variant="tonal">
                  Approved
                </v-chip>
              </td>

              <td class="text-center">
                <v-btn
                  v-if="isLocked(user)"
                  icon="mdi-lock-open-variant"
                  size="small"
                  class="unlock"
                  variant="text"
                  @click="unlockUser(user)"
                />
                <v-btn
                  icon="mdi-key-variant"
                  size="small"
                  variant="text"
                  color="orange"
                  @click="resetPassword(user)"
                />
                <v-btn icon="mdi-pencil" size="small" variant="text" @click="openEdit(user)" />
                <v-btn
                  v-if="user.id !== myUserId"
                  icon="mdi-delete"
                  size="small"
                  color="red"
                  variant="text"
                  @click="deleteUser(user)"
                />
              </td>
            </tr>
          </tbody>
        </v-table>
      </v-card>
    </div>

    <!-- FORM DIALOG -->
    <v-dialog v-model="dialog" max-width="480" content-class="agents-dialog-dark">
      <v-card class="pa-4">
        <h3 class="mb-4 text-white">{{ isEdit ? 'Edit User' : 'Add User' }}</h3>
        <v-text-field v-model="form.name" label="Full Name" />
        <v-text-field v-model="form.email" label="Email" />
        <v-select v-model="form.role" :items="['Superadmin','Admin','Supervisor','Agent']" label="Role" />
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

    <!-- PASSWORD DISPLAY DIALOG -->
    <v-dialog v-model="passwordDialog" max-width="600">
      <v-card>
        <v-card-title class="text-h5 d-flex align-center">
          <v-icon color="success" class="mr-2">mdi-key-variant</v-icon>
          Temporary Password Generated
        </v-card-title>
        <v-card-text class="pt-4">
          <v-alert type="info" variant="tonal" class="mb-4">
            Please save this password and share it with the user. This password will not be shown again.
          </v-alert>

          <v-text-field
            v-model="temporaryPassword"
            label="Temporary Password"
            readonly
            variant="outlined"
            class="password-field"
          >
            <template #append-inner>
              <v-btn
                icon="mdi-content-copy"
                variant="text"
                size="small"
                @click="copyToClipboard(temporaryPassword)"
              />
            </template>
          </v-text-field>

          <div class="text-center mt-2">
            <v-chip color="success" size="large" class="font-weight-bold">
              {{ temporaryPassword }}
            </v-chip>
          </div>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn
            color="primary"
            variant="flat"
            @click="passwordDialog = false"
          >
            Close
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
.text-muted{color:#94a3b8;}
.row-hover:hover{background:rgba(59,130,246,.08);}
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

.summary-card.locked {
  border-color: #ef4444;
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
