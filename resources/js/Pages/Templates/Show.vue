<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, usePage, router } from '@inertiajs/vue3'
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

// variable values map
const vars = ref({})

// extract variables
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

// replace preview vars
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

// fetch latest template
async function refresh() {
  try {
    const res = await axios.get(`/templates/${template.value.id}`)
    template.value  = res.data.template
    versions.value  = res.data.versions ?? []
    notes.value     = res.data.notes ?? []

    draft.value = {
      header: template.value.header,
      body: template.value.body,
      footer: template.value.footer,
      buttons: template.value.buttons ?? []
    }
  } catch (e) {
    console.error(e)
    alert('Failed to refresh')
  }
}

// SAVE VERSION
async function createVersion() {
  try {
    await axios.post(`/templates/${template.value.id}/versions`, draft.value)
    await refresh()
    alert("Version saved")
  } catch (e) {
    console.error(e)
    alert('Failed to save version')
  }
}

// REVERT VERSION
async function revertTo(versionId) {
  if (!confirm("Revert to this version?")) return
  await axios.post(`/templates/${template.value.id}/versions/${versionId}/revert`)
  await refresh()
}

// SUBMIT FOR APPROVAL
async function submitForApproval() {
  if (!confirm("Submit for approval?")) return
  await axios.post(`/templates/${template.value.id}/submit`)
  await refresh()
}

// APPROVE (superadmin)
async function approveTemplate() {
  const note = prompt("Approval note:")
  await axios.post(`/templates/${template.value.id}/approve`, { note })
  await refresh()
}

// REJECT
async function rejectTemplate() {
  const r = prompt("Reason for rejection:")
  if (!r) return
  await axios.post(`/templates/${template.value.id}/reject`, { reason: r })
  await refresh()
}

// ADD NOTE
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
