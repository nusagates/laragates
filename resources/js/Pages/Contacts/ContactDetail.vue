<script setup>
import { ref, watch } from 'vue'
import axios from 'axios'
import { useToast } from 'vue-toastification'

const props = defineProps({
  contactId: Number,
})

const toast = useToast()

const contact = ref(null)
const timeline = ref([])
const loading = ref(false)
const loadingTimeline = ref(false)

const newTag = ref('')
const showBlacklistConfirm = ref(false)

/* ================= LOAD CONTACT ================= */
async function loadContact() {
  if (!props.contactId) {
    contact.value = null
    return
  }

  loading.value = true
  try {
    const res = await axios.get(`/contacts/${props.contactId}`)
    contact.value = res.data
  } catch {
    toast.error('Gagal memuat contact')
  } finally {
    loading.value = false
  }
}

/* ================= LOAD TIMELINE ================= */
async function loadTimeline() {
  if (!props.contactId) return
  loadingTimeline.value = true
  try {
    const res = await axios.get(`/contacts/${props.contactId}/timeline`)
    timeline.value = res.data
  } finally {
    loadingTimeline.value = false
  }
}

watch(() => props.contactId, async () => {
  await loadContact()
  await loadTimeline()
}, { immediate: true })

/* ================= SAVE ================= */
async function saveContact() {
  try {
    await axios.put(`/contacts/${contact.value.id}`, {
      notes: contact.value.notes,
      is_vip: contact.value.is_vip,
      is_blacklisted: contact.value.is_blacklisted,
      tags: contact.value.tags,
    })
    toast.success('Contact updated')
    loadTimeline()
  } catch {
    toast.error('Gagal update contact')
  }
}

/* ================= TAG ================= */
function addTag() {
  if (!newTag.value) return
  const tag = newTag.value.toLowerCase()

  if (!contact.value.tags) contact.value.tags = []
  if (!contact.value.tags.includes(tag)) {
    contact.value.tags.push(tag)
    saveContact()
  }
  newTag.value = ''
}

function removeTag(tag) {
  contact.value.tags = contact.value.tags.filter(t => t !== tag)
  saveContact()
}

/* ================= BLACKLIST ================= */
function toggleBlacklist() {
  if (!contact.value.is_blacklisted) {
    showBlacklistConfirm.value = true
    return
  }
  contact.value.is_blacklisted = false
  saveContact()
}

function confirmBlacklist() {
  contact.value.is_blacklisted = true
  showBlacklistConfirm.value = false
  saveContact()
}

/* ================= FORMAT ================= */
function formatTime(t) {
  if (!t) return '-'
  return new Date(t).toLocaleString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>

<template>
  <div class="contact-detail">

    <div v-if="!contact && !loading" class="empty">
      Select a contact
    </div>

    <div v-else-if="contact" class="detail-wrapper">

      <!-- STATUS BANNER -->
<div
  v-if="contact.is_blacklisted"
  class="status-banner danger"
>
  🚫 <b>Blacklist Contact</b>
  <span>Agent tidak dapat mengirim pesan ke nomor ini</span>
</div>

<div
  v-else-if="contact.is_vip"
  class="status-banner vip"
>
  ⭐ <b>VIP Customer</b>
  <span>Prioritaskan respon dan layanan</span>
</div>


      <!-- HEADER -->
      <div class="header">
        <div>
          <h2>
            {{ contact.name || contact.phone }}
            <span v-if="contact.is_vip" class="badge vip">VIP</span>
          </h2>
          <p class="phone">{{ contact.phone }}</p>
        </div>
      </div>

      <!-- STATS -->
      <div class="stats">
        <div><b>{{ contact.total_chats }}</b><span>Chats</span></div>
        <div><b>{{ contact.total_messages }}</b><span>Messages</span></div>
        <div>
          <b>{{ formatTime(contact.last_contacted_at) }}</b>
          <span>Last Contact</span>
        </div>
      </div>

      <!-- BLACKLIST ALERT -->
      <div v-if="contact.is_blacklisted" class="alert">
        ⚠️ Contact ini diblacklist. Agent tidak dapat mengirim pesan.
      </div>

      <!-- TOGGLES -->
      <div class="toggles">
        <label>
          <input type="checkbox" v-model="contact.is_vip" @change="saveContact" />
          VIP Customer
        </label>

        <label>
          <input type="checkbox"
            :checked="contact.is_blacklisted"
            @change="toggleBlacklist"
          />
          Blacklist Contact
        </label>
      </div>

      <!-- TAGS -->
      <div class="section">
        <h4>Tags</h4>
        <div class="tags">
          <span v-for="tag in contact.tags || []" :key="tag" class="tag">
            {{ tag }}
            <button @click="removeTag(tag)">×</button>
          </span>
        </div>
        <input
          v-model="newTag"
          @keyup.enter="addTag"
          placeholder="Type tag and press Enter"
        />
      </div>

      <!-- NOTES -->
      <div class="section">
        <h4>Internal Notes</h4>
        <textarea
          v-model="contact.notes"
          rows="4"
          placeholder="Notes internal untuk agent / supervisor..."
          @blur="saveContact"
        />
      </div>

      <!-- TIMELINE -->
      <div class="section timeline-box">
        <h4>Activity Timeline</h4>

        <div v-if="loadingTimeline" class="muted">Loading activity...</div>
        <div v-else-if="!timeline.length" class="muted">No activity</div>

        <div class="timeline-scroll">
          <div
            v-for="(item, i) in timeline"
            :key="i"
            class="timeline-item"
          >
            <div class="dot"></div>
            <div>
              <div class="event">{{ item.event.replaceAll('_',' ') }}</div>
              <div class="meta">
                {{ item.actor }} • {{ formatTime(item.time) }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ACTION -->
      <div class="actions">
        <a :href="`/chat?phone=${contact.phone}`" class="btn">
          Open Chat
        </a>
      </div>
    </div>

    <!-- BLACKLIST CONFIRM -->
    <div v-if="showBlacklistConfirm" class="confirm">
      <div class="confirm-box">
        <h4>Blacklist contact?</h4>
        <p>Agent tidak akan bisa mengirim pesan.</p>
        <div class="confirm-actions">
          <button class="btn ghost" @click="showBlacklistConfirm=false">Cancel</button>
          <button class="btn danger" @click="confirmBlacklist">Blacklist</button>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
/* ===== CONTAINER ===== */
.contact-detail {
  height: 100%;
  background: radial-gradient(circle at top, #0f172a, #020617);
  border-radius: 14px;
  overflow: hidden;
}

.detail-wrapper {
  height: 100%;
  overflow-y: auto;
  padding: 20px;
}

/* ===== HEADER ===== */
.header h2 {
  display: flex;
  gap: 8px;
  align-items: center;
}

.phone {
  font-size: 13px;
  color: #94a3b8;
}

/* ===== BADGE ===== */
.badge {
  font-size: 11px;
  padding: 4px 8px;
  border-radius: 999px;
}
.vip {
  background: rgba(59,130,246,.2);
  color: #60a5fa;
}

/* ===== STATS ===== */
.stats {
  display: flex;
  gap: 16px;
  margin: 16px 0;
}
.stats div {
  text-align: center;
}
.stats span {
  font-size: 11px;
  color: #94a3b8;
}

/* ===== ALERT ===== */
.alert {
  background: rgba(239,68,68,.15);
  border: 1px solid rgba(239,68,68,.35);
  color: #f87171;
  padding: 10px;
  border-radius: 10px;
  margin-bottom: 14px;
}

/* ===== TOGGLES ===== */
.toggles {
  display: flex;
  gap: 20px;
  margin-bottom: 16px;
}

/* ===== SECTION ===== */
.section {
  margin-bottom: 18px;
}

/* ===== TAGS ===== */
.tags {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
  margin-bottom: 6px;
}
.tag {
  background: rgba(99,102,241,.2);
  color: #c7d2fe;
  padding: 4px 8px;
  border-radius: 999px;
  font-size: 11px;
}
.tag button {
  background: none;
  border: none;
  margin-left: 4px;
  cursor: pointer;
  color: inherit;
}

/* ===== INPUT ===== */
input, textarea {
  width: 100%;
  background: rgba(255,255,255,.05);
  border: 1px solid rgba(255,255,255,.1);
  border-radius: 10px;
  padding: 10px;
  color: #e5e7eb;
}

/* ===== TIMELINE ===== */
.timeline-box {
  border-top: 1px solid rgba(255,255,255,.08);
  padding-top: 12px;
}

.timeline-scroll {
  max-height: 220px;
  overflow-y: auto;
  margin-top: 10px;
  padding-left: 14px;
  border-left: 2px solid rgba(255,255,255,.08);
}

.timeline-item {
  position: relative;
  margin-bottom: 14px;
}
.timeline-item .dot {
  position: absolute;
  left: -9px;
  top: 4px;
  width: 8px;
  height: 8px;
  background: #6366f1;
  border-radius: 50%;
}
.event {
  font-weight: 600;
  font-size: 13px;
}
.meta {
  font-size: 11px;
  color: #94a3b8;
}

/* ===== ACTION ===== */
.actions {
  margin-top: 20px;
}
.btn {
  background: linear-gradient(135deg,#6366f1,#3b82f6);
  padding: 10px 16px;
  border-radius: 12px;
  color: white;
  text-decoration: none;
}

/* ===== CONFIRM ===== */
.confirm {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.6);
  display: flex;
  align-items: center;
  justify-content: center;
}
.confirm-box {
  background: linear-gradient(180deg,#020617,#0f172a);
  padding: 20px;
  border-radius: 16px;
  width: 360px;
}
.confirm-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}
.btn.ghost {
  background: transparent;
  border: 1px solid rgba(255,255,255,.2);
}
.btn.danger {
  background: linear-gradient(135deg,#ef4444,#dc2626);
}

/* ================= GLOBAL TEXT FIX ================= */
.contact-detail,
.contact-detail * {
  color: #e5e7eb;
}

/* ================= HEADINGS ================= */
.contact-detail h2,
.contact-detail h4 {
  color: #f8fafc;
}

/* ================= TOGGLES ================= */
.toggles {
  display: flex;
  gap: 24px;
  margin-bottom: 18px;
}

.toggles label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  color: #e5e7eb;
}

/* ================= CUSTOM CHECKBOX ================= */
.toggles input[type="checkbox"] {
  appearance: none;
  -webkit-appearance: none;
  width: 16px;
  height: 16px;
  border-radius: 4px;
  border: 1.5px solid rgba(255,255,255,.35);
  background: transparent;
  position: relative;
  cursor: pointer;
}

.toggles input[type="checkbox"]:checked {
  background: linear-gradient(135deg,#6366f1,#3b82f6);
  border-color: #6366f1;
}

.toggles input[type="checkbox"]:checked::after {
  content: "✓";
  position: absolute;
  top: -2px;
  left: 3px;
  font-size: 12px;
  color: white;
}

.toggles input[type="checkbox"]:focus-visible {
  outline: 2px solid rgba(99,102,241,.6);
  outline-offset: 2px;
}

/* ================= INPUT / TEXTAREA ================= */
input::placeholder,
textarea::placeholder {
  color: #64748b;
}

/* ================= STATS ================= */
.stats b {
  color: #f8fafc;
}

/* ================= TIMELINE TEXT ================= */
.timeline-item .event {
  color: #e5e7eb;
}

.timeline-item .meta {
  color: #94a3b8;
}

/* ================= EMPTY / MUTED ================= */
.empty,
.muted {
  color: #94a3b8;
}

/* ================= ALERT ================= */
.alert {
  color: #fca5a5;
}

/* ================= STATUS BANNER ================= */
.status-banner {
  padding: 12px 14px;
  border-radius: 12px;
  margin-bottom: 14px;
  display: flex;
  gap: 8px;
  align-items: center;
  font-size: 13px;
  line-height: 1.4;
}

.status-banner span {
  opacity: .85;
}

.status-banner.vip {
  background: linear-gradient(
    135deg,
    rgba(59,130,246,.25),
    rgba(59,130,246,.1)
  );
  border: 1px solid rgba(59,130,246,.35);
  color: #bfdbfe;
}

.status-banner.danger {
  background: linear-gradient(
    135deg,
    rgba(239,68,68,.25),
    rgba(239,68,68,.1)
  );
  border: 1px solid rgba(239,68,68,.35);
  color: #fecaca;
}


</style>
