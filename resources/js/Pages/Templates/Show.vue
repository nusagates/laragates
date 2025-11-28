<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, usePage } from '@inertiajs/vue3'
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { useRouter } from '@inertiajs/vue3'

const router = useRouter()
const page = usePage()
const template = ref(page.props.template ?? null) // Inertia prop when navigated
const versions = ref(page.props.versions ?? [])
const notes = ref(page.props.notes ?? [])
const me = page.props.auth?.user ?? null

// local reactive (copy of template for edits)
const draft = ref({
  header: template.value?.header ?? '',
  body: template.value?.body ?? '',
  footer: template.value?.footer ?? '',
  buttons: template.value?.buttons ?? []
})

// variable inputs map { "1": "Alice" }
const vars = ref({})

// extract variables {1} {2}
function extractVars(text) {
  if (!text) return []
  const m = text.match(/\{(\d+)\}/g)
  if (!m) return []
  const set = [...new Set(m.map(x => x.replace(/[{}]/g,'')))]
  return set.sort((a,b)=>Number(a)-Number(b))
}
const allVars = computed(() => {
  const s = new Set([
    ...extractVars(draft.value.header),
    ...extractVars(draft.value.body),
    ...extractVars(draft.value.footer)
  ])
  return Array.from(s)
})

// replace variables in preview
function replaceVars(text) {
  if (!text) return ''
  let out = text
  allVars.value.forEach(v => {
    const key = `{${v}}`
    out = out.replaceAll(key, vars.value[v] ?? key)
  })
  return out
}

const previewHeader = computed(() => replaceVars(draft.value.header))
const previewBody   = computed(() => replaceVars(draft.value.body))
const previewFooter = computed(() => replaceVars(draft.value.footer))

// UI state
const saving = ref(false)
const loading = ref(false)
const noteText = ref('')

// fetch fresh data (versions & notes)
async function refresh() {
  if (!template.value) return
  loading.value = true
  try {
    const res = await axios.get(`/templates/${template.value.id}`)
    // controller returns { template, versions, notes }
    template.value = res.data.template
    draft.value = {
      header: template.value.header,
      body: template.value.body,
      footer: template.value.footer,
      buttons: template.value.buttons ?? []
    }
    versions.value = res.data.versions ?? []
    notes.value = res.data.notes ?? []
  } catch (e) {
    console.error(e)
    alert('Fetch failed')
  } finally {
    loading.value = false
  }
}

// create new version (save snapshot)
async function createVersion() {
  if (!template.value) return
  saving.value = true
  try {
    await axios.post(`/templates/${template.value.id}/versions`, {
      header: draft.value.header,
      body: draft.value.body,
      footer: draft.value.footer,
      buttons: draft.value.buttons
    })
    await refresh()
    alert('Version saved')
  } catch (e) {
    console.error(e)
    alert('Save version failed')
  } finally {
    saving.value = false
  }
}

// revert to version
async function revertTo(versionId) {
  if (!confirm('Revert template to this version?')) return
  try {
    await axios.post(`/templates/${template.value.id}/versions/${versionId}/revert`)
    await refresh()
    alert('Reverted')
  } catch (e) {
    console.error(e)
    alert('Revert failed')
  }
}

// submit for approval (workflow)
async function submitForApproval() {
  if (!confirm('Submit template for approval?')) return
  try {
    await axios.post(`/templates/${template.value.id}/submit`)
    await refresh()
    alert('Submitted')
  } catch (e) {
    console.error(e)
    alert('Submit failed')
  }
}

// approve (superadmin)
async function approveTemplate() {
  const reason = prompt('Approval note (optional):')
  try {
    await axios.post(`/templates/${template.value.id}/approve`, { note: reason ?? '' })
    await refresh()
    alert('Approved')
  } catch (e) {
    console.error(e)
    alert('Approve failed')
  }
}

// reject with note
async function rejectTemplate() {
  const reason = prompt('Reason for rejection:')
  if (!reason) return
  try {
    await axios.post(`/templates/${template.value.id}/reject`, { reason })
    await refresh()
    alert('Rejected')
  } catch (e) {
    console.error(e)
    alert('Reject failed')
  }
}

// add approval note (any user)
async function addNote() {
  if (!noteText.value || !template.value) return
  try {
    await axios.post(`/templates/${template.value.id}/notes`, { note: noteText.value })
    noteText.value = ''
    await refresh()
  } catch (e) {
    console.error(e)
    alert('Add note failed')
  }
}

onMounted(() => {
  // set initial vars empty
  allVars.value.forEach(v => { vars.value[v] = '' })
})
</script>

<template>
  <Head title="Template Detail" />

  <AdminLayout>
    <template #title>Template Preview</template>

    <v-row>
      <!-- left: preview + variables -->
      <v-col cols="12" md="6">
        <v-card class="pa-4" elevation="2">
          <div class="d-flex justify-space-between">
            <div>
              <h3 class="text-h6 mb-1">{{ template?.name }}</h3>
              <div class="text-body-2 text-grey-darken-1">{{ template?.category }} • {{ template?.language }}</div>
            </div>

            <div class="d-flex gap-2">
              <v-btn small variant="outlined" @click="createVersion">Save Version</v-btn>
              <v-btn small color="blue" @click="refresh">Refresh</v-btn>
            </div>
          </div>

          <v-divider class="my-4" />

          <h4 class="text-subtitle-2 mb-2">Variables</h4>
          <div v-if="allVars.length === 0" class="text-caption mb-3">No variables detected.</div>
          <v-row v-else>
            <v-col cols="12" md="6" v-for="v in allVars" :key="v">
              <v-text-field v-model="vars[v]" :label="`Value for {${v}}`" />
            </v-col>
          </v-row>

          <h4 class="text-subtitle-2 mt-4 mb-2">WhatsApp Preview</h4>

          <v-sheet style="background:#ece5dd; border-radius:12px; padding:18px;">
            <div style="display:flex; gap:12px;">
              <div style="max-width:100%;">
                <div style="background:#dcf8c6; padding:14px; border-radius:12px; max-width:100%;">
                  <div v-if="previewHeader" style="font-weight:600; margin-bottom:6px;">{{ previewHeader }}</div>
                  <div style="white-space:pre-line;">{{ previewBody }}</div>
                  <div v-if="previewFooter" style="margin-top:8px; opacity:0.8; font-size:13px;">{{ previewFooter }}</div>

                  <div v-if="draft.buttons && draft.buttons.length" style="margin-top:10px;">
                    <v-chip v-for="(b,i) in draft.buttons" :key="i" class="ma-1" color="primary" small label>{{ b.text || 'Button' }}</v-chip>
                  </div>
                </div>
              </div>
            </div>
          </v-sheet>

        </v-card>
      </v-col>

      <!-- right: versions & notes & actions -->
      <v-col cols="12" md="6">
        <v-card class="pa-4" elevation="2">
          <h4 class="text-subtitle-1 mb-2">Versions</h4>
          <div v-if="versions.length === 0" class="text-caption mb-3">No versions yet.</div>

          <v-list two-line>
            <v-list-item v-for="v in versions" :key="v.id">
              <v-list-item-content>
                <v-list-item-title>{{ v.version_label || `v${v.id}` }}</v-list-item-title>
                <v-list-item-subtitle>{{ new Date(v.created_at).toLocaleString() }}</v-list-item-subtitle>
              </v-list-item-content>

              <v-list-item-action>
                <v-btn icon small @click="revertTo(v.id)" title="Revert to this version">
                  <v-icon>mdi-restore</v-icon>
                </v-btn>
                <v-btn icon small @click="() => { draft.header=v.header; draft.body=v.body; draft.footer=v.footer; draft.buttons=v.buttons || []; } " title="Load into editor">
                  <v-icon>mdi-download</v-icon>
                </v-btn>
              </v-list-item-action>
            </v-list-item>
          </v-list>

          <v-divider class="my-4" />

          <h4 class="text-subtitle-1 mb-2">Approval Notes</h4>

          <div v-if="notes.length === 0" class="text-caption mb-3">No notes yet.</div>

          <v-list dense>
            <v-list-item v-for="n in notes" :key="n.id">
              <v-list-item-content>
                <v-list-item-title class="text-caption">{{ n.note }}</v-list-item-title>
                <v-list-item-subtitle class="text-caption">{{ n.user_name ?? 'System' }} • {{ new Date(n.created_at).toLocaleString() }}</v-list-item-subtitle>
              </v-list-item-content>
            </v-list-item>
          </v-list>

          <v-textarea v-model="noteText" label="Add note" rows="2" class="mt-3" />
          <div class="d-flex justify-end gap-2 mt-2">
            <v-btn variant="text" @click="noteText=''">Cancel</v-btn>
            <v-btn color="primary" @click="addNote">Add Note</v-btn>
          </div>

          <v-divider class="my-4" />

          <div class="d-flex justify-space-between">
            <div>
              <v-chip small color="grey" label>{{ template?.status }}</v-chip>
            </div>

            <div class="d-flex gap-2">
              <v-btn color="blue" @click="submitForApproval" v-if="template?.status === 'draft'">Submit</v-btn>
              <v-btn color="green" @click="approveTemplate" v-if="template?.status === 'submitted' && me?.role === 'superadmin'">Approve</v-btn>
              <v-btn color="red" @click="rejectTemplate" v-if="template?.status === 'submitted' && me?.role === 'superadmin'">Reject</v-btn>
            </div>
          </div>

        </v-card>
      </v-col>
    </v-row>

  </AdminLayout>
</template>

<style scoped>
/* small styling tweaks */
</style>
