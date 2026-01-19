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

/* ===== SUMMARY (INLINE STATUS BAR) ===== */
const summary = ref(null)
const loadingSummary = ref(false)

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

/* ================= LOAD SUMMARY ================= */
async function loadSummary() {
  if (!props.contactId) return
  loadingSummary.value = true
  try {
    const res = await axios.get(`/customers/${props.contactId}/summary`)
    summary.value = res.data
  } catch {
    summary.value = null
  } finally {
    loadingSummary.value = false
  }
}

watch(
  () => props.contactId,
  async () => {
    await loadContact()
    await loadTimeline()
    await loadSummary()
  },
  { immediate: true }
)

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
      <div v-if="contact.is_blacklisted" class="status-banner danger">
        🚫 <b>Blacklist Contact</b>
        <span>Agent tidak dapat mengirim pesan ke nomor ini</span>
      </div>

      <div v-else-if="contact.is_vip" class="status-banner vip">
        ⭐ <b>VIP Customer</b>
        <span> | Prioritaskan respon dan layanan</span>
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

      <!-- ===== INLINE SUMMARY STATUS BAR ===== -->
      <div
        v-if="summary && !loadingSummary"
        class="contact-status-bar"
      >
        <span class="status-pill" :class="summary.status">
  <span class="dot-indicator"></span>
  {{ summary.status.toUpperCase() }}
</span>


        <span class="dot">•</span>

        <span class="activity">
          {{ summary.last_activity?.text ?? 'No recent activity' }}
        </span>

        <span class="dot">•</span>

        <span class="messages">
          {{ summary.counters.messages_today }} msg today
        </span>

        <template v-if="summary.flags.length">
          <span class="dot">•</span>
          <span class="flags">
            {{ summary.flags.join(', ') }}
          </span>
        </template>
      </div>

      <div v-else-if="loadingSummary" class="contact-status-bar muted">
        Loading contact status...
      </div>

      <!-- STATS -->
      <div class="stats">
        <div><b>{{ contact.total_chats }}</b><span> Chats |</span></div>
        <div><b>{{ contact.total_messages }}</b><span> Messages |</span></div>
        <div>
          <b>{{ formatTime(contact.last_contacted_at) }}</b>
          <span> Last Contact</span>
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
          <input
            type="checkbox"
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

/* ===== INLINE STATUS BAR ===== */
.contact-status-bar {
  margin: 8px 0 14px;
  font-size: 13px;
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  align-items: center;
}

.contact-status-bar .status {
  font-weight: 600;
  letter-spacing: .3px;
}

.contact-status-bar .status.active {
  color: #4ade80;
}

.contact-status-bar .status.inactive {
  color: #facc15;
}

.contact-status-bar .status.blocked {
  color: #f87171;
}

.contact-status-bar .dot {
  opacity: .5;
}

.contact-status-bar .activity,
.contact-status-bar .messages,
.contact-status-bar .flags {
  color: #cbd5f5;
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

/* ===== GLOBAL TEXT ===== */
.contact-detail,
.contact-detail * {
  color: #e5e7eb;
}
.empty,
.muted {
  color: #94a3b8;
}

.status-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 8px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: .3px;
  background: rgba(255,255,255,.06);
}

.status-pill .dot-indicator {
  width: 6px;
  height: 6px;
  border-radius: 50%;
}

.status-pill.active {
  color: #4ade80;
  border: 1px solid rgba(74,222,128,.35);
}
.status-pill.active .dot-indicator {
  background: #4ade80;
}

.status-pill.inactive {
  color: #facc15;
  border: 1px solid rgba(250,204,21,.35);
}
.status-pill.inactive .dot-indicator {
  background: #facc15;
}

.status-pill.blocked {
  color: #f87171;
  border: 1px solid rgba(248,113,113,.35);
}
.status-pill.blocked .dot-indicator {
  background: #f87171;
}

</style>
