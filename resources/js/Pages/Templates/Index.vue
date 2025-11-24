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
const selected = ref(null)

// Form Template
const form = ref({
  name: '',
  category: '',
  language: '',
  body: ''
})

// ====================== API Calls ======================

// LOAD Templates
async function loadTemplates() {
  loading.value = true
  try {
    const res = await axios.get('/templates/list')
    templates.value = res.data
  } catch (e) {
    console.error(e)
  }
  loading.value = false
}

// SYNC to Meta
async function syncTemplates() {
  loading.value = true
  try {
    await axios.post('/templates/sync')
    await loadTemplates()
  } catch (e) {
    console.error(e)
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
  }
}

// ====================== Helpers ======================

// Badge color
const statusColor = (status) => {
  if (status === 'approved') return 'green'
  if (status === 'pending') return 'orange'
  return 'red'
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
            Kelola template pesan resmi yang disetujui oleh WhatsApp.
          </p>
        </v-col>

        <v-col cols="auto" class="d-flex gap-2">
          <v-btn :loading="loading" variant="outlined" prepend-icon="mdi-refresh" @click="syncTemplates">SYNC</v-btn>
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

            <td>
              <v-btn icon size="small" @click="openPreview(t)">
                <v-icon>mdi-eye</v-icon>
              </v-btn>

              <v-btn icon size="small" @click="openEdit(t)">
                <v-icon>mdi-pencil</v-icon>
              </v-btn>

              <v-btn icon size="small" color="red" @click="remove(t.id)">
                <v-icon>mdi-delete</v-icon>
              </v-btn>
            </td>
          </tr>

          <tr v-if="templates.length === 0">
            <td colspan="6" class="text-center pa-4">
              Belum ada template. Klik <b>SYNC</b> atau <b>NEW TEMPLATE.</b>
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
    <v-dialog v-model="modalView" width="420">
      <v-card class="pa-4">
        <h3 class="text-h6 mb-4 font-weight-bold">Template Preview</h3>

        <v-sheet elevation="1" class="pa-4" style="border-radius:12px; background:#f4f7fb;">
          <div style="background:white; padding:12px 16px; border-radius:12px; display:inline-block;">
            <p>{{ selected?.body }}</p>
          </div>
        </v-sheet>

        <div class="d-flex justify-end mt-4">
          <v-btn variant="text" @click="modalView = false">Close</v-btn>
        </div>
      </v-card>
    </v-dialog>

  </AdminLayout>
</template>
