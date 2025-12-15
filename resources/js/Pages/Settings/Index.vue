<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = usePage().props

// data dari backend
const settings = ref({
  company_name: props.settings?.company_name ?? '',
  timezone: props.settings?.timezone ?? 'Asia/Jakarta',

  wa_phone: props.settings?.wa_phone ?? '',
  wa_webhook: props.settings?.wa_webhook ?? '',
  wa_api_key: props.settings?.wa_api_key ?? '',

  notif_sound: props.settings?.notif_sound ?? false,
  notif_desktop: props.settings?.notif_desktop ?? false,
  auto_assign_ticket: props.settings?.auto_assign_ticket ?? false,
})

// TAB STATE
const tab = ref(0)

function saveGeneral() {
  router.post(route('settings.general'), {
    company_name: settings.value.company_name,
    timezone: settings.value.timezone,
  })
}

function saveWaba() {
  router.post(route('settings.waba'), {
    wa_phone: settings.value.wa_phone,
    wa_webhook: settings.value.wa_webhook,
    wa_api_key: settings.value.wa_api_key,
  })
}

function testWebhook() {
  router.get(route('settings.testWebhook'), {}, {
    onSuccess: () => alert("Webhook reachable!"),
    onError: () => alert("Webhook NOT reachable."),
  })
}

function savePreferences() {
  router.post(route('settings.preferences'), {
    notif_sound: settings.value.notif_sound,
    notif_desktop: settings.value.notif_desktop,
    auto_assign_ticket: settings.value.auto_assign_ticket,
  })
}
</script>

<template>
  <Head title="Settings" />

  <AdminLayout>
    <template #title>Settings</template>

    <v-card elevation="2" class="pa-4">

      <!-- Tabs -->
      <v-tabs v-model="tab" bg-color="transparent" color="primary">
        <v-tab>
          <v-icon class="mr-2">mdi-cog</v-icon>
          GENERAL
        </v-tab>
        <v-tab>
          <v-icon class="mr-2">mdi-whatsapp</v-icon>
          WHATSAPP API
        </v-tab>
        <v-tab>
          <v-icon class="mr-2">mdi-tune</v-icon>
          PREFERENCES
        </v-tab>
      </v-tabs>

      <v-divider class="mb-6" />

      <!-- --------------------------- GENERAL TAB --------------------------- -->
      <v-window v-model="tab">
        <v-window-item :value="0">
          <h3 class="text-h6 font-weight-bold mb-4">General Settings</h3>

          <v-text-field
            label="Company Name"
            v-model="settings.company_name"
            class="mb-4"
            density="comfortable"
          />

          <v-text-field
            label="Timezone"
            v-model="settings.timezone"
            class="mb-4"
            density="comfortable"
          />

          <v-btn color="primary" @click="saveGeneral">
            SAVE CHANGES
          </v-btn>
        </v-window-item>

        <!-- --------------------------- WABA API TAB --------------------------- -->
        <v-window-item :value="1">
          <h3 class="text-h6 font-weight-bold mb-4">WhatsApp Business API</h3>

          <v-text-field
            readonly
            label="Connected Phone Number"
            v-model="settings.wa_phone"
            class="mb-4"
          />

          <v-text-field
            label="Webhook URL"
            v-model="settings.wa_webhook"
            class="mb-4"
          />

          <v-text-field
            label="API Key"
            v-model="settings.wa_api_key"
            type="password"
            class="mb-4"
            append-inner-icon="mdi-eye"
          />

          <div class="d-flex align-center ga-4 mb-4">
            <v-chip color="red" text-color="white">
              Disconnected
            </v-chip>

            <v-btn size="small" variant="outlined">
              RECONNECT
            </v-btn>

            <v-btn size="small" variant="text" @click="testWebhook">
              TEST WEBHOOK
            </v-btn>
          </div>

          <v-btn color="primary" @click="saveWaba">
            SAVE CHANGES
          </v-btn>
        </v-window-item>

        <!-- --------------------------- PREFERENCES TAB --------------------------- -->
        <v-window-item :value="2">
          <h3 class="text-h6 font-weight-bold mb-4">Preferences</h3>

          <v-switch
            v-model="settings.notif_sound"
            label="Enable sound notification for new messages"
            class="mb-2"
          />

          <v-switch
            v-model="settings.notif_desktop"
            label="Show desktop notification for new chats"
            class="mb-2"
          />

          <v-switch
            v-model="settings.auto_assign_ticket"
            label="Auto-assign new tickets to available agents"
            class="mb-4"
          />

          <v-btn color="primary" @click="savePreferences">
            SAVE CHANGES
          </v-btn>
        </v-window-item>
      </v-window>
    </v-card>
  </AdminLayout>
</template>
