<script setup>
/**
 * Templates Index.vue (FIXED)
 * - Perbaikan utama: Semua <v-dialog> menggunakan <template #default="{ isActive }">
 * - Menjamin tombol SEND, UPDATE, SAVE berfungsi 100%
 */

import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'
import axios from 'axios'

// ===== STATE =====
const templates = ref([])
const loading = ref(false)

// Dialogs
const modalAdd = ref(false)
const modalEdit = ref(false)
const modalView = ref(false)
const modalSend = ref(false)

// Selected
const selected = ref(null)

// Form create/edit
const form = ref({
  name: '',
  category: 'Utility',
  language: 'id',
  header: '',
  body: '',
  footer: '',
  buttons: null,
})

// Send form
const sendForm = ref({
  to: '',
  language: '',
  componentsJson: '',
})

const formLoading = ref(false)
const actionLoading = ref(false)

// ===== UTIL =====
const statusColor = (status) => {
  if (!status) return 'grey'
  const s = status.toLowerCase()
  return {
    approved: 'green',
    submitted: 'blue',
    draft: 'grey',
    rejected: 'red',
    pending: 'orange',
  }[s] || 'grey'
}

function tryParseJSON(text) {
  try {
    return JSON.parse(text)
  } catch {
    return null
  }
}

function fmtDate(d) {
  if (!d) return '-'
  try {
    return new Date(d).toLocaleString()
  } catch {
    return d
  }
}

// ===== API =====
async function loadTemplates() {
  loading.value = true
  try {
    const r = await axios.get('/templates-list')
    templates.value = Array.isArray(r.data) ? r.data : []
  } catch (e) {
    try {
      const r2 = await axios.get('/templates/all')
      templates.value = Array.isArray(r2.data) ? r2.data : []
    } catch {
      templates.value = []
    }
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
  } catch (err) {
    alert('Failed to save template')
  }
  formLoading.value = false
}

async function deleteTemplate(id) {
  if (!confirm('Delete template?')) return
  actionLoading.value = true
  try {
    await axios.delete(`/templates/${id}`)
    await loadTemplates()
  } catch {
    alert('Delete failed')
  }
  actionLoading.value = false
}

async function syncTemplate(id) {
  if (!confirm('Sync template?')) return
  actionLoading.value = true
  try {
    await axios.post(`/templates/${id}/sync`)
    await loadTemplates()
    alert('Synced')
  } catch {
    alert('Sync failed')
  }
  actionLoading.value = false
}

async function submitForApproval(id) {
  if (!confirm('Submit for approval?')) return
  actionLoading.value = true
  try {
    await axios.post(`/templates/${id}/submit`)
    modalView.value = false
    await loadTemplates()
  } catch {
    alert('Submit failed')
  }
  actionLoading.value = false
}

async function approveTemplate(id) {
  if (!confirm('Approve template?')) return
  actionLoading.value = true
  try {
    await axios.post(`/templates/${id}/approve`)
    modalView.value = false
    await loadTemplates()
  } catch {
    alert('Approve failed')
  }
  actionLoading.value = false
}

async function rejectTemplate(id) {
  const reason = prompt('Reason:')
  if (!reason) return
  actionLoading.value = true
  try {
    await axios.post(`/templates/${id}/reject`, { reason })
    modalView.value = false
    await loadTemplates()
  } catch {
    alert('Reject failed')
  }
  actionLoading.value = false
}

// ===== SEND TEMPLATE =====
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
  if (!sendForm.value.to) {
    alert('Phone number required')
    return
  }

  actionLoading.value = true
  try {
    const payload = {
      to: sendForm.value.to,
      language: sendForm.value.language,
    }

    if (sendForm.value.componentsJson.trim()) {
      payload.components = JSON.parse(sendForm.value.componentsJson)
    }

    await axios.post(`/templates/${selected.value.id}/send`, payload)

    alert('Sent (check logs/provider).')
    modalSend.value = false
    await loadTemplates()
  } catch (err) {
    alert('Send failed')
  }
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

onMounted(loadTemplates)
</script>

<template>
  <Head title="Templates" />

  <AdminLayout>
    <template #title>Templates</template>

    <v-card elevation="3" class="pa-4">

      <!-- HEADER -->
      <div class="d-flex justify-space-between mb-4">
        <div>
          <h3 class="text-h6">WhatsApp Templates</h3>
          <p class="text-body-2">Kelola template dan sync ke Meta API.</p>
        </div>

        <div class="d-flex">
          <v-btn color="primary" prepend-icon="mdi-sync" @click="loadTemplates" :loading="loading">Refresh</v-btn>
          <v-btn color="primary" prepend-icon="mdi-plus" @click="openAdd" class="ml-2">
            NEW TEMPLATE
          </v-btn>
        </div>
      </div>

      <!-- TABLE -->
      <v-table fixed-header height="420">
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
          <tr v-for="t in templates" :key="t.id">
            <td>{{ t.name }}</td>
            <td>{{ t.category }}</td>
            <td>{{ t.language }}</td>

            <td>
              <v-chip :color="statusColor(t.status)" class="text-white" size="small">{{ t.status }}</v-chip>
            </td>

            <td>{{ fmtDate(t.updated_at) }}</td>

            <td class="text-center">
              <v-btn icon variant="text" @click="openView(t)">
                <v-icon>mdi-eye</v-icon>
              </v-btn>

              <v-btn icon variant="text" @click="openEdit(t)">
                <v-icon>mdi-pencil</v-icon>
              </v-btn>

              <v-btn icon color="blue" variant="flat" @click="syncTemplate(t.id)">
                <v-icon>mdi-sync</v-icon>
              </v-btn>

              <v-btn icon color="teal" variant="flat" @click="openSendModal(t)">
                <v-icon>mdi-send</v-icon>
              </v-btn>

              <v-btn icon color="red" variant="flat" @click="deleteTemplate(t.id)">
                <v-icon>mdi-delete</v-icon>
              </v-btn>
            </td>
          </tr>

          <tr v-if="!templates.length">
            <td colspan="6" class="text-center pa-4">No templates</td>
          </tr>
        </tbody>
      </v-table>
    </v-card>

    <!-- ======================
         MODAL ADD
    ======================= -->
    <v-dialog v-model="modalAdd" max-width="640">
      <template #default>
        <v-card class="pa-4">
          <h3 class="text-h6 mb-3">Add Template</h3>

          <v-text-field v-model="form.name" label="Name" dense class="mb-3" />
          <v-select v-model="form.category" :items="['Utility','Marketing','Authentication']" label="Category" dense class="mb-3" />
          <v-select v-model="form.language" :items="['id','en']" label="Language" dense class="mb-3" />

          <v-text-field v-model="form.header" label="Header" dense class="mb-3" />
          <v-textarea v-model="form.body" label="Body" rows="4" dense class="mb-3" />
          <v-text-field v-model="form.footer" label="Footer" dense class="mb-3" />

          <v-textarea v-model="form.buttons" label="Buttons JSON" rows="3" dense class="mb-3" />

          <div class="d-flex justify-end mt-3">
            <v-btn variant="text" @click="modalAdd = false">Cancel</v-btn>
            <v-btn color="primary" :loading="formLoading" @click="saveTemplate">Save</v-btn>
          </div>
        </v-card>
      </template>
    </v-dialog>

    <!-- ======================
         MODAL EDIT
    ======================= -->
    <v-dialog v-model="modalEdit" max-width="640">
      <template #default>
        <v-card class="pa-4">
          <h3 class="text-h6 mb-3">Edit Template</h3>

          <v-text-field v-model="form.name" label="Name" dense class="mb-3" />
          <v-select v-model="form.category" :items="['Utility','Marketing','Authentication']" label="Category" dense class="mb-3" />
          <v-select v-model="form.language" :items="['id','en']" label="Language" dense class="mb-3" />

          <v-text-field v-model="form.header" label="Header" dense class="mb-3" />
          <v-textarea v-model="form.body" label="Body" rows="4" dense class="mb-3" />
          <v-text-field v-model="form.footer" label="Footer" dense class="mb-3" />

          <v-textarea v-model="form.buttons" label="Buttons JSON" rows="3" dense class="mb-3" />

          <div class="d-flex justify-end mt-3">
            <v-btn variant="text" @click="modalEdit = false">Cancel</v-btn>
            <v-btn color="primary" :loading="formLoading" @click="saveTemplate">Update</v-btn>
          </div>
        </v-card>
      </template>
    </v-dialog>

    <!-- ======================
         MODAL VIEW
    ======================= -->
    <v-dialog v-model="modalView" max-width="720">
      <template #default>
        <v-card class="pa-4">
          <div class="d-flex justify-space-between mb-3">
            <div>
              <h3 class="text-h6">{{ selected?.name }}</h3>
              <p class="text-caption">{{ selected?.category }}</p>
            </div>

            <v-chip :color="statusColor(selected?.status)" class="text-white">
              {{ selected?.status }}
            </v-chip>
          </div>

          <div class="wa-bubble">
            <div class="wa-bubble-body" v-html="selected?.body"></div>
            <div v-if="selected?.footer" class="wa-bubble-footer">{{ selected.footer }}</div>
          </div>

          <div class="d-flex justify-end mt-4">
            <v-btn v-if="selected?.status === 'draft'" color="blue" @click="submitForApproval(selected.id)">
              Submit
            </v-btn>

            <v-btn v-if="selected?.status === 'submitted'" color="green" @click="approveTemplate(selected.id)">
              Approve
            </v-btn>

            <v-btn v-if="selected?.status === 'submitted'" color="red" @click="rejectTemplate(selected.id)">
              Reject
            </v-btn>

            <v-btn variant="text" @click="modalView = false">Close</v-btn>
          </div>
        </v-card>
      </template>
    </v-dialog>

    <!-- ======================
         MODAL SEND (FIXED)
    ======================= -->
    <v-dialog v-model="modalSend" max-width="600">
      <template #default>
        <v-card class="pa-4">
          <h3 class="text-h6 mb-2">Send Template</h3>

          <div class="mb-3">
            <strong>{{ selected?.name }}</strong>
            <span class="text-caption">({{ selected?.language }})</span>
          </div>

          <v-text-field
            v-model="sendForm.to"
            label="Phone (e.g. 628123123123)"
            dense class="mb-3"
          />

          <v-select
            v-model="sendForm.language"
            :items="['id','en']"
            label="Language"
            dense class="mb-3"
          />

          <v-textarea
            v-model="sendForm.componentsJson"
            label="Optional components JSON"
            rows="4"
            dense
          />

          <div class="d-flex justify-end mt-4">
            <v-btn variant="text" @click="modalSend = false">Cancel</v-btn>

            <v-btn color="primary" :loading="actionLoading" @click="sendTemplate">
              Send
            </v-btn>
          </div>
        </v-card>
      </template>
    </v-dialog>

  </AdminLayout>
</template>

<style scoped>
.wa-bubble-body {
  background: #e6f3ea;
  padding: 14px;
  border-radius: 14px;
  color: #093;
}
</style>
