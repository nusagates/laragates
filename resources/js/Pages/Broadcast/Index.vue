<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

/* ========================= FORM ========================= */
const form = ref({
  name: '',
  template_id: null,
  audience: 'all',
  schedule_type: 'now',
  schedule_at: null,
})

/* ========================= DUMMY TEMPLATES ========================= */
const templates = [
  { id: 1, name: 'Promo November', preview: 'Nikmati promo spesial bulan ini 🎉' },
  { id: 2, name: 'Pengingat Pembayaran', preview: 'Segera selesaikan pembayaran Anda ya 😊' },
  { id: 3, name: 'Ucapan Terima Kasih', preview: 'Terima kasih sudah berbelanja 🙏' },
]

/* ========================= CSV UPLOAD (Dummy) ========================= */
const uploaded = ref(null)
const csvCount = ref(0)
function uploadCSV(e) {
  const file = e.target.files[0]
  if (!file) return
  uploaded.value = file.name
  csvCount.value = Math.floor(Math.random() * 1000 + 20) // dummy preview count
}

/* ========================= SENDING PROGRESS ========================= */
const sending = ref(false)
const progress = ref(0)
const sentCount = ref(0)
const total = ref(0)
const showPreview = ref(false) // Template preview modal

function startBroadcast() {
  sending.value = true
  total.value = uploaded.value ? csvCount.value : 500 // dummy audience
  progress.value = 0
  sentCount.value = 0
  simulateSend()
}

// fake realtime sending
function simulateSend() {
  if (progress.value < 100) {
    setTimeout(() => {
      sentCount.value += Math.floor(total.value / 40)
      progress.value += 3
      simulateSend()
    }, 200)
  } else {
    progress.value = 100
    sentCount.value = total.value
    setTimeout(() => sending.value = false, 1000)
  }
}

/* ========================= HISTORY ========================= */
const history = ref([
  { id: 1, name: 'Promo 10.10', sent: 350, date: '2025-11-10' },
  { id: 2, name: 'Flash Sale', sent: 220, date: '2025-11-07' },
])

function resend(item) {
  console.log("Resend:", item)
  startBroadcast()
}

/* ========================= TEMPLATE PREVIEW ========================= */
function previewTemplate() {
  if (!form.value.template_id) return
  showPreview.value = true
}
</script>

<template>
  <Head title="Broadcast" />

  <AdminLayout>
    <template #title>Broadcast</template>

    <v-row>
      <!-- ===================== LEFT FORM ===================== -->
      <v-col cols="12" md="7">

        <v-card elevation="2" class="pa-4 mb-4">
          <h3 class="text-h6 font-weight-bold mb-4">Create Broadcast Campaign</h3>

          <v-form @submit.prevent="startBroadcast">
            <v-text-field
              v-model="form.name"
              label="Campaign Name"
              placeholder="Contoh: Promo Akhir Tahun"
              class="mb-3"
            />

            <v-select
              v-model="form.template_id"
              :items="templates"
              item-title="name"
              item-value="id"
              label="Template Message"
              class="mb-3"
              @click="previewTemplate"
              @change="previewTemplate"
            />

            <v-divider class="my-4" />

            <h4 class="text-subtitle-1 mb-2">Audience</h4>
            <v-radio-group v-model="form.audience" inline>
              <v-radio label="All Customers" value="all" />
              <v-radio label="By Tag / Segment" value="tag" />
              <v-radio label="Upload CSV" value="csv" />
            </v-radio-group>

            <div v-if="form.audience === 'tag'" class="mt-2 mb-4">
              <v-text-field label="Tag Name" placeholder="contoh: VIP, new_user" />
            </div>

            <div v-if="form.audience === 'csv'" class="mt-2 mb-4">
              <v-file-input label="Upload CSV Contacts" accept=".csv" @change="uploadCSV" />
              <p class="text-caption text-grey-darken-1">
                {{ uploaded ? `Uploaded: ${uploaded} (${csvCount} contacts)` : 'Format: phone_number, full_name' }}
              </p>
            </div>

            <v-divider class="my-4" />

            <h4 class="text-subtitle-1 mb-2">Schedule</h4>
            <v-radio-group v-model="form.schedule_type" inline>
              <v-radio label="Send Now" value="now" />
              <v-radio label="Schedule Later" value="later" />
            </v-radio-group>

            <div v-if="form.schedule_type === 'later'" class="mt-2 mb-4">
              <v-text-field v-model="form.schedule_at" type="datetime-local" label="Send At" />
            </div>

            <div class="d-flex justify-end mt-4">
              <v-btn variant="text" class="mr-2">Cancel</v-btn>
              <v-btn color="primary" type="submit">Start Broadcast</v-btn>
            </div>
          </v-form>
        </v-card>
      </v-col>

      <!-- ===================== RIGHT PANEL ===================== -->
      <v-col cols="12" md="5">

        <!-- ===== SENDING PROGRESS ===== -->
        <v-card v-if="sending" elevation="2" class="pa-4 mb-4 text-center">
          <h3 class="text-subtitle-1 mb-2 font-weight-bold">Broadcast in Progress</h3>
          <v-progress-linear :model-value="progress" class="mt-2 mb-4" height="10" color="blue" />

          <div class="circle-loader">
            <strong>{{ sentCount }}/{{ total }}</strong>
          </div>

          <p class="text-caption text-grey-darken-1 mt-2">Sending messages...</p>
        </v-card>

        <!-- ===== PREVIEW ===== -->
        <v-card elevation="2" class="pa-4 mb-4">
          <h3 class="text-subtitle-1 font-weight-bold mb-2">Selected Template Preview</h3>
          <v-sheet elevation="1" class="pa-4" style="border-radius:12px; background:#f4f7fb;">
            <p class="text-body-2 text-grey-darken-1 mb-2">
              {{ form.name || 'Campaign name here' }}
            </p>
            <div style="background:white; padding:10px 14px; border-radius:12px; display:inline-block;">
              <span class="text-body-2">
                {{ templates.find(t => t.id === form.template_id)?.preview || 'Isi template akan tampil di sini.' }}
              </span>
            </div>
          </v-sheet>
        </v-card>

        <!-- ===== HISTORY ===== -->
        <v-card elevation="2" class="pa-4">
          <h3 class="text-subtitle-1 font-weight-bold mb-3">History</h3>

          <v-table density="comfortable">
            <thead>
              <tr>
                <th>Campaign</th>
                <th>Sent</th>
                <th>Date</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="h in history" :key="h.id">
                <td>{{ h.name }}</td>
                <td>{{ h.sent }}</td>
                <td>{{ h.date }}</td>
                <td>
                  <v-btn icon @click="resend(h)">
                    <v-icon color="primary">mdi-refresh</v-icon>
                  </v-btn>
                </td>
              </tr>
            </tbody>
          </v-table>
        </v-card>
      </v-col>
    </v-row>

    <!-- ===================== MODAL TEMPLATE PREVIEW ===================== -->
    <v-dialog v-model="showPreview" width="420">
      <v-card class="pa-4">
        <h3 class="text-h6 mb-4 font-weight-bold">Template Preview</h3>
        <v-sheet elevation="1" class="pa-4" style="border-radius:12px; background:#f4f7fb;">
          <div style="background:white; padding:12px 16px; border-radius:12px; display:inline-block;">
            <p>{{ templates.find(t => t.id === form.template_id)?.preview }}</p>
          </div>
        </v-sheet>
        <div class="d-flex justify-end mt-4">
          <v-btn variant="text" @click="showPreview = false">Close</v-btn>
        </div>
      </v-card>
    </v-dialog>

  </AdminLayout>
</template>

<style scoped>
.circle-loader {
  height: 70px;
  width: 70px;
  margin: auto;
  border-radius: 50%;
  border: 6px solid rgba(0,0,0,0.08);
  border-top-color: #1976d2;
  display: flex;
  justify-content: center;
  align-items: center;
  animation: spin 1s infinite linear;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
