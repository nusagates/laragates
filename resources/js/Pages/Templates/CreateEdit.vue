<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const isEdit = ref(false)

const page = usePage()
const incoming = page.props.template || null

// ======================= FORM =======================
const form = ref({
  name: '',
  category: '',
  language: 'id',
  header: '',
  body: '',
  footer: '',
  buttons: []
})

// Dynamic parameters {1}, {2}, ...
const params = ref({})

// ======================= LOAD IF EDIT =======================
onMounted(() => {
  if (incoming) {
    isEdit.value = true
    form.value = {
      name: incoming.name,
      category: incoming.category,
      language: incoming.language,
      header: incoming.header,
      body: incoming.body,
      footer: incoming.footer,
      buttons: incoming.buttons ?? []
    }
  }
})

// ======================= VARIABLE PARSER =======================
function extractVars(text) {
  const match = text?.match(/\{(\d+)\}/g)
  if (!match) return []
  return [...new Set(match.map(v => v.replace('{','').replace('}','')))]
}

const allVars = computed(() => {
  const set = new Set([
    ...extractVars(form.value.header),
    ...extractVars(form.value.body),
    ...extractVars(form.value.footer)
  ])
  return Array.from(set)
})

// ======================= LIVE PREVIEW =======================
function replaceVars(text) {
  if (!text) return ""
  let out = text

  allVars.value.forEach(v => {
    const key = `{${v}}`
    out = out.replaceAll(key, params.value[v] || key)
  })

  return out
}

const previewHeader = computed(() => replaceVars(form.value.header))
const previewBody   = computed(() => replaceVars(form.value.body))
const previewFooter = computed(() => replaceVars(form.value.footer))

// ======================= SAVE =======================
async function save() {
  try {
    if (isEdit.value && incoming.id) {
      await axios.put(`/templates/${incoming.id}`, form.value)
      alert("Template updated")
    } else {
      await axios.post(`/templates`, form.value)
      alert("Template created")
    }

    router.visit('/templates')
  } catch (e) {
    console.error(e)
    alert("Failed to save")
  }
}
</script>

<template>
  <Head :title="isEdit ? 'Edit Template' : 'New Template'" />

  <AdminLayout>
    <template #title>
      {{ isEdit ? 'Edit Template' : 'New Template' }}
    </template>

    <div class="template-form-dark">
      <v-row>

        <!-- LEFT : FORM -->
        <v-col cols="12" md="6">
          <v-card class="dark-card pa-5">

            <h3 class="text-h6 mb-4">
              {{ isEdit ? 'Edit Template' : 'Create New Template' }}
            </h3>

            <v-text-field v-model="form.name" label="Template Name" />
            <v-select
              v-model="form.category"
              label="Category"
              :items="['Utility','Marketing','Authentication']"
            />
            <v-select
              v-model="form.language"
              label="Language"
              :items="['id','en']"
            />

            <v-text-field
              v-model="form.header"
              label="Header (optional)"
            />

            <v-textarea
              v-model="form.body"
              label="Body Message"
              rows="5"
              hint="Gunakan {1}, {2} sebagai variable"
              persistent-hint
            />

            <v-text-field
              v-model="form.footer"
              label="Footer (optional)"
            />

            <v-textarea
              v-model="form.buttons"
              label="Buttons JSON (optional)"
              hint='Example: [{"type":"url","text":"Visit","url":"https://..."}]'
              rows="3"
              persistent-hint
            />

            <div class="d-flex justify-end gap-2 mt-5">
              <v-btn variant="text" @click="router.visit('/templates')">
                Cancel
              </v-btn>
              <v-btn color="primary" @click="save">
                {{ isEdit ? 'Update' : 'Save' }}
              </v-btn>
            </div>

          </v-card>
        </v-col>

        <!-- RIGHT : PREVIEW -->
        <v-col cols="12" md="6">
          <v-card class="dark-card pa-5">

            <h3 class="text-h6 mb-4">Live Preview</h3>

            <!-- VARIABLES -->
            <div v-if="allVars.length">
              <h4 class="text-subtitle-2 mb-2 text-muted">Variables</h4>

              <v-row>
                <v-col
                  cols="12"
                  md="6"
                  v-for="v in allVars"
                  :key="v"
                >
                  <v-text-field
                    v-model="params[v]"
                    :label="`Value for {${v}}`"
                    density="compact"
                  />
                </v-col>
              </v-row>
            </div>

            <!-- WHATSAPP PREVIEW -->
            <div class="wa-preview mt-4">
              <div class="wa-bubble">

                <p v-if="previewHeader" class="wa-header">
                  {{ previewHeader }}
                </p>

                <p class="wa-body">
                  {{ previewBody }}
                </p>

                <p
                  v-if="previewFooter"
                  class="wa-footer"
                >
                  {{ previewFooter }}
                </p>

                <div
                  v-if="form.buttons?.length"
                  class="wa-buttons"
                >
                  <v-chip
                    v-for="(btn,i) in form.buttons"
                    :key="i"
                    size="small"
                    color="blue-darken-2"
                    label
                  >
                    {{ btn.text || 'Button' }}
                  </v-chip>
                </div>

              </div>
            </div>

          </v-card>
        </v-col>

      </v-row>
    </div>
  </AdminLayout>
</template>

<style scoped>
/* ===============================
   PAGE BASE
================================ */
.template-form-dark {
  color: #e5e7eb;
}

/* ===============================
   CARD
================================ */
.dark-card {
  background: linear-gradient(180deg,#020617,#0f172a);
  border: 1px solid rgba(255,255,255,.06);
  border-radius: 16px;
}

/* ===============================
   TEXT
================================ */
.text-muted {
  color: #94a3b8;
}

/* ===============================
   WHATSAPP PREVIEW
================================ */
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
  font-size: 15px;
  line-height: 1.45;
}

.wa-header {
  font-weight: 600;
  margin-bottom: 4px;
}

.wa-body {
  white-space: pre-line;
}

.wa-footer {
  font-size: 12px;
  color: #94a3b8;
  margin-top: 6px;
}

.wa-buttons {
  margin-top: 8px;
}
</style>
