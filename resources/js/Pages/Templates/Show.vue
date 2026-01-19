<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, usePage } from '@inertiajs/vue3'
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

// load props
const page = usePage()
const template = ref(page.props.template ?? null)
const versions = ref(page.props.versions ?? [])
const notes = ref(page.props.notes ?? [])
const me = page.props.auth?.user ?? null

// draft for preview
const draft = ref({
  header: template.value?.header ?? '',
  body: template.value?.body ?? '',
  footer: template.value?.footer ?? '',
  buttons: template.value?.buttons ?? []
})

// variable values
const vars = ref({})

// ===================== VAR PARSER =====================
function extractVars(text) {
  if (!text) return []
  const m = text.match(/\{(\d+)\}/g)
  if (!m) return []
  return [...new Set(m.map(x => x.replace(/[{}]/g, '')))]
}

const allVars = computed(() => {
  const s = new Set([
    ...extractVars(draft.value.header),
    ...extractVars(draft.value.body),
    ...extractVars(draft.value.footer),
  ])
  return Array.from(s)
})

function replaceVars(text) {
  if (!text) return ''
  let out = text
  allVars.value.forEach(v => {
    const k = `{${v}}`
    out = out.replaceAll(k, vars.value[v] ?? k)
  })
  return out
}

const previewHeader = computed(() => replaceVars(draft.value.header))
const previewBody   = computed(() => replaceVars(draft.value.body))
const previewFooter = computed(() => replaceVars(draft.value.footer))

// ===================== API =====================
async function refresh() {
  try {
    const res = await axios.get(`/templates/${template.value.id}`)
    template.value = res.data.template
    versions.value = res.data.versions ?? []
    notes.value    = res.data.notes ?? []

    draft.value = {
      header: template.value.header,
      body: template.value.body,
      footer: template.value.footer,
      buttons: template.value.buttons ?? []
    }
  } catch {
    alert('Failed to refresh')
  }
}

async function createVersion() {
  await axios.post(`/templates/${template.value.id}/versions`, draft.value)
  await refresh()
  alert('Version saved')
}

async function revertTo(versionId) {
  if (!confirm('Revert to this version?')) return
  await axios.post(`/templates/${template.value.id}/versions/${versionId}/revert`)
  await refresh()
}

async function submitForApproval() {
  if (!confirm('Submit for approval?')) return
  await axios.post(`/templates/${template.value.id}/submit`)
  await refresh()
}

async function approveTemplate() {
  const note = prompt('Approval note:')
  await axios.post(`/templates/${template.value.id}/approve`, { note })
  await refresh()
}

async function rejectTemplate() {
  const r = prompt('Reason for rejection:')
  if (!r) return
  await axios.post(`/templates/${template.value.id}/reject`, { reason: r })
  await refresh()
}

const noteText = ref('')
async function addNote() {
  if (!noteText.value) return
  await axios.post(`/templates/${template.value.id}/notes`, { note: noteText.value })
  noteText.value = ''
  await refresh()
}

onMounted(() => {
  allVars.value.forEach(v => vars.value[v] = '')
})
</script>

<template>
  <Head title="Template Detail" />

  <AdminLayout>
    <template #title>
      Template Detail
    </template>

    <div class="template-detail-dark">
      <v-row>

        <!-- LEFT -->
        <v-col cols="12" md="6">
          <v-card class="dark-card pa-5 mb-4">
            <h3 class="text-h6 mb-2">{{ template?.name }}</h3>
            <p class="text-muted">
              {{ template?.category }} · {{ template?.language }}
            </p>

            <v-chip class="mt-2" color="blue-darken-2">
              {{ template?.status }}
            </v-chip>

            <div class="d-flex gap-2 mt-4">
              <v-btn color="primary" @click="createVersion">
                Save Version
              </v-btn>

              <v-btn
                v-if="template?.status === 'draft'"
                color="blue"
                @click="submitForApproval"
              >
                Submit
              </v-btn>

              <v-btn
                v-if="me?.role === 'superadmin' && template?.status === 'submitted'"
                color="green"
                @click="approveTemplate"
              >
                Approve
              </v-btn>

              <v-btn
                v-if="template?.status === 'submitted'"
                color="red"
                @click="rejectTemplate"
              >
                Reject
              </v-btn>
            </div>
          </v-card>

          <!-- VARIABLES -->
          <v-card class="dark-card pa-5 mb-4">
            <h4 class="text-subtitle-1 mb-3">Variables</h4>

            <v-row>
              <v-col
                v-for="v in allVars"
                :key="v"
                cols="12"
                md="6"
              >
                <v-text-field
                  v-model="vars[v]"
                  :label="`Value for {${v}}`"
                  density="compact"
                />
              </v-col>
            </v-row>
          </v-card>

          <!-- VERSIONS -->
          <v-card class="dark-card pa-5">
            <h4 class="text-subtitle-1 mb-3">Versions</h4>

            <div
              v-for="v in versions"
              :key="v.id"
              class="version-row"
            >
              <div>
                <strong>#{{ v.id }}</strong>
                <span class="text-muted text-caption">
                  · {{ v.created_at }}
                </span>
              </div>

              <v-btn
                size="small"
                variant="text"
                @click="revertTo(v.id)"
              >
                Revert
              </v-btn>
            </div>
          </v-card>
        </v-col>

        <!-- RIGHT -->
        <v-col cols="12" md="6">
          <v-card class="dark-card pa-5 mb-4">
            <h4 class="text-subtitle-1 mb-3">Live Preview</h4>

            <div class="wa-preview">
              <div class="wa-bubble">
                <p v-if="previewHeader" class="wa-header">
                  {{ previewHeader }}
                </p>
                <p class="wa-body">{{ previewBody }}</p>
                <p v-if="previewFooter" class="wa-footer">
                  {{ previewFooter }}
                </p>
              </div>
            </div>
          </v-card>

          <!-- NOTES -->
          <v-card class="dark-card pa-5">
            <h4 class="text-subtitle-1 mb-3">Notes</h4>

            <div
              v-for="n in notes"
              :key="n.id"
              class="note-item"
            >
              <strong>{{ n.user?.name }}</strong>
              <p class="text-muted text-caption">{{ n.note }}</p>
            </div>

            <v-textarea
              v-model="noteText"
              label="Add note"
              rows="2"
            />

            <div class="d-flex justify-end mt-3">
              <v-btn color="primary" @click="addNote">
                Add Note
              </v-btn>
            </div>
          </v-card>
        </v-col>

      </v-row>
    </div>
  </AdminLayout>
</template>

<style scoped>
.template-detail-dark {
  color: #e5e7eb;
}

.dark-card {
  background: linear-gradient(180deg,#020617,#0f172a);
  border: 1px solid rgba(255,255,255,.06);
  border-radius: 16px;
}

.text-muted {
  color: #94a3b8;
}

.version-row {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px solid rgba(255,255,255,.06);
}

.wa-preview {
  background: rgba(255,255,255,.04);
  padding: 16px;
  border-radius: 16px;
}

.wa-bubble {
  background: #1f2937;
  padding: 14px 16px;
  border-radius: 14px;
  max-width: 90%;
}

.wa-header {
  font-weight: 600;
}

.wa-body {
  white-space: pre-line;
}

.wa-footer {
  font-size: 12px;
  color: #94a3b8;
}

.note-item {
  padding-bottom: 8px;
  margin-bottom: 8px;
  border-bottom: 1px solid rgba(255,255,255,.05);
}
</style>
