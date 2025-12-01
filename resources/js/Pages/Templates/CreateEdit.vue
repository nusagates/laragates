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

    <v-row>
      <!-- LEFT SIDE = FORM -->
      <v-col cols="12" md="6">
        <v-card class="pa-4" elevation="2">

          <h3 class="text-h6 font-weight-bold mb-4">
            {{ isEdit ? 'Edit Template' : 'Create New Template' }}
          </h3>

          <v-text-field v-model="form.name" label="Template Name" class="mb-3" />

          <v-select
            v-model="form.category"
            label="Category"
            :items="['Utility','Marketing','Authentication']"
            class="mb-3"
          />

          <v-select
            v-model="form.language"
            label="Language"
            :items="['id','en']"
            class="mb-3"
          />

          <v-text-field
            v-model="form.header"
            label="Header (optional)"
            class="mb-3"
          />

          <v-textarea
            v-model="form.body"
            label="Body Message"
            rows="5"
            hint="Gunakan {1}, {2} sebagai variable"
            class="mb-3"
          />

          <v-text-field
            v-model="form.footer"
            label="Footer (optional)"
            class="mb-3"
          />

          <v-textarea
            v-model="form.buttons"
            label="Buttons JSON (optional)"
            hint='Example: [{"type":"url","text":"Visit","url":"https://..."}]'
            rows="3"
            class="mb-3"
          />

          <div class="d-flex justify-end gap-2 mt-4">
            <v-btn variant="text" @click="router.visit('/templates')">Cancel</v-btn>
            <v-btn color="primary" @click="save">
              {{ isEdit ? 'Update' : 'Save' }}
            </v-btn>
          </div>

        </v-card>
      </v-col>

      <!-- RIGHT SIDE = PREVIEW -->
      <v-col cols="12" md="6">
        <v-card class="pa-4" elevation="2">
          <h3 class="text-h6 font-weight-bold mb-4">Live Preview</h3>

          <div v-if="allVars.length">
            <h4 class="text-subtitle-2 mb-2">Variables</h4>

            <v-row>
              <v-col cols="12" md="6" v-for="v in allVars" :key="v">
                <v-text-field
                  v-model="params[v]"
                  :label="`Value for {${v}}`"
                  class="mb-2"
                />
              </v-col>
            </v-row>
          </div>

          <v-sheet
            elevation="1"
            class="pa-4 mt-4"
            style="background:#ece5dd; border-radius:12px;"
          >
            <div style="
              background:#dcf8c6;
              padding:14px 16px;
              border-radius:12px;
              max-width:90%;
              font-size:15px;
              line-height:1.4;
            ">
              <p v-if="previewHeader"><b>{{ previewHeader }}</b></p>
              <p style="white-space:pre-line;">{{ previewBody }}</p>
              <p v-if="previewFooter" class="text-grey-darken-1 text-caption">
                {{ previewFooter }}
              </p>

              <div v-if="form.buttons?.length" class="mt-2">
                <v-chip
                  v-for="(btn,i) in form.buttons"
                  :key="i"
                  class="ma-1"
                  color="blue"
                  label
                  small
                >
                  {{ btn.text || 'Button' }}
                </v-chip>
              </div>
            </div>
          </v-sheet>

        </v-card>
      </v-col>
    </v-row>
  </AdminLayout>
</template>
