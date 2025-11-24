<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { ref } from 'vue'

/* ===================== LOAD DATA DARI BACKEND ===================== */
const page = usePage()
const s = page.props.settings // shortcut biar rapi

/* ===================== TAB CONTROL ===================== */
const tab = ref('general')

/* ===================== GENERAL ===================== */
const general = useForm({
  company_name: s?.company_name ?? 'CS WABA Demo',
  timezone: s?.timezone ?? 'Asia/Jakarta',
})

function saveGeneral() {
  general.post('/settings/general')
}

/* ===================== WHATSAPP ===================== */
const whatsapp = useForm({
  phone_number: s?.wa_phone_number ?? '',
  webhook_url: s?.wa_webhook_url ?? '',
  api_key: s?.wa_api_key ?? '',
  status: s?.wa_phone_number ? 'connected' : 'disconnected',
  show_key: false,
})

function saveWhatsApp() {
  whatsapp.post('/settings/whatsapp')
}

/* ===================== PREFERENCES ===================== */
const preferences = useForm({
  sound_notification: s?.notif_sound ?? true,
  desktop_notification: s?.notif_desktop ?? true,
  auto_assign_ticket: s?.auto_assign ?? false,
})

function savePreferences() {
  preferences.post('/settings/preferences')
}

/* ===================== TEST WEBHOOK MODAL ===================== */
const showTest = ref(false)
const testResponse = ref(null)

function testWebhook() {
  testResponse.value = null
  setTimeout(() => {
    testResponse.value = {
      success: true,
      message: 'Webhook received successfully!',
      timestamp: new Date().toLocaleString()
    }
  }, 800)
}
</script>

<template>
  <Head title="Settings" />

  <AdminLayout>
    <template #title>Settings</template>

    <v-card elevation="2" class="pa-0">

      <!-- ===================== TABS ===================== -->
      <v-tabs v-model="tab" bg-color="#f5f6fa" grow>
        <v-tab value="general" prepend-icon="mdi-cog">General</v-tab>
        <v-tab value="whatsapp" prepend-icon="mdi-whatsapp">WhatsApp API</v-tab>
        <v-tab value="preferences" prepend-icon="mdi-tune">Preferences</v-tab>
      </v-tabs>

      <v-divider></v-divider>

      <v-card-text class="pa-6">
        
        <!-- ===================== GENERAL ===================== -->
        <div v-if="tab === 'general'">
          <h3 class="text-h6 mb-4 font-weight-bold">General Settings</h3>

          <v-text-field
            v-model="general.company_name"
            label="Company Name"
            class="mb-3"
          />

          <v-text-field
            v-model="general.timezone"
            label="Timezone"
            placeholder="Asia/Jakarta"
            class="mb-3"
          />

          <v-btn color="primary" class="mt-4" :loading="general.processing" @click="saveGeneral">
            Save Changes
          </v-btn>
        </div>

        <!-- ===================== WHATSAPP ===================== -->
        <div v-if="tab === 'whatsapp'">
          <h3 class="text-h6 mb-4 font-weight-bold">WhatsApp Business API</h3>

          <v-text-field
            v-model="whatsapp.phone_number"
            label="Connected Phone Number"
            class="mb-3"
          />

          <v-text-field
            v-model="whatsapp.webhook_url"
            label="Webhook URL"
            class="mb-3"
            readonly
          />

          <v-text-field
            v-model="whatsapp.api_key"
            :type="whatsapp.show_key ? 'text' : 'password'"
            label="API Key"
            class="mb-3"
            prepend-inner-icon="mdi-key-outline"
            :append-inner-icon="whatsapp.show_key ? 'mdi-eye-off' : 'mdi-eye'"
            @click:append-inner="whatsapp.show_key = !whatsapp.show_key"
          />

          <div class="d-flex align-center mb-4">
            <v-chip
              :color="whatsapp.status === 'connected' ? 'green' : 'red'"
              dark
              size="small"
            >
              {{ whatsapp.status === 'connected' ? 'Connected' : 'Disconnected' }}
            </v-chip>

            <v-btn class="ms-3" variant="outlined" size="small">Reconnect</v-btn>

            <v-btn class="ms-3" variant="text" size="small" color="primary" @click="showTest = true">
              Test Webhook
            </v-btn>
          </div>

          <p class="text-caption text-grey-darken-1">
            Pastikan webhook URL di atas sudah dipasang di provider WhatsApp Anda.
          </p>

          <v-btn color="primary" class="mt-4" :loading="whatsapp.processing" @click="saveWhatsApp">
            Save Changes
          </v-btn>
        </div>

        <!-- ===================== PREFERENCES ===================== -->
        <div v-if="tab === 'preferences'">
          <h3 class="text-h6 mb-4 font-weight-bold">Preferences</h3>

          <v-switch v-model="preferences.sound_notification" label="Enable sound notification for new messages" />
          <v-switch v-model="preferences.desktop_notification" label="Show desktop notification for new chats" />
          <v-switch v-model="preferences.auto_assign_ticket" label="Auto-assign new tickets to available agents" />

          <v-btn color="primary" class="mt-4" :loading="preferences.processing" @click="savePreferences">
            Save Changes
          </v-btn>
        </div>

      </v-card-text>
    </v-card>

    <!-- ===================== TEST WEBHOOK MODAL ===================== -->
    <v-dialog v-model="showTest" width="450">
      <v-card class="pa-4">
        <h3 class="text-h6 mb-3 font-weight-bold">Test Webhook</h3>

        <p class="text-body-2 text-grey-darken-1 mb-3">
          Klik tombol di bawah untuk mengirim simulasi payload webhook.
        </p>

        <v-btn color="primary" @click="testWebhook" class="mb-3">Send Test Request</v-btn>

        <v-alert
          v-if="testResponse"
          type="success"
          class="mt-3"
          border="start"
          prominent
        >
          {{ testResponse.message }}  
          <br />
          <small>{{ testResponse.timestamp }}</small>
        </v-alert>

        <div class="d-flex justify-end mt-4">
          <v-btn variant="text" @click="showTest = false">Close</v-btn>
        </div>
      </v-card>
    </v-dialog>

  </AdminLayout>
</template>

<style scoped>
</style>
