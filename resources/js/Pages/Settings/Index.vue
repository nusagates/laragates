<script setup>
import { ref } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import axios from 'axios'

/* =========================
   PROPS
========================= */
const props = defineProps({
  settings: {
    type: Object,
    default: () => ({})
  }
})

/* =========================
   TAB STATE
========================= */
const activeTab = ref('general')

/* =========================
   FORM STATES
========================= */
const general = ref({
  company_name: props.settings?.company_name || 'WABA',
  timezone: props.settings?.timezone || 'Asia/Jakarta',
})

const whatsapp = ref({
  provider: 'Fonnte',
  api_key: props.settings?.wa_api_key || '',
  webhook_url: props.settings?.wa_webhook || '',
})

const preferences = ref({
  auto_assign: props.settings?.auto_assign_ticket ?? true,
  notify_sound: props.settings?.notif_sound ?? true,
})

/* =========================
   SAVE HANDLER (DUMMY)
========================= */
const saveSettings = async () => {
  try {
    await axios.post('/settings/save', {
      general: general.value,
      whatsapp: whatsapp.value,
      preferences: preferences.value,
    })
    alert('Settings saved')
  } catch (e) {
    console.error(e)
    alert('Failed to save settings')
  }
}
</script>

<template>
  <AdminLayout>
    <template #title>Settings</template>

    <div class="settings-wrapper">

      <!-- ================= TABS ================= -->
      <div class="settings-tabs">
        <button
          :class="{ active: activeTab === 'general' }"
          @click="activeTab = 'general'"
        >
          ⚙️ General
        </button>

        <button
          :class="{ active: activeTab === 'whatsapp' }"
          @click="activeTab = 'whatsapp'"
        >
          💬 WhatsApp API
        </button>

        <button
          :class="{ active: activeTab === 'preferences' }"
          @click="activeTab = 'preferences'"
        >
          🧩 Preferences
        </button>
      </div>

      <!-- ================= CONTENT ================= -->
      <div class="settings-card">

        <!-- ========== GENERAL ========= -->
        <div v-if="activeTab === 'general'" class="tab-content">
          <h3>General Settings</h3>
          <p class="subtitle">Pengaturan dasar aplikasi</p>

          <div class="form-grid">
            <div class="form-group">
              <label>Company Name</label>
              <input v-model="general.company_name" />
            </div>

            <div class="form-group">
              <label>Timezone</label>
              <select v-model="general.timezone">
                <option>Asia/Jakarta</option>
                <option>Asia/Singapore</option>
                <option>UTC</option>
              </select>
            </div>
          </div>
        </div>

        <!-- ========== WHATSAPP ========= -->
        <div v-if="activeTab === 'whatsapp'" class="tab-content">
          <h3>WhatsApp API</h3>
          <p class="subtitle">Konfigurasi provider WhatsApp</p>

          <div class="form-grid">
            <div class="form-group">
              <label>Provider</label>
              <select v-model="whatsapp.provider">
                <option>Fonnte</option>
                <option>Official WABA</option>
              </select>
            </div>

            <div class="form-group">
              <label>API Key</label>
              <input v-model="whatsapp.api_key" placeholder="********" />
            </div>

            <div class="form-group full">
              <label>Webhook URL</label>
              <input v-model="whatsapp.webhook_url" placeholder="https://..." />
            </div>
          </div>
        </div>

        <!-- ========== PREFERENCES ========= -->
        <div v-if="activeTab === 'preferences'" class="tab-content">
          <h3>Preferences</h3>
          <p class="subtitle">Preferensi sistem</p>

          <div class="toggle-list">
            <label>
              <input type="checkbox" v-model="preferences.auto_assign" />
              Auto assign agent
            </label>

            <label>
              <input type="checkbox" v-model="preferences.notify_sound" />
              Notification sound
            </label>
          </div>
        </div>

        <!-- ================= ACTION ================= -->
        <div class="actions">
          <button class="btn-primary" @click="saveSettings">
            Save Changes
          </button>
        </div>

      </div>
    </div>
  </AdminLayout>
</template>

<style scoped>
/* =====================================================
   SETTINGS – DARK WABA STYLE
===================================================== */

.settings-wrapper {
  max-width: 1100px;
  margin: 0 auto;
}

/* ================= TABS ================= */
.settings-tabs {
  display: flex;
  gap: 24px;
  margin-bottom: 18px;
}

.settings-tabs button {
  background: transparent;
  border: none;
  color: #94a3b8;
  font-weight: 600;
  padding-bottom: 8px;
  cursor: pointer;
}

.settings-tabs button.active {
  color: #38bdf8;
  border-bottom: 2px solid #38bdf8;
}

/* ================= CARD ================= */
.settings-card {
  background: linear-gradient(180deg, #020617, #0f172a);
  border: 1px solid rgba(255,255,255,.06);
  border-radius: 18px;
  padding: 24px;
  color: #e5e7eb;
}

/* ================= CONTENT ================= */
.tab-content h3 {
  margin-bottom: 4px;
}

.subtitle {
  color: #94a3b8;
  font-size: 13px;
  margin-bottom: 20px;
}

/* ================= FORM ================= */
.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group.full {
  grid-column: span 2;
}

.form-group label {
  font-size: 12px;
  color: #94a3b8;
  margin-bottom: 6px;
}

.form-group input,
.form-group select {
  background: rgba(255,255,255,.04);
  border: 1px solid rgba(255,255,255,.08);
  border-radius: 10px;
  padding: 10px 12px;
  color: #e5e7eb;
}

/* ================= TOGGLE ================= */
.toggle-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.toggle-list label {
  font-size: 14px;
  color: #e5e7eb;
}

/* ================= ACTION ================= */
.actions {
  margin-top: 24px;
  display: flex;
  justify-content: flex-end;
}

.btn-primary {
  background: linear-gradient(135deg, #2563eb, #38bdf8);
  border: none;
  color: white;
  padding: 10px 18px;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
}
</style>
