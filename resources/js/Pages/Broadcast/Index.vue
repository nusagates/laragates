<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import axios from 'axios'
import { useToast } from 'vue-toastification'

const page = usePage()
const toast = useToast()

/* ================= PROPS ================= */
const templates = computed(() => page.props.templates || [])
const history   = computed(() => page.props.history || [])

/* ================= FORM (LOGIC ASLI) ================= */
const form = ref({
  name: '',
  template_id: null,
  audience_type: 'upload',
  schedule_type: 'now',
  send_at: null,
  csv_file: null,
})

const loading = ref(false)
const errors  = ref({})

/* ================= UI STATE ================= */
const csvFileName = ref('')
const dragActive = ref(false)
const uploadProgress = ref(0)
const uploading = ref(false)

/* ================= COMPUTED ================= */
const selectedTemplate = computed(() =>
  templates.value.find(t => t.id === form.value.template_id) || null
)

function fieldError(name) {
  return errors.value[name]?.[0] || ''
}

/* ================= CSV ================= */
function onCsvChange(e) {
  const f = e.target.files?.[0]
  if (f) handleFile(f)
}

function onDrop(e) {
  dragActive.value = false
  const f = e.dataTransfer.files?.[0]
  if (f) handleFile(f)
}

function handleFile(file) {
  form.value.csv_file = file
  csvFileName.value = file.name
}

/* ================= PREVIEW ================= */
function highlightVars(text) {
  if (!text) return ''
  return text
    .replace(/\{\{(\d+)\}\}/g, `<span class="var-chip">var$1</span>`)
    .replace(/\n/g, '<br/>')
}

/* ================= SUBMIT ================= */
async function startBroadcast() {
  loading.value = true
  errors.value = {}
  uploading.value = true

  try {
    const fd = new FormData()
    fd.append('name', form.value.name)
    fd.append('template_id', form.value.template_id)
    fd.append('audience_type', form.value.audience_type)
    fd.append('schedule_type', form.value.schedule_type)

    if (form.value.send_at) {
      fd.append('send_at', form.value.send_at)
    }

    if (form.value.csv_file) {
      fd.append('csv_file', form.value.csv_file)
    }

    const res = await axios.post(route('broadcast.store'), fd, {
      onUploadProgress(e) {
        if (e.lengthComputable) {
          uploadProgress.value = Math.round((e.loaded * 100) / e.total)
        }
      }
    })

    const campaign = res.data.campaign
    const targetCount = campaign?.total_targets || 0

    toast.success(`Broadcast campaign "${form.value.name}" berhasil dibuat!${targetCount > 0 ? ` (${targetCount} targets)` : ''}`)

    router.visit(route('broadcast.report.show', { campaign: campaign.id }))

  } catch (e) {
    console.error(e)
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors
      toast.error('Terdapat kesalahan pada form. Mohon periksa kembali.')
    } else {
      const errorMsg = e.response?.data?.message || 'Gagal membuat broadcast campaign'
      toast.error(errorMsg)
    }
  } finally {
    loading.value = false
    uploading.value = false
    uploadProgress.value = 0
  }
}

function detailReport(campaign) {
  router.visit(route('broadcast.report.show', { campaign: campaign.id }))
}
</script>

<template>
  <Head title="Broadcast" />

  <AdminLayout>
    <template #title>Broadcast</template>

    <div class="broadcast-dark">

      <!-- HEADER -->
      <div class="header-card">
        <div>
          <h3>Create Broadcast Campaign</h3>
          <p>Kirim pesan massal menggunakan template WhatsApp</p>
        </div>

        <v-btn
          color="primary"
          prepend-icon="mdi-chart-box"
          @click="router.visit(route('broadcast.reports'))"

        >
          View Report
        </v-btn>
      </div>

      <div class="grid">

        <!-- FORM -->
        <v-card class="card pa-4">
          <v-text-field
            v-model="form.name"
            label="Campaign Name"
            :error-messages="fieldError('name')"
          />

          <v-select
            v-model="form.template_id"
            :items="templates"
            item-title="name"
            item-value="id"
            label="Template Message"
            class="mt-3"
          />

          <h4 class="section">Audience</h4>
          <v-radio-group v-model="form.audience_type" inline>
            <v-radio label="Upload CSV" value="upload" />
          </v-radio-group>

          <!-- CSV -->
          <div
            class="dropzone"
            :class="{ active: dragActive }"
            @dragover.prevent
            @drop.prevent="onDrop"
          >
            <v-icon size="32">mdi-upload</v-icon>
            <p>Drag CSV atau klik</p>

            <div class="dropzone-actions">
              <v-btn
                size="small"
                variant="tonal"
                @click="$refs.csv.click()"
              >
                Choose File
              </v-btn>

              <v-btn
                size="small"
                variant="text"
                color="primary"
                prepend-icon="mdi-download"
                :href="route('broadcast.download-sample-csv')"
                download
              >
                Download Sample
              </v-btn>
            </div>

            <input
              ref="csv"
              type="file"
              hidden
              accept=".csv"
              @change="onCsvChange"
            />

            <small v-if="csvFileName" class="filename-display">{{ csvFileName }}</small>
          </div>

          <h4 class="section">Schedule</h4>
          <v-radio-group v-model="form.schedule_type" inline>
            <v-radio label="Send Now" value="now" />
            <v-radio label="Later" value="later" />
          </v-radio-group>

          <v-text-field
            v-if="form.schedule_type === 'later'"
            v-model="form.send_at"
            type="datetime-local"
          />

          <div class="actions">
            <v-btn variant="text" @click="router.visit(route('broadcast'))">
              Cancel
            </v-btn>
            <v-btn color="primary" :loading="loading" @click="startBroadcast">
              START BROADCAST
            </v-btn>
          </div>

          <div v-if="uploading" class="mt-3">
            Uploading {{ uploadProgress }}%
          </div>
        </v-card>

        <!-- SIDEBAR -->
        <div>
          <v-card class="card pa-4 mb-4">
            <h4>Template Preview</h4>

            <div
              v-if="selectedTemplate"
              class="preview"
              v-html="highlightVars(selectedTemplate.body)"
            />

            <p v-else class="muted">Pilih template</p>
          </v-card>

          <v-card class="card pa-4">
            <h4>History</h4>
            <v-table density="compact">
              <tbody>
                <tr v-for="h in history" :key="h.id" @click="detailReport(h)">
                  <td>{{ h.name }}</td>
                  <td>{{ h.sent }}/{{ h.failed }}</td>
                </tr>
              </tbody>
            </v-table>
          </v-card>
        </div>

      </div>
    </div>
  </AdminLayout>
</template>

<style scoped>
/* =========================================================
   DARK WABA – BROADCAST (FINAL FIXED)
========================================================= */

:global(:root) {
  --bg-main: #020617;
  --bg-soft: #0f172a;
  --border-soft: rgba(255,255,255,.06);

  --text-main: #e5e7eb;
  --text-muted: #94a3b8;

  --blue-strong: #3b82f6;
  --blue-soft: rgba(59,130,246,.12);
}

/* wrapper */
.broadcast-dark {
  color: var(--text-main);
}

/* header */
.header-card {
  background: linear-gradient(180deg, var(--bg-main), var(--bg-soft));
  padding: 20px;
  border-radius: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.header-card p {
  color: var(--text-muted);
}

/* grid */
.grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 20px;
}

/* card */
.card {
  background: linear-gradient(180deg, var(--bg-main), var(--bg-soft));
  border: 1px solid var(--border-soft);
  border-radius: 16px;
  color: var(--text-main);
}

/* section */
.section {
  margin-top: 20px;
  font-weight: 600;
}

/* dropzone */
.dropzone {
  border: 2px dashed #475569;
  border-radius: 14px;
  padding: 22px;
  text-align: center;
  margin-top: 12px;
  background: rgba(255,255,255,.03);
}
.dropzone:hover {
  border-color: var(--blue-strong);
  background: var(--blue-soft);
}
.dropzone-actions {
  display: flex;
  gap: 8px;
  justify-content: center;
  margin-top: 12px;
}
.filename-display {
  display: block;
  margin-top: 12px;
  color: var(--blue-strong);
}

/* preview */
.preview {
  background: rgba(255,255,255,.05);
  padding: 14px;
  border-radius: 12px;
  margin-top: 10px;
  line-height: 1.5;
}
.var-chip {
  background: rgba(250,204,21,.2);
  color: #fde68a;
  padding: 2px 6px;
  border-radius: 6px;
}

/* actions */
.actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 24px;
}

/* muted */
.muted {
  color: var(--text-muted);
}

/* table force dark */
:deep(.v-table),
:deep(table) {
  background: transparent !important;
}
:deep(tbody td) {
  color: var(--text-main) !important;
  border-bottom: 1px solid var(--border-soft);
}
:deep(tbody tr) {
  cursor: pointer;
  transition: all 0.2s ease;
}
:deep(tbody tr:hover) {
  background-color: rgba(59, 130, 246, 0.15) !important;
  border-left: 3px solid var(--blue-strong);
  padding-left: 8px;
}

/* VUETIFY TEXT FIX */
:deep(.v-field__input input),
:deep(.v-field__input textarea),
:deep(.v-select__selection-text) {
  color: var(--text-main) !important;
}
:deep(.v-label) {
  color: var(--text-muted) !important;
}
:deep(.v-field--active .v-label) {
  color: var(--blue-strong) !important;
}
</style>
