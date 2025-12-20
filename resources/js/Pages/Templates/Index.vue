<script setup>
/**
 * Templates Index.vue
 * UI REMAKE – DARK WABA
 * LOGIC TIDAK DIUBAH
 */

import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'
import axios from 'axios'

// ===== STATE =====
const templates = ref([])
const loading = ref(false)

const modalAdd = ref(false)
const modalEdit = ref(false)
const modalView = ref(false)
const modalSend = ref(false)

const selected = ref(null)

const form = ref({
  name: '',
  category: 'Utility',
  language: 'id',
  header: '',
  body: '',
  footer: '',
  buttons: null,
})

const sendForm = ref({
  to: '',
  language: '',
  componentsJson: '',
})

const formLoading = ref(false)
const actionLoading = ref(false)

// ===== UTIL =====
const statusColor = (status) => {
  if (!status) return 'grey-darken-1'
  return {
    approved: 'green-darken-2',
    submitted: 'blue-darken-2',
    draft: 'grey-darken-1',
    rejected: 'red-darken-2',
    pending: 'amber-darken-2',
  }[status.toLowerCase()] || 'grey-darken-1'
}

function tryParseJSON(text) {
  try { return JSON.parse(text) } catch { return null }
}

function fmtDate(d) {
  if (!d) return '-'
  try { return new Date(d).toLocaleString() } catch { return d }
}

// ===== API =====
async function loadTemplates() {
  loading.value = true
  try {
    const r = await axios.get('/templates-list')
    templates.value = Array.isArray(r.data) ? r.data : []
  } catch {
    const r2 = await axios.get('/templates/all')
    templates.value = Array.isArray(r2.data) ? r2.data : []
  }
  loading.value = false
}

async function saveTemplate() {
  formLoading.value = true
  try {
    const payload = { ...form.value }
    if (typeof payload.buttons === 'string' && payload.buttons.trim()) {
      payload.buttons = tryParseJSON(payload.buttons) || payload.buttons
    }

    if (modalEdit.value) {
      await axios.put(`/templates/${selected.value.id}`, payload)
    } else {
      await axios.post('/templates', payload)
    }

    modalAdd.value = false
    modalEdit.value = false
    await loadTemplates()
  } finally {
    formLoading.value = false
  }
}

async function deleteTemplate(id) {
  if (!confirm('Delete template?')) return

  await axios.delete(`/templates/${id}`)

  templates.value = templates.value.filter(t => t.id !== id)
}


async function syncTemplate(id) {
  if (!confirm('Sync template?')) return
  actionLoading.value = true
  await axios.post(`/templates/${id}/sync`)
  await loadTemplates()
  actionLoading.value = false
}

async function submitForApproval(id) {
  if (!confirm('Submit for approval?')) return
  actionLoading.value = true
  await axios.post(`/templates/${id}/submit`)
  modalView.value = false
  await loadTemplates()
  actionLoading.value = false
}

async function approveTemplate(id) {
  if (!confirm('Approve template?')) return
  actionLoading.value = true
  await axios.post(`/templates/${id}/approve`)
  modalView.value = false
  await loadTemplates()
  actionLoading.value = false
}

async function rejectTemplate(id) {
  const reason = prompt('Reason:')
  if (!reason) return
  actionLoading.value = true
  await axios.post(`/templates/${id}/reject`, { reason })
  modalView.value = false
  await loadTemplates()
  actionLoading.value = false
}

// ===== UI OPENERS =====
function openAdd() {
  modalAdd.value = true
  form.value = {
    name: '',
    category: 'Utility',
    language: 'id',
    header: '',
    body: '',
    footer: '',
    buttons: null,
  }
}

function openEdit(item) {
  selected.value = item
  modalEdit.value = true
  form.value = {
    name: item.name,
    category: item.category,
    language: item.language,
    header: item.header,
    body: item.body,
    footer: item.footer,
    buttons: item.buttons ? JSON.stringify(item.buttons, null, 2) : null,
  }
}

function openView(item) {
  selected.value = item
  modalView.value = true
}

function openSendModal(item) {
  selected.value = item
  sendForm.value = {
    to: '',
    language: item.language || 'id',
    componentsJson: '',
  }
  modalSend.value = true
}

async function sendTemplate() {
  if (!sendForm.value.to) return alert('Phone required')

  actionLoading.value = true
  const payload = {
    to: sendForm.value.to,
    language: sendForm.value.language,
  }

  if (sendForm.value.componentsJson.trim()) {
    payload.components = JSON.parse(sendForm.value.componentsJson)
  }

  await axios.post(`/templates/${selected.value.id}/send`, payload)
  modalSend.value = false
  actionLoading.value = false
}

onMounted(loadTemplates)
</script>

<template>
  <Head title="Templates" />

  <AdminLayout>
    <template #title>Templates</template>

    <div class="templates-dark">

      <!-- HEADER -->
      <div class="header-card mb-6">
        <div>
          <h3>WhatsApp Templates</h3>
          <p>Kelola template dan sinkronisasi ke Meta API</p>
        </div>

        <div class="d-flex gap-2">
          <v-btn color="primary" prepend-icon="mdi-sync" @click="loadTemplates" :loading="loading">
            Refresh
          </v-btn>
          <v-btn color="primary" prepend-icon="mdi-plus" @click="openAdd">
            New Template
          </v-btn>
        </div>
      </div>

      <!-- TABLE -->
      <v-card class="main-card pa-0">
        <v-table>
          <thead>
            <tr>
              <th>Name</th>
              <th>Category</th>
              <th>Lang</th>
              <th>Status</th>
              <th>Updated</th>
              <th class="text-center">Action</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="t in templates" :key="t.id" class="row-hover">
              <td>
                <strong>{{ t.name }}</strong>
              </td>
              <td>{{ t.category }}</td>
              <td>{{ t.language }}</td>
              <td>
                <v-chip size="small" :color="statusColor(t.status)">
                  {{ t.status }}
                </v-chip>
              </td>
              <td>{{ fmtDate(t.updated_at) }}</td>

              <td class="text-center actions">
                <v-btn icon="mdi-eye" variant="text" @click="openView(t)"></v-btn>
                <v-btn icon="mdi-pencil" variant="text" @click="openEdit(t)"></v-btn>
                <v-btn icon="mdi-sync" color="blue" @click="syncTemplate(t.id)"></v-btn>
                <v-btn icon="mdi-send" color="teal" @click="openSendModal(t)"></v-btn>
                <v-btn icon="mdi-delete" color="red" @click="deleteTemplate(t.id)"></v-btn>
              </td>
            </tr>

            <tr v-if="!templates.length">
              <td colspan="6" class="text-center pa-6 text-muted">
                No templates found
              </td>
            </tr>
          </tbody>
        </v-table>
      </v-card>
    </div>

    <!-- ALL DIALOGS -->
    <v-dialog v-model="modalAdd" max-width="640" theme="dark">
      <v-card class="pa-4">
        <h3>Add Template</h3>
        <v-text-field v-model="form.name" label="Name" />
        <v-select v-model="form.category" :items="['Utility','Marketing','Authentication']" label="Category" />
        <v-select v-model="form.language" :items="['id','en']" label="Language" />
        <v-textarea v-model="form.body" label="Body" rows="4" />
        <div class="d-flex justify-end mt-4">
          <v-btn variant="text" @click="modalAdd=false">Cancel</v-btn>
          <v-btn color="primary" :loading="formLoading" @click="saveTemplate">Save</v-btn>
        </div>
      </v-card>
    </v-dialog>

    <v-dialog v-model="modalEdit" max-width="640" theme="dark">
      <v-card class="pa-4">
        <h3>Edit Template</h3>
        <v-text-field v-model="form.name" label="Name" />
        <v-select v-model="form.category" :items="['Utility','Marketing','Authentication']" label="Category" />
        <v-select v-model="form.language" :items="['id','en']" label="Language" />
        <v-textarea v-model="form.body" label="Body" rows="4" />
        <div class="d-flex justify-end mt-4">
          <v-btn variant="text" @click="modalEdit=false">Cancel</v-btn>
          <v-btn color="primary" :loading="formLoading" @click="saveTemplate">Update</v-btn>
        </div>
      </v-card>
    </v-dialog>

    <v-dialog v-model="modalView" max-width="720" theme="dark">
      <v-card class="pa-4">
        <h3>{{ selected?.name }}</h3>
        <p class="text-muted">{{ selected?.category }}</p>
        <v-chip class="mb-3" :color="statusColor(selected?.status)">
          {{ selected?.status }}
        </v-chip>

        <div class="wa-preview">
          {{ selected?.body }}
        </div>

        <div class="d-flex justify-end mt-4 gap-2">
          <v-btn v-if="selected?.status==='draft'" @click="submitForApproval(selected.id)">Submit</v-btn>
          <v-btn v-if="selected?.status==='submitted'" color="green" @click="approveTemplate(selected.id)">Approve</v-btn>
          <v-btn v-if="selected?.status==='submitted'" color="red" @click="rejectTemplate(selected.id)">Reject</v-btn>
          <v-btn variant="text" @click="modalView=false">Close</v-btn>
        </div>
      </v-card>
    </v-dialog>

    <v-dialog v-model="modalSend" max-width="600" theme="dark">
      <v-card class="pa-4">
        <h3>Send Template</h3>
        <v-text-field v-model="sendForm.to" label="Phone" />
        <v-select v-model="sendForm.language" :items="['id','en']" label="Language" />
        <v-textarea v-model="sendForm.componentsJson" label="Components JSON" rows="4" />
        <div class="d-flex justify-end mt-4">
          <v-btn variant="text" @click="modalSend=false">Cancel</v-btn>
          <v-btn color="primary" :loading="actionLoading" @click="sendTemplate">Send</v-btn>
        </div>
      </v-card>
    </v-dialog>

  </AdminLayout>
</template>

<style scoped>
.templates-dark {
  color: #e5e7eb;
}

.header-card {
  background: linear-gradient(180deg,#020617,#0f172a);
  padding: 20px;
  border-radius: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-card p {
  color: #94a3b8;
}

.main-card {
  background: linear-gradient(180deg,#020617,#0f172a);
  border: 1px solid rgba(255,255,255,.06);
}

:deep(.v-table thead th) {
  background: #020617;
  color: #94a3b8;
}

.row-hover:hover {
  background: rgba(59,130,246,.08);
}

.text-muted {
  color: #94a3b8;
}

.wa-preview {
  background: rgba(255,255,255,.05);
  padding: 16px;
  border-radius: 12px;
}

/* =====================================
   FORCE DARK TABLE - TEMPLATES (FINAL)
===================================== */

/* table wrapper */
.templates-dark :deep(.v-table),
.templates-dark :deep(.v-table__wrapper),
.templates-dark :deep(table) {
  background: transparent !important;
}

/* header */
.templates-dark :deep(thead),
.templates-dark :deep(th) {
  background: #020617 !important;
  color: #94a3b8 !important;
  border-bottom: 1px solid rgba(255,255,255,0.08);
}

/* body rows */
.templates-dark :deep(tbody),
.templates-dark :deep(tr),
.templates-dark :deep(td) {
  background: transparent !important;
  color: #e5e7eb !important;
  border-bottom: 1px solid rgba(255,255,255,0.06);
}

/* hover */
.templates-dark :deep(tbody tr:hover) {
  background: rgba(59,130,246,0.08) !important;
}

/* active / selected row (yang putih di screenshot) */
.templates-dark :deep(.v-table__row--active),
.templates-dark :deep(tr.v-table__row--active) {
  background: rgba(59,130,246,0.12) !important;
}

</style>
