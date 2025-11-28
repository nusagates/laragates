<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import axios from 'axios'

const page = usePage()

// props from server
const templates = computed(() => page.props.templates || [])
const history   = computed(() => page.props.history || [])

// form state (keep original fields)
const form = ref({
  name: '',
  template_id: null,
  audience_type: 'csv',
  schedule_type: 'now',
  send_at: null,
  csv_file: null,
})

const loading = ref(false)
const errors  = ref({})

// premium UI state
const csvFileName = ref('')
const dragActive = ref(false)
const csvPreview = ref({ count: null, sample: [], errors: [] })
const uploadProgress = ref(0)
const uploading = ref(false)
const createdCampaignId = ref(null)

// helper: selected template
const selectedTemplate = computed(() =>
  templates.value.find(t => t.id === form.value.template_id) || null
)

function fieldError(name) {
  return errors.value[name]?.[0] || ''
}

// ---------------- CSV handling ----------------
function onCsvChange(e) {
  const file = e.target.files?.[0] ?? null
  if (!file) return
  handleFile(file)
}

function onDrop(e) {
  dragActive.value = false
  const file = e.dataTransfer.files?.[0] ?? null
  if (file) handleFile(file)
}

function onDragEnter() { dragActive.value = true }
function onDragLeave() { dragActive.value = false }

function handleFile(file) {
  csvPreview.value = { count: null, sample: [], errors: [] }
  form.value.csv_file = file
  csvFileName.value = file.name

  if (!file.name.toLowerCase().endsWith('.csv')) {
    csvPreview.value.errors.push('File harus berekstensi .csv')
    return
  }

  const reader = new FileReader()
  reader.onload = (ev) => {
    const txt = String(ev.target.result || '')
    const lines = txt.split(/\r\n|\n/).map(l => l.trim()).filter(l => l)

    let start = 0
    if (lines.length && /[a-zA-Z]/.test(lines[0])) start = 1

    const parsed = []
    const errs = []

    for (let i = start; i < Math.min(lines.length, 2000); i++) {
      const cols = lines[i].split(',').map(c => c.trim())

      if (cols.length === 1) {
        const phone = cols[0].replace(/\s+/g, '')
        if (/^\d{6,15}$/.test(phone)) parsed.push({ phone })
        else errs.push(`Baris ${i+1}: nomor tidak valid (${cols[0]})`)
      } else {
        const obj = { name: cols[0], phone: cols[1].replace(/\s+/g, '') }
        if (cols[2]) {
          try {
            obj.variables = JSON.parse(cols[2])
          } catch {
            obj.variables = []
            errs.push(`Baris ${i+1}: kolom variables bukan JSON valid`)
          }
        }
        if (/^\d{6,15}$/.test(obj.phone)) parsed.push(obj)
        else errs.push(`Baris ${i+1}: nomor tidak valid (${cols[1]})`)
      }
    }

    csvPreview.value.count = Math.max(0, lines.length - start)
    csvPreview.value.sample = parsed.slice(0, 6)
    csvPreview.value.errors = errs
  }

  reader.onerror = () => {
    csvPreview.value.errors.push('Tidak bisa membaca file CSV')
  }

  reader.readAsText(file)
}

// highlight variables like {{1}}
function highlightVars(text) {
  if (!text) return ''
  const esc = text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')

  return esc.replace(/\{\{(\d+)\}\}/g, (m, p1) =>
    `<span style="padding:2px 6px;background:#FFF7C6;color:#8A6D00;border-radius:6px;margin-right:4px">var${p1}</span>`
  ).replace(/\n/g, '<br/>')
}

// create campaign
async function startBroadcast() {
  loading.value = true
  errors.value = {}

  try {
    const data = new FormData()
    data.append('name', form.value.name)
    data.append('template_id', form.value.template_id || '')
    data.append('audience_type', form.value.audience_type)
    data.append('schedule_type', form.value.schedule_type)

    if (form.value.schedule_type === 'later' && form.value.send_at) {
      data.append('send_at', form.value.send_at)
    }

    const res = await axios.post(route('broadcast.store'), data)
    const campaign = res.data.campaign ?? res.data
    const id = campaign.id ?? campaign

    createdCampaignId.value = id

    // upload CSV if needed
    if (form.value.audience_type === 'csv' && form.value.csv_file) {
      await uploadTargets(id, form.value.csv_file)
    }

    // request approval
    await axios.post(route('broadcast.request-approval', { campaign: id }), {
      notes: 'Submitted from UI'
    }).catch(() => {})

    router.visit(route('broadcast'), { preserveScroll: true })

  } catch (err) {
    if (err.response?.status === 422) errors.value = err.response.data.errors
    else alert('Gagal membuat broadcast.')
  } finally {
    loading.value = false
  }
}

// upload targets with progress
async function uploadTargets(id, file) {
  uploading.value = true
  uploadProgress.value = 0

  const fd = new FormData()
  fd.append('file', file)

  try {
    await axios.post(
      `/broadcast/campaigns/${id}/upload-targets`,
      fd,
      {
        headers: { 'Content-Type': 'multipart/form-data' },
        onUploadProgress(e) {
          if (e.lengthComputable)
            uploadProgress.value = Math.round((e.loaded * 100) / e.total)
        }
      }
    )
  } catch (e) {
    errors.value.csv_file = [
      e.response?.data?.message ?? 'Upload targets gagal'
    ]
    throw e
  } finally {
    uploading.value = false
  }
}
</script>

<template>
  <Head title="Broadcast" />

  <AdminLayout>
    <template #title>Broadcast</template>

    <!-- 🔥 TOP BAR WITH REPORT BUTTON -->
    <div class="d-flex justify-space-between align-center mb-4 px-4">
      <h2 class="text-h5 font-weight-bold">Create Broadcast Campaign</h2>

      <v-btn
        color="indigo-darken-3"
        variant="tonal"
        prepend-icon="mdi-chart-box-outline"
        @click="router.visit(route('broadcast.report'))"
      >
        View Report
      </v-btn>
    </div>

    <div class="pa-4">
      <div class="d-flex">
        <!-- LEFT FORM -->
        <div style="flex:2; margin-right:20px;">
          <v-card elevation="2" class="pa-4" style="border-radius:12px;">
            
            <!-- Campaign Name -->
            <v-text-field
              v-model="form.name"
              label="Campaign Name"
              dense
              :error-messages="fieldError('name')"
              class="mb-4"
            />

            <!-- Template -->
            <v-select
              v-model="form.template_id"
              :items="templates"
              item-title="name"
              item-value="id"
              label="Template Message"
              dense
              :error-messages="fieldError('template_id')"
              class="mb-4"
            />

            <!-- Audience -->
            <h4 class="text-subtitle-2 mb-2">Audience</h4>
            <v-radio-group v-model="form.audience_type" inline>
              <v-radio label="All Customers (TODO)" value="all" />
              <v-radio label="Upload CSV" value="csv" />
            </v-radio-group>

            <!-- CSV -->
            <div v-if="form.audience_type === 'csv'" class="mt-3 mb-4">
              <p class="text-body-2 mb-1">
                Upload file CSV berisi nomor WhatsApp. Contoh:
                <code>6281234567890</code>
              </p>

              <div
                class="dropzone-box"
                :class="{ active: dragActive }"
                @dragenter.prevent="onDragEnter"
                @dragleave.prevent="onDragLeave"
                @dragover.prevent
                @drop.prevent="onDrop"
              >
                <div class="d-flex align-center" style="flex-direction:column; gap:12px;">
                  <v-icon size="36">mdi-upload</v-icon>
                  <div class="text-body-2">Drag & drop CSV atau klik tombol</div>

                  <v-btn small variant="tonal" @click="$refs.csvInput.click()">Choose File</v-btn>
                  <input ref="csvInput" type="file" class="d-none" accept=".csv" @change="onCsvChange" />

                  <div v-if="csvFileName" class="text-caption mt-1">
                    <strong>Selected:</strong> {{ csvFileName }}
                  </div>
                </div>
              </div>

              <!-- CSV Preview -->
              <div v-if="csvPreview.count !== null" class="mt-3">
                <div class="text-caption">Rows parsed: <strong>{{ csvPreview.count }}</strong></div>
                <div v-if="csvPreview.sample.length" class="mt-2" style="background:#fafafa;border:1px solid #eee;padding:8px;border-radius:8px;">
                  <div v-for="(r, idx) in csvPreview.sample" :key="idx" style="font-size:13px;">
                    {{ idx+1 }}. {{ JSON.stringify(r) }}
                  </div>
                </div>
                <div v-if="csvPreview.errors.length" class="mt-2 red--text text-caption">
                  <div v-for="(err, idx) in csvPreview.errors" :key="idx">{{ err }}</div>
                </div>
              </div>
            </div>

            <!-- Schedule -->
            <h4 class="text-subtitle-2 mt-3 mb-2">Schedule</h4>
            <v-radio-group v-model="form.schedule_type" inline>
              <v-radio label="Send Now" value="now" />
              <v-radio label="Schedule Later" value="later" />
            </v-radio-group>

            <v-text-field
              v-if="form.schedule_type === 'later'"
              v-model="form.send_at"
              type="datetime-local"
              label="Send At"
              dense
              class="mt-2"
              :error-messages="fieldError('send_at')"
            />

            <!-- Buttons -->
            <div class="d-flex justify-end mt-6" style="gap:10px;">
              <v-btn variant="text" @click="router.visit(route('broadcast'))">Cancel</v-btn>
              <v-btn color="primary" :loading="loading" @click="startBroadcast">START BROADCAST</v-btn>
            </div>

            <!-- Upload progress -->
            <div v-if="uploading" class="mt-4">
              <div style="height:8px;background:#eee;border-radius:6px;overflow:hidden;">
                <div :style="{ width: uploadProgress+'%', background:'#4caf50', height:'8px' }"></div>
              </div>
              <div class="text-caption mt-2">Uploading {{ uploadProgress }}%</div>
            </div>
          </v-card>
        </div>

        <!-- RIGHT SIDEBAR -->
        <div style="flex:1;">
          <v-card elevation="2" class="pa-4 mb-4" style="border-radius:12px;">
            <h4 class="text-subtitle-1 font-weight-bold mb-2">Selected Template Preview</h4>

            <v-sheet class="pa-3 mt-2" color="grey-lighten-4" style="border-radius:12px;">
              <div v-if="selectedTemplate">
                <strong>{{ selectedTemplate.name }}</strong>
                <div class="text-body-2 mt-2" v-html="highlightVars(selectedTemplate.body)"></div>
              </div>
              <div v-else class="text-body-2 text-grey-darken-1">
                Pilih template untuk melihat preview.
              </div>
            </v-sheet>
          </v-card>

          <v-card elevation="2" class="pa-4" style="border-radius:12px;">
            <h4 class="text-subtitle-1 font-weight-bold mb-2">History</h4>

            <v-table density="compact">
              <thead>
                <tr><th>Campaign</th><th>Sent</th><th>Date</th></tr>
              </thead>
              <tbody>
                <tr v-if="!history.length">
                  <td colspan="3" class="text-center">Belum ada riwayat broadcast.</td>
                </tr>
                <tr v-for="h in history" :key="h.id">
                  <td><strong>{{ h.name }}</strong></td>
                  <td>{{ h.sent }} / {{ h.failed }} failed</td>
                  <td>{{ h.date }}</td>
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
.dropzone-box {
  border: 2px dashed #9e9e9e;
  border-radius: 12px;
  padding: 28px;
  text-align: center;
  transition: all .15s;
  background: #fff;
}
.dropzone-box.active {
  border-color: #1976d2;
  background: #e8f3ff;
}
</style>
