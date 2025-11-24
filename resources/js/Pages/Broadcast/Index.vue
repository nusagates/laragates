<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import axios from 'axios'

const page = usePage()

const templates = computed(() => page.props.templates || [])
const history   = computed(() => page.props.history || [])

// form state
const form = ref({
  name: '',
  template_id: null,
  audience_type: 'csv',      // 'all' | 'csv' (sementara kita pakai csv dulu)
  schedule_type: 'now',      // 'now' | 'later'
  send_at: null,
  csv_file: null,
})

const loading = ref(false)
const errors  = ref({})

// preview
const selectedTemplate = computed(() =>
  templates.value.find(t => t.id === form.value.template_id) || null
)

function onCsvChange(e) {
  const file = e.target.files[0]
  form.value.csv_file = file ?? null
}

async function startBroadcast() {
  loading.value = true
  errors.value  = {}

  try {
    const data = new FormData()
    data.append('name', form.value.name)
    data.append('template_id', form.value.template_id || '')
    data.append('audience_type', form.value.audience_type)
    data.append('schedule_type', form.value.schedule_type)
    if (form.value.schedule_type === 'later' && form.value.send_at) {
      data.append('send_at', form.value.send_at)
    }
    if (form.value.audience_type === 'csv' && form.value.csv_file) {
      data.append('csv_file', form.value.csv_file)
    }

    await axios.post(route('broadcast.store'), data)

    // reload page inertia
    router.visit(route('broadcast'), { preserveScroll: true })

  } catch (err) {
    if (err.response && err.response.status === 422) {
      errors.value = err.response.data.errors || {}
    } else {
      console.error(err)
      alert('Gagal membuat broadcast. Cek console.')
    }
  } finally {
    loading.value = false
  }
}

function fieldError(name) {
  return errors.value[name]?.[0] || ''
}
</script>

<template>
  <Head title="Broadcast" />

  <AdminLayout>
    <template #title>Broadcast</template>

    <v-row>

      <!-- FORM KIRI -->
      <v-col cols="12" md="8">
        <v-card elevation="2" class="pa-4">

          <h3 class="text-h6 font-weight-bold mb-1">Create Broadcast Campaign</h3>
          <p class="text-body-2 text-grey-darken-1 mb-4">
            Kirim pesan template WhatsApp ke banyak pelanggan sekaligus.
          </p>

          <!-- Campaign Name -->
          <v-text-field
            v-model="form.name"
            label="Campaign Name"
            density="comfortable"
            :error-messages="fieldError('name')"
            class="mb-4"
          />

          <!-- Template select -->
          <v-select
            v-model="form.template_id"
            :items="templates"
            item-title="name"
            item-value="id"
            label="Template Message"
            density="comfortable"
            :error-messages="fieldError('template_id')"
            class="mb-4"
          />

          <!-- Audience -->
          <h4 class="text-subtitle-2 mb-2">Audience</h4>

          <v-radio-group
            v-model="form.audience_type"
            inline
            :error-messages="fieldError('audience_type')"
          >
            <v-radio label="All Customers (TODO)" value="all" />
            <v-radio label="Upload CSV" value="csv" />
          </v-radio-group>

          <div v-if="form.audience_type === 'csv'" class="mt-2 mb-4">
            <p class="text-body-2 mb-1">
              Upload file CSV berisi daftar nomor WhatsApp (1 kolom, tanpa header). Contoh:
              <code>6281234567890</code>
            </p>
            <v-file-input
              accept=".csv,text/csv"
              label="CSV File"
              density="comfortable"
              prepend-icon="mdi-file-delimited"
              @change="onCsvChange"
              :error-messages="fieldError('csv_file')"
            />
          </div>

          <!-- Schedule -->
          <h4 class="text-subtitle-2 mt-4 mb-2">Schedule</h4>

          <v-radio-group
            v-model="form.schedule_type"
            inline
            :error-messages="fieldError('schedule_type')"
          >
            <v-radio label="Send Now" value="now" />
            <v-radio label="Schedule Later" value="later" />
          </v-radio-group>

          <v-text-field
            v-if="form.schedule_type === 'later'"
            v-model="form.send_at"
            type="datetime-local"
            label="Send At"
            density="comfortable"
            class="mt-2"
            :error-messages="fieldError('send_at')"
          />

          <div class="d-flex justify-end mt-6 ga-2">
            <v-btn variant="text" @click="router.visit(route('broadcast'))">
              Cancel
            </v-btn>
            <v-btn
              color="primary"
              :loading="loading"
              @click="startBroadcast"
            >
              START BROADCAST
            </v-btn>
          </div>
        </v-card>
      </v-col>

      <!-- PANEL KANAN: Preview + History -->
      <v-col cols="12" md="4">

        <v-card elevation="2" class="pa-4 mb-4">
          <h4 class="text-subtitle-1 font-weight-bold mb-2">
            Selected Template Preview
          </h4>

          <v-sheet
            class="pa-3 mt-2"
            color="grey-lighten-4"
            style="border-radius: 12px;"
          >
            <div v-if="selectedTemplate">
              <strong>{{ selectedTemplate.name }}</strong>
              <p class="text-body-2 mt-2">
                {{ selectedTemplate.body || 'Template body preview.' }}
              </p>
            </div>
            <div v-else class="text-body-2 text-grey-darken-1">
              Pilih template untuk melihat preview.
            </div>
          </v-sheet>
        </v-card>

        <v-card elevation="2" class="pa-4">
          <h4 class="text-subtitle-1 font-weight-bold mb-2">
            History
          </h4>

          <v-table density="compact">
            <thead>
              <tr>
                <th>Campaign</th>
                <th>Sent</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!history.length">
                <td colspan="3" class="text-center text-body-2 py-4">
                  Belum ada riwayat broadcast.
                </td>
              </tr>
              <tr v-for="h in history" :key="h.id">
                <td>
                  <div class="text-body-2">{{ h.name }}</div>
                  <div class="text-caption text-grey-darken-1">
                    {{ h.template }}
                  </div>
                </td>
                <td class="text-body-2">
                  {{ h.sent }} <span v-if="h.failed">/ {{ h.failed }} failed</span>
                </td>
                <td class="text-body-2">{{ h.date }}</td>
              </tr>
            </tbody>
          </v-table>
        </v-card>

      </v-col>
    </v-row>
  </AdminLayout>
</template>
