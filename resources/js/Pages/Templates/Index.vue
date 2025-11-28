<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'
import axios from 'axios'

// Data template
const templates = ref([])
const loading = ref(false)

// Modal control
const modalAdd = ref(false)
const modalView = ref(false)
const modalEdit = ref(false)
const modalSend = ref(false)
const selected = ref(null)

// Form Template
const form = ref({
  name: '',
  category: '',
  language: '',
  body: ''
})

// Send form
const sendForm = ref({
  to: '',
  language: '',
  // components as JSON string (advanced). For simple cases leave empty.
  componentsJson: ''
})

// ====================== API Calls ======================

// LOAD Templates
async function loadTemplates() {
  loading.value = true
  try {
    const res = await axios.get('/templates/all') // ensure route exists and returns JSON
    templates.value = res.data
  } catch (e) {
    console.error(e)
    // fallback: try /templates (Inertia) — but prefer /templates/all
    try {
      const res2 = await axios.get('/templates')
      templates.value = res2.data
    } catch (e2) {
      console.error(e2)
    }
  }
  loading.value = false
}

// SYNC per template (ke Meta)
async function syncTemplate(id) {
  loading.value = true
  try {
    await axios.post(`/templates/${id}/sync`)
    await loadTemplates()
  } catch (e) {
    console.error(e)
    alert('Sync failed')
  }
  loading.value = false
}

// SUBMIT FOR APPROVAL (local workflow)
async function submitForApproval(id) {
  loading.value = true
  try {
    await axios.post(`/templates/${id}/submit`)
    await loadTemplates()
    modalView.value = false
  } catch (e) {
    console.error(e)
    alert('Submit failed')
  }
  loading.value = false
}

// APPROVE
async function approveTemplate(id) {
  loading.value = true
  try {
    await axios.post(`/templates/${id}/approve`)
    await loadTemplates()
    modalView.value = false
  } catch (e) {
    console.error(e)
    alert('Approve failed')
  }
  loading.value = false
}

// REJECT
async function rejectTemplate(id) {
  const reason = prompt("Reason for rejection?")
  if (!reason) return

  loading.value = true
  try {
    await axios.post(`/templates/${id}/reject`, { reason })
    await loadTemplates()
    modalView.value = false
  } catch (e) {
    console.error(e)
    alert('Reject failed')
  }
  loading.value = false
}

// SAVE (Add / Update)
async function save() {
  try {
    if (modalEdit.value && selected.value) {
      await axios.put(`/templates/${selected.value.id}`, form.value)
    } else {
      await axios.post('/templates', form.value)
    }

    modalAdd.value = false
    modalEdit.value = false
    await loadTemplates()
  } catch (e) {
    console.error(e)
    alert('Save failed')
  }
}

// DELETE TEMPLATE
async function remove(id) {
  if (!confirm("Delete this template?")) return
  try {
    await axios.delete(`/templates/${id}`)
    await loadTemplates()
  } catch (e) {
    console.error(e)
    alert('Delete failed')
  }
}

// SEND template
async function openSend(item) {
  selected.value = item
  sendForm.value = {
    to: '',
    language: item.language || 'id',
    componentsJson: ''
  }
  modalSend.value = true
}

/**
 * Send the template.
 * componentsJson is optional - if provided must be valid JSON (array) that follows Meta components structure.
 */
async function sendTemplate() {
  if (!selected.value) return
  const id = selected.value.id
  const payload = {
    to: sendForm.value.to,
    language: sendForm.value.language || selected.value.language
  }

  // parse components JSON if provided
  if (sendForm.value.componentsJson && sendForm.value.componentsJson.trim().length) {
    try {
      payload.components = JSON.parse(sendForm.value.componentsJson)
    } catch (e) {
      alert('Invalid components JSON')
      return
    }
  }

  try {
    loading.value = true
    const res = await axios.post(`/templates/${id}/send`, payload)
    modalSend.value = false
    // show result basic
    if (res.data?.sent) {
      alert('Template sent successfully')
    } else if (res.data?.warning) {
      alert(`Warning: ${res.data.warning}`)
    } else {
      alert('Sent (response received). Check server logs for details.')
    }
    await loadTemplates()
  } catch (e) {
    console.error(e)
    alert('Send failed: ' + (e.response?.data?.error ?? e.message))
  } finally {
    loading.value = false
  }
}

// ====================== Helpers ======================

// Badge color
const statusColor = (status) => {
  if (status === 'approved') return 'green'
  if (status === 'submitted') return 'blue'
  if (status === 'draft') return 'grey'
  if (status === 'rejected') return 'red'
  return 'orange'
}

// Open Preview
function openPreview(item) {
  selected.value = item
  modalView.value = true
}

// Open Edit
function openEdit(item) {
  selected.value = item
  form.value = { ...item }
  modalEdit.value = true
}

// Auto load when page opened
onMounted(loadTemplates)
</script>

<template>
  <Head title="Templates" />

  <AdminLayout>
    <template #title>Templates</template>

    <v-card elevation="2" class="pa-4">

      <!-- Header -->
      <v-row class="mb-4" align="center">
        <v-col>
          <h3 class="text-h6 font-weight-bold">WhatsApp Message Templates</h3>
          <p class="text-body-2 text-grey-darken-1">
            Kelola template pesan resmi WhatsApp.
          </p>
        </v-col>

        <v-col cols="auto" class="d-flex gap-2">
          <v-btn color="primary" prepend-icon="mdi-plus" @click="modalAdd = true">NEW TEMPLATE</v-btn>
        </v-col>
      </v-row>

      <!-- Table -->
      <v-table density="comfortable" hover>
        <thead>
          <tr>
            <th>Name</th>
            <th>Category</th>
            <th>Language</th>
            <th>Status</th>
            <th>Last Updated</th>
            <th>Action</th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="t in templates" :key="t.id">
            <td>{{ t.name }}</td>
            <td>{{ t.category }}</td>
            <td>{{ t.language }}</td>

            <td>
              <v-chip :color="statusColor(t.status)" size="small" dark>{{ t.status }}</v-chip>
            </td>

            <td>{{ t.updated_at }}</td>

            <td class="d-flex gap-1">
              <v-btn icon size="small" @click="openPreview(t)">
                <v-icon>mdi-eye</v-icon>
              </v-btn>

              <v-btn icon size="small" @click="openEdit(t)">
                <v-icon>mdi-pencil</v-icon>
              </v-btn>

              <v-btn icon size="small" color="blue" @click="syncTemplate(t.id)">
                <v-icon>mdi-sync</v-icon>
              </v-btn>

              <v-btn icon size="small" color="teal" @click="openSend(t)" title="Send template">
                <v-icon>mdi-send</v-icon>
              </v-btn>

              <v-btn icon size="small" color="red" @click="remove(t.id)">
                <v-icon>mdi-delete</v-icon>
              </v-btn>
            </td>
          </tr>

          <tr v-if="templates.length === 0">
            <td colspan="6" class="text-center pa-4">
              Belum ada template. Klik <b>NEW TEMPLATE</b>.
            </td>
          </tr>
        </tbody>
      </v-table>

    </v-card>

    <!-- ==================== MODAL ADD ==================== -->
    <v-dialog v-model="modalAdd" width="520">
      <v-card class="pa-4">
        <h3 class="text-h6 mb-4 font-weight-bold">Add New Template</h3>

        <v-text-field v-model="form.name" label="Template Name" class="mb-3" />
        <v-select v-model="form.category" :items="['Utility','Marketing','Authentication']" label="Category" class="mb-3" />
        <v-select v-model="form.language" :items="['id','en']" label="Language" class="mb-3" />
        <v-textarea v-model="form.body" label="Body Message" rows="4" variant="outlined" />

        <div class="d-flex justify-end mt-4 gap-2">
          <v-btn variant="text" @click="modalAdd = false">Cancel</v-btn>
          <v-btn color="primary" @click="save">Save</v-btn>
        </div>
      </v-card>
    </v-dialog>

    <!-- ==================== MODAL EDIT ==================== -->
    <v-dialog v-model="modalEdit" width="520">
      <v-card class="pa-4">
        <h3 class="text-h6 mb-4 font-weight-bold">Edit Template</h3>

        <v-text-field v-model="form.name" label="Template Name" class="mb-3" />
        <v-select v-model="form.category" :items="['Utility','Marketing','Authentication']" label="Category" class="mb-3" />
        <v-select v-model="form.language" :items="['id','en']" label="Language" class="mb-3" />
        <v-textarea v-model="form.body" label="Body Message" rows="4" variant="outlined" />

        <div class="d-flex justify-end mt-4 gap-2">
          <v-btn variant="text" @click="modalEdit = false">Cancel</v-btn>
          <v-btn color="primary" @click="save">Update</v-btn>
        </div>
      </v-card>
    </v-dialog>

    <!-- ==================== MODAL PREVIEW ==================== -->
    <v-dialog v-model="modalView" width="600">
      <v-card class="pa-4">
        <h3 class="text-h6 mb-4 font-weight-bold">Template Preview</h3>

        <p><strong>Name:</strong> {{ selected?.name }}</p>
        <p><strong>Category:</strong> {{ selected?.category }}</p>
        <p><strong>Status:</strong>
          <v-chip :color="statusColor(selected?.status)" size="small" dark>
            {{ selected?.status }}
          </v-chip>
        </p>

        <v-sheet elevation="1" class="pa-4" style="border-radius:12px; background:#f4f7fb;">
          <div style="background:white; padding:12px 16px; border-radius:12px;">
            <p v-html="selected?.body"></p>
          </div>
        </v-sheet>

        <!-- Workflow actions -->
        <div class="d-flex justify-end mt-4 gap-2">
          <v-btn
            v-if="selected?.status === 'draft'"
            color="blue"
            @click="submitForApproval(selected.id)"
          >
            Submit for Approval
          </v-btn>

          <v-btn
            v-if="selected?.status === 'submitted'"
            color="green"
            @click="approveTemplate(selected.id)"
          >
            Approve
          </v-btn>

          <v-btn
            v-if="selected?.status === 'submitted'"
            color="red"
            @click="rejectTemplate(selected.id)"
          >
            Reject
          </v-btn>

          <v-btn variant="text" @click="modalView = false">Close</v-btn>
        </div>
      </v-card>
    </v-dialog>

    <!-- ==================== MODAL SEND ==================== -->
    <v-dialog v-model="modalSend" width="520">
      <v-card class="pa-4">
        <h3 class="text-h6 mb-4 font-weight-bold">Send Template</h3>

        <p v-if="selected"><strong>Template:</strong> {{ selected.name }}</p>

        <v-text-field v-model="sendForm.to" label="Phone number (e.g. 628123...)" class="mb-3" />
        <v-select v-model="sendForm.language" :items="['id','en']" label="Language (override)" class="mb-3" />

        <v-textarea
          v-model="sendForm.componentsJson"
          label="Optional components JSON (advanced)"
          hint='Example: [{"type":"body","parameters":[{"type":"text","text":"Alice"}]}]'
          rows="4"
          class="mb-3"
        />

        <div class="d-flex justify-end mt-4 gap-2">
          <v-btn variant="text" @click="modalSend = false">Cancel</v-btn>
          <v-btn color="primary" @click="sendTemplate">Send</v-btn>
        </div>
      </v-card>
    </v-dialog>

  </AdminLayout>
</template>

<style scoped>
.hover-card:hover {
  background-color: rgba(18, 131, 218, 0.07);
}
</style>
