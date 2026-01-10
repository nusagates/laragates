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

/* === SOURCE OPTIONS === */
const sources = ['WhatsApp', 'Website', 'Instagram', 'Manual']

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

    if (!contact.value.priority) {
      contact.value.priority = 'normal'
    }
  } catch {
    toast.error('Failed to load contact')
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
      source: contact.value.source,
      priority: contact.value.priority,
    })
    toast.success('Contact updated successfully')
    loadTimeline()
  } catch {
    toast.error('Failed to update contact')
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
      Select a contact to view details
    </div>

    <div v-else-if="contact" class="detail-wrapper">

      <!-- STATUS BANNER -->
      <div v-if="contact.is_blacklisted" class="status-banner danger">
        🚫 <b>Blacklisted Contact</b>
        <span> — Agents can no longer send messages to this number</span>
      </div>

      <div v-else-if="contact.is_vip" class="status-banner vip">
        ⭐ <b>VIP Customer</b>
        <span> — Prioritized response and service</span>
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
      <div v-if="summary && !loadingSummary" class="contact-status-bar">
        <span class="status-pill" :class="summary.status">
          <span class="dot-indicator"></span>
          {{ summary.status.toUpperCase() }}
        </span>

        <span class="dot">•</span>
        <span class="activity">
          {{ summary.last_activity?.text ?? 'No recent activity recorded' }}
        </span>

        <span class="dot">•</span>
        <span class="messages">
          Messages today: {{ summary.counters.messages_today }}
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
        <div><b>{{ contact.total_chats }}</b><span> Total Chats</span></div>
        <div><b>{{ contact.total_messages }}</b><span> Total Messages</span></div>
        <div>
          <b>{{ formatTime(contact.last_contacted_at) }}</b>
          <span> Last Contact</span>
        </div>
      </div>

      <!-- BLACKLIST ALERT -->
      <div v-if="contact.is_blacklisted" class="alert">
        ⚠️ This contact is blacklisted. Messaging is disabled.
      </div>

      <!-- TOGGLES -->
      <div class="toggles">
        <label>
          <input type="checkbox" v-model="contact.is_vip" @change="saveContact" />
          Mark as VIP Customer
        </label>

        <label>
          <input
            type="checkbox"
            :checked="contact.is_blacklisted"
            @change="toggleBlacklist"
          />
          Blacklist this contact
        </label>
      </div>

      <!-- CONTACT PROFILE -->
      <div class="section">
        <h4>Contact Profile</h4>

        <div class="row">
          <label>Contact Source</label>
          <select v-model="contact.source" @change="saveContact">
            <option value="">— Select source —</option>
            <option v-for="s in sources" :key="s" :value="s">
              {{ s }}
            </option>
          </select>
        </div>

        <div class="row">
          <label>Priority Level</label>
          <select v-model="contact.priority" @change="saveContact">
            <option value="low">Low</option>
            <option value="normal">Normal</option>
            <option value="high">High</option>
          </select>
        </div>
      </div>

      <!-- TAGS -->
      <div class="section">
        <h4>Customer Tags</h4>
        <div class="tags">
          <span v-for="tag in contact.tags || []" :key="tag" class="tag">
            {{ tag }}
            <button @click="removeTag(tag)">×</button>
          </span>
        </div>
        <input
          v-model="newTag"
          @keyup.enter="addTag"
          placeholder="Add tag and press Enter"
        />
      </div>

      <!-- NOTES -->
      <div class="section">
        <h4>Internal Notes</h4>
        <textarea
          v-model="contact.notes"
          rows="4"
          placeholder="Visible only to agents and supervisors"
          @blur="saveContact"
        />
      </div>

      <!-- TIMELINE -->
      <div class="section timeline-box">
        <h4>Customer Activity Timeline</h4>

        <div v-if="loadingTimeline" class="muted">Loading activity...</div>
        <div v-else-if="!timeline.length" class="muted">
          No recorded activity yet
        </div>

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
        <h4>Blacklist this contact?</h4>
        <p>Agents will no longer be able to send messages to this number.</p>
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

/* ============================= */
/* === ADDITION: CONTACT INFO === */
/* ============================= */
.row {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-bottom: 12px;
}

.row label {
  font-size: 13px;
  color: #cbd5f5;
}

.row select {
  background: rgba(255,255,255,.06);
  border: 1px solid rgba(255,255,255,.12);
  border-radius: 10px;
  padding: 10px;
  color: #f8fafc;
}

.row select option {
  color: #020617;
}

/* ===================================================
   FIX DARK MODE TEXT (SELECT, OPTION, LABEL)
   =================================================== */

/* FORCE text color inside select */
.contact-detail select {
  color: #f8fafc !important;
}

/* FORCE selected value */
.contact-detail select:focus,
.contact-detail select:active {
  color: #f8fafc !important;
}

/* FORCE option text (dropdown list) */
.contact-detail select option {
  color: #020617 !important; /* dropdown background putih */
  background: #f8fafc;
}

/* FIX label that still black */
.contact-detail label {
  color: #e5e7eb !important;
}

/* FIX headings inside section */
.contact-detail h4 {
  color: #f8fafc !important;
}

/* FIX stray spans (stats, small text) */
.contact-detail span,
.contact-detail small,
.contact-detail p {
  color: #cbd5f5;
}

/* FIX placeholder text */
.contact-detail input::placeholder,
.contact-detail textarea::placeholder {
  color: #94a3b8;
}

/* ===================================================
   FINAL VISIBILITY FIX (TIMELINE + NOTES)
   =================================================== */

/* Internal Notes text */
.contact-detail textarea {
  color: #f8fafc !important;
  background: rgba(255,255,255,0.08);
}

/* Timeline container */
.contact-detail .timeline-box {
  background: rgba(255,255,255,0.02);
}

/* Timeline scroll area */
.contact-detail .timeline-scroll {
  background: linear-gradient(
    180deg,
    rgba(255,255,255,0.04),
    rgba(255,255,255,0.01)
  );
}

/* Timeline event title */
.contact-detail .timeline-item .event {
  color: #f8fafc !important;
  opacity: 1 !important;
}

/* Timeline meta text (actor + time) */
.contact-detail .timeline-item .meta {
  color: #cbd5f5 !important;
  opacity: 1 !important;
}

/* Timeline empty / muted text */
.contact-detail .timeline-box .muted {
  color: #94a3b8 !important;
}

/* Safety: kill opacity inheritance */
.contact-detail .timeline-box * {
  opacity: 1;
}

/* ===================================================
   ABSOLUTE FINAL FIX — TEXT FILL OVERRIDE
   =================================================== */

/* Force ALL text rendering to light color */
.contact-detail,
.contact-detail * {
  color: #e5e7eb !important;
  -webkit-text-fill-color: #e5e7eb !important;
}

/* Exception: dropdown option list (must stay dark on light bg) */
.contact-detail select option {
  color: #020617 !important;
  -webkit-text-fill-color: #020617 !important;
  background: #f8fafc;
}

/* Checkbox accent (visual only) */
.contact-detail input[type="checkbox"] {
  accent-color: #60a5fa;
}

</style>
