<script setup>
/**
 * Templates Index.vue
 * - Full functionality: load, list, create, update, delete
 * - Workflow: submit, approve, reject
 * - Sync per-template
 * - Send template (with optional components JSON)
 * - Modals: Add, Edit, View, Send
 *
 * Replace your existing Index.vue with this file.
 */

import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'
import axios from 'axios'

// ====== STATE ======
const templates = ref([])
const loading = ref(false)

// Dialogs / modals
const modalAdd = ref(false)
const modalEdit = ref(false)
const modalView = ref(false)
const modalSend = ref(false)

// Selected/current item
const selected = ref(null)

// Form for create/edit
const form = ref({
  name: '',
  category: 'Utility',
  language: 'id',
  header: '',
  body: '',
  footer: '',
  buttons: null, // can be JSON / array
})

// Send form (for sending a template to phone)
const sendForm = ref({
  to: '',
  language: '',
  componentsJson: '', // optional advanced components
})

// small UI state
const formLoading = ref(false)
const actionLoading = ref(false)

// ====== HELPERS / UTILS ======
const statusColor = (status) => {
  if (!status) return 'grey'
  const s = status.toString().toLowerCase()
  if (s === 'approved') return 'green'
  if (s === 'submitted') return 'blue'
  if (s === 'draft') return 'grey'
  if (s === 'rejected') return 'red'
  if (s === 'pending') return 'orange'
  return 'grey'
}

// safe parse JSON
function tryParseJSON(text) {
  if (!text) return null
  try {
    return JSON.parse(text)
  } catch (e) {
    return null
  }
}

// format date fallback
function fmtDate(d) {
  if (!d) return '-'
  try {
    return new Date(d).toISOString()
  } catch {
    return d
  }
}

// ====== API / ACTIONS ======

// load list (used by page mount and refresh)
async function loadTemplates() {
  loading.value = true
  try {
    // prefer dedicated JSON endpoint
    const res = await axios.get('/templates-list')
    templates.value = Array.isArray(res.data) ? res.data : []
  } catch (e) {
    // fallback to /templates (if it returns JSON)
    try {
      const res2 = await axios.get('/templates/all')
      templates.value = Array.isArray(res2.data) ? res2.data : []
    } catch (e2) {
      console.error('Failed loading templates', e, e2)
      templates.value = []
    }
  } finally {
    loading.value = false
  }
}

// create or update template
async function saveTemplate() {
  formLoading.value = true
  try {
    // normalize buttons if string
    const payload = { ...form.value }
    if (typeof payload.buttons === 'string' && payload.buttons.trim()) {
      payload.buttons = tryParseJSON(payload.buttons) || payload.buttons
    }

    if (modalEdit.value && selected.value?.id) {
      await axios.put(`/templates/${selected.value.id}`, payload)
    } else {
      await axios.post('/templates', payload)
    }

    modalAdd.value = false
    modalEdit.value = false
    await loadTemplates()
  } catch (err) {
    console.error(err)
    alert('Failed to save template: ' + (err.response?.data?.message || err.message))
  } finally {
    formLoading.value = false
  }
}

// delete
async function deleteTemplate(id) {
  if (!confirm('Delete this template?')) return
  actionLoading.value = true
  try {
    await axios.delete(`/templates/${id}`)
    await loadTemplates()
  } catch (err) {
    console.error(err)
    alert('Failed to delete template')
  } finally {
    actionLoading.value = false
  }
}

// sync per-template (sync with Meta/WhatsApp Cloud API)
async function syncTemplate(id) {
  if (!confirm('Sync this template with provider?')) return
  actionLoading.value = true
  try {
    await axios.post(`/templates/${id}/sync`)
    await loadTemplates()
    alert('Synced successfully (server response)')
  } catch (err) {
    console.error(err)
    alert('Sync failed: ' + (err.response?.data?.error || err.message))
  } finally {
    actionLoading.value = false
  }
}

// workflow: submit for approval (local)
async function submitForApproval(id) {
  if (!confirm('Submit this template for approval?')) return
  actionLoading.value = true
  try {
    await axios.post(`/templates/${id}/submit`)
    await loadTemplates()
    modalView.value = false
  } catch (err) {
    console.error(err)
    alert('Submit failed')
  } finally {
    actionLoading.value = false
  }
}

// approve
async function approveTemplate(id) {
  if (!confirm('Approve this template?')) return
  actionLoading.value = true
  try {
    await axios.post(`/templates/${id}/approve`)
    await loadTemplates()
    modalView.value = false
  } catch (err) {
    console.error(err)
    alert('Approve failed')
  } finally {
    actionLoading.value = false
  }
}

// reject (with notes)
async function rejectTemplate(id) {
  const reason = prompt('Reason for rejection (visible to submitter):')
  if (!reason) return
  actionLoading.value = true
  try {
    await axios.post(`/templates/${id}/reject`, { reason })
    await loadTemplates()
    modalView.value = false
  } catch (err) {
    console.error(err)
    alert('Reject failed')
  } finally {
    actionLoading.value = false
  }
}

// ====== SEND TEMPLATE ======
// prepare send modal
function openSendModal(item) {
  selected.value = item
  sendForm.value = {
    to: '',
    language: item.language || 'id',
    componentsJson: ''
  }
  modalSend.value = true
}

// send; payload may include "components" parsed from componentsJson
async function sendTemplate() {
  if (!selected.value) return
  if (!sendForm.value.to) {
    alert('Provide a phone number to send to')
    return
  }

  actionLoading.value = true
  try {
    const payload = {
      to: sendForm.value.to,
      language: sendForm.value.language || selected.value.language
    }

    if (sendForm.value.componentsJson && sendForm.value.componentsJson.trim()) {
      try {
        payload.components = JSON.parse(sendForm.value.componentsJson)
      } catch (err) {
        alert('Invalid components JSON')
        actionLoading.value = false
        return
      }
    }

    const res = await axios.post(`/templates/${selected.value.id}/send`, payload)

    // server should respond with success / warning
    if (res?.data?.warning) {
      alert('Warning: ' + res.data.warning)
    } else if (res?.data?.sent || res?.status === 200) {
      alert('Template sent (check logs/provider).')
    } else {
      alert('Send request finished, check server response.')
    }

    modalSend.value = false
    await loadTemplates()
  } catch (err) {
    console.error(err)
    alert('Send failed: ' + (err.response?.data?.error || err.message))
  } finally {
    actionLoading.value = false
  }
}

// ====== UI helpers for open modals ======
function openAdd() {
  modalAdd.value = true
  modalEdit.value = false
  selected.value = null
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
  modalAdd.value = false

  // copy server fields to form (buttons likely stored as JSON)
  form.value = {
    name: item.name || '',
    category: item.category || 'Utility',
    language: item.language || 'id',
    header: item.header || '',
    body: item.body || '',
    footer: item.footer || '',
    buttons: (item.buttons && typeof item.buttons === 'object') ? JSON.stringify(item.buttons, null, 2) : (item.buttons || null),
  }
}

function openView(item) {
  selected.value = item
  modalView.value = true
}

// ====== LIFECYCLE ======
onMounted(loadTemplates)
</script>

<template>
  <Head title="Templates" />

  <AdminLayout>
    <template #title>Templates</template>

    <v-card elevation="3" class="pa-4">

      <!-- Header -->
      <div class="d-flex align-center justify-space-between mb-4">
        <div>
          <h3 class="text-h6">WhatsApp Message Templates</h3>
          <p class="text-body-2 text-grey-darken-1">Kelola template pesan resmi WhatsApp (create, sync, workflow, send).</p>
        </div>

        <div class="d-flex align-center gap-3">
          <v-btn color="primary" prepend-icon="mdi-sync" @click="loadTemplates" :loading="loading">
            Refresh
          </v-btn>

          <v-btn color="primary" prepend-icon="mdi-plus" @click="openAdd" class="ml-2">
            NEW TEMPLATE
          </v-btn>
        </div>
      </div>

      <!-- Table -->
      <v-table fixed-header height="420" density="comfortable">
        <thead>
          <tr>
            <th>Name</th>
            <th>Category</th>
            <th>Language</th>
            <th>Status</th>
            <th>Last Updated</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="t in templates" :key="t.id" class="hover-row">
            <td class="font-weight-medium">{{ t.name }}</td>
            <td>{{ t.category }}</td>
            <td>{{ t.language }}</td>

            <td>
              <v-chip :color="statusColor(t.status)" size="small" class="text-white" variant="flat">
                {{ t.status }}
              </v-chip>
            </td>

            <td>{{ fmtDate(t.updated_at) }}</td>

            <td class="text-center">
              <div class="d-flex justify-center gap-2">
                <!-- View -->
                <v-btn icon size="small" variant="text" @click="openView(t)" title="Preview">
                  <v-icon>mdi-eye</v-icon>
                </v-btn>

                <!-- Edit -->
                <v-btn icon size="small" variant="text" @click="openEdit(t)" title="Edit">
                  <v-icon>mdi-pencil</v-icon>
                </v-btn>

                <!-- Sync -->
                <v-btn icon size="small" color="blue" variant="flat" @click="syncTemplate(t.id)" title="Sync with Provider">
                  <v-icon>mdi-sync</v-icon>
                </v-btn>

                <!-- Send -->
                <v-btn icon size="small" color="teal" variant="flat" @click="openSendModal(t)" title="Send Template">
                  <v-icon>mdi-send</v-icon>
                </v-btn>

                <!-- Delete -->
                <v-btn icon size="small" color="red" variant="flat" @click="deleteTemplate(t.id)" title="Delete">
                  <v-icon>mdi-delete</v-icon>
                </v-btn>
              </div>
            </td>
          </tr>

          <tr v-if="!templates.length">
            <td colspan="6" class="text-center pa-4 text-grey">Belum ada template. Klik NEW TEMPLATE untuk membuat.</td>
          </tr>
        </tbody>
      </v-table>
    </v-card>

    <!-- ================= MODAL ADD ================= -->
    <v-dialog v-model="modalAdd" max-width="640">
      <v-card class="pa-4">
        <h3 class="text-h6 mb-3">Add New Template</h3>

        <v-text-field v-model="form.name" label="Template Name" class="mb-3" dense />
        <v-select v-model="form.category" :items="['Utility','Marketing','Authentication']" label="Category" class="mb-3" dense />
        <v-select v-model="form.language" :items="['id','en']" label="Language" class="mb-3" dense />

        <v-text-field v-model="form.header" label="Header (optional)" class="mb-3" dense />
        <v-textarea v-model="form.body" label="Body (required)" rows="4" class="mb-3" dense />
        <v-text-field v-model="form.footer" label="Footer (optional)" class="mb-3" dense />

        <v-textarea v-model="form.buttons" label="Buttons JSON (optional)" rows="3" hint='Example: [{"type":"quick_reply","text":"Yes"}]' class="mb-3" dense />

        <div class="d-flex justify-end gap-2 mt-3">
          <v-btn variant="text" @click="modalAdd = false">Cancel</v-btn>
          <v-btn color="primary" :loading="formLoading" @click="saveTemplate">Save</v-btn>
        </div>
      </v-card>
    </v-dialog>

    <!-- ================= MODAL EDIT ================= -->
    <v-dialog v-model="modalEdit" max-width="640">
      <v-card class="pa-4">
        <h3 class="text-h6 mb-3">Edit Template</h3>

        <v-text-field v-model="form.name" label="Template Name" class="mb-3" dense />
        <v-select v-model="form.category" :items="['Utility','Marketing','Authentication']" label="Category" class="mb-3" dense />
        <v-select v-model="form.language" :items="['id','en']" label="Language" class="mb-3" dense />

        <v-text-field v-model="form.header" label="Header (optional)" class="mb-3" dense />
        <v-textarea v-model="form.body" label="Body (required)" rows="4" class="mb-3" dense />
        <v-text-field v-model="form.footer" label="Footer (optional)" class="mb-3" dense />

        <v-textarea v-model="form.buttons" label="Buttons JSON (optional)" rows="3" hint='Example: [{"type":"quick_reply","text":"Yes"}]' class="mb-3" dense />

        <div class="d-flex justify-end gap-2 mt-3">
          <v-btn variant="text" @click="modalEdit = false">Cancel</v-btn>
          <v-btn color="primary" :loading="formLoading" @click="saveTemplate">Update</v-btn>
        </div>
      </v-card>
    </v-dialog>

    <!-- ================= MODAL VIEW / PREVIEW (WhatsApp-like bubble) ================= -->
    <v-dialog v-model="modalView" max-width="720">
      <v-card class="pa-4">
        <div class="d-flex justify-space-between align-center mb-3">
          <div>
            <h3 class="text-h6 mb-1">Template Preview</h3>
            <div class="text-body-2 text-grey-darken-1">{{ selected?.name || '' }} — {{ selected?.category || '' }}</div>
          </div>

          <div>
            <v-chip :color="statusColor(selected?.status)" class="text-white">{{ selected?.status }}</v-chip>
          </div>
        </div>

        <!-- Visual preview -->
        <div class="preview-area pa-4">
          <!-- optional header (small) -->
          <div v-if="selected?.header" class="preview-header">{{ selected.header }}</div>

          <!-- bubble body -->
          <div class="wa-bubble">
            <div class="wa-bubble-body" v-html="selected?.body"></div>

            <!-- footer (small) -->
            <div v-if="selected?.footer" class="wa-bubble-footer">{{ selected.footer }}</div>

            <!-- buttons if any (simple preview) -->
            <div v-if="selected?.buttons" class="wa-bubble-buttons">
              <v-chip v-for="(b, idx) in (typeof selected.buttons === 'string' ? tryParseJSON(selected.buttons) || [] : (selected.buttons || []))"
                      :key="idx"
                      class="ma-1" small>
                {{ b?.text || b?.title || JSON.stringify(b).slice(0,20) }}
              </v-chip>
            </div>
          </div>
        </div>

        <!-- Approval notes + actions -->
        <div class="d-flex justify-end gap-2 mt-4">
          <v-btn v-if="selected?.status === 'draft'" color="blue" @click="submitForApproval(selected.id)">Submit for Approval</v-btn>

          <v-btn v-if="selected?.status === 'submitted'" color="green" @click="approveTemplate(selected.id)">Approve</v-btn>
          <v-btn v-if="selected?.status === 'submitted'" color="red" @click="rejectTemplate(selected.id)">Reject</v-btn>

          <v-btn variant="text" @click="modalView = false">Close</v-btn>
        </div>
      </v-card>
    </v-dialog>

    <!-- ================= MODAL SEND ================= -->
    <v-dialog v-model="modalSend" max-width="600">
      <v-card class="pa-4">
        <h3 class="text-h6 mb-2">Send Template</h3>
        <div v-if="selected" class="mb-3 text-body-2">
          <strong>Template:</strong> {{ selected.name }} — <small>{{ selected.language }}</small>
        </div>

        <v-text-field v-model="sendForm.to" label="Phone number (e.g. 628123...)" class="mb-3" dense />
        <v-select v-model="sendForm.language" :items="['id','en']" label="Language (override)" class="mb-3" dense />

        <v-textarea v-model="sendForm.componentsJson" label="Optional components JSON (advanced)" rows="4" hint='Example: [{"type":"body","parameters":[{"type":"text","text":"Alice"}]}]' dense />

        <div class="d-flex justify-end gap-2 mt-4">
          <v-btn variant="text" @click="modalSend = false">Cancel</v-btn>
          <v-btn color="primary" :loading="actionLoading" @click="sendTemplate">Send</v-btn>
        </div>
      </v-card>
    </v-dialog>

  </AdminLayout>
</template>

<style scoped>
.hover-row:hover {
  background-color: rgba(18, 131, 218, 0.03);
}

/* WhatsApp-like bubble preview */
.preview-area {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.preview-header {
  font-size: 13px;
  color: #6b7280;
}

.wa-bubble {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.wa-bubble-body {
  max-width: 720px;
  background: #e6f3ea; /* light green */
  padding: 14px 16px;
  border-radius: 14px;
  color: #0b4a2a;
  line-height: 1.45;
  box-shadow: 0 4px 12px rgba(0,0,0,0.06);
  font-size: 14px;
}

.wa-bubble-footer {
  font-size: 12px;
  color: #6b7280;
  margin-left: 8px;
}

.wa-bubble-buttons {
  display: flex;
  gap: 8px;
  margin-top: 6px;
  flex-wrap: wrap;
}
</style>
