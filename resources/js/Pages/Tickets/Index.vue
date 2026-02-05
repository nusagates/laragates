<script setup>
/* ===============================
   LOGIC ASLI — TIDAK DIUBAH
=============================== */
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'

const page = usePage()

const tickets = ref(page.props.tickets || [])
const counts  = ref(page.props.counts || {})
const agents  = ref(page.props.agents || [])
const filters = ref(page.props.filters || { status: 'all', q: '' })

const search       = ref(filters.value.q || '')
const statusFilter = ref(filters.value.status || 'all')

const activeTicketId = ref(tickets.value[0]?.id || null)
const activeTicket   = ref(null)
const messages       = ref([])

const replyText     = ref('')
const loadingReply  = ref(false)
const loadingTicket = ref(false)

/* ===============================
   CREATE TICKET STATE
=============================== */
const showCreate = ref(false)

const newTicket = ref({
  customer_name: '',
  customer_phone: '', // ✅ TAMBAHAN
  subject: '',
  priority: 'medium',
  channel: 'other',
})

const filteredTickets = computed(() => {
  let data = tickets.value

  if (statusFilter.value !== 'all') {
    data = data.filter(t => t.status === statusFilter.value)
  }

  if (search.value) {
    const q = search.value.toLowerCase()
    data = data.filter(t =>
      t.customer_name.toLowerCase().includes(q) ||
      t.subject.toLowerCase().includes(q)
    )
  }

  return data
})

function changeStatusFilter(v) {
  statusFilter.value = v
  router.get('/tickets', { status: v, q: search.value }, {
    preserveState: true,
    replace: true,
    onSuccess: res => {
      tickets.value = res.props.tickets
      counts.value  = res.props.counts
    },
  })
}

function doSearch() {
  router.get('/tickets', { status: statusFilter.value, q: search.value }, {
    preserveState: true,
    replace: true,
    onSuccess: res => {
      tickets.value = res.props.tickets
      counts.value  = res.props.counts
    },
  })
}

/* ===============================
   CREATE TICKET (STANDALONE)
=============================== */
async function createTicket() {
  if (!newTicket.value.customer_name || !newTicket.value.subject) return

  try {
    const res = await axios.post('/tickets', {
      customer_name: newTicket.value.customer_name,
      customer_phone: newTicket.value.customer_phone || null, // ✅ KIRIM KE DB
      subject: newTicket.value.subject,
      priority: newTicket.value.priority,
      channel: newTicket.value.channel,
    })

    showCreate.value = false
    newTicket.value = {
      customer_name: '',
      customer_phone: '',
      subject: '',
      priority: 'medium',
      channel: 'other',
    }

    await refreshTicketsList()
    openTicket(res.data.id)
  } catch {
    alert('Failed to create ticket')
  }
}

async function loadTicket(id) {
  if (!id) return
  loadingTicket.value = true
  activeTicketId.value = id

  try {
    const res = await axios.get(`/tickets/${id}`)
    activeTicket.value = res.data.ticket
    messages.value     = res.data.messages
  } finally {
    loadingTicket.value = false
  }
}

function openTicket(id) {
  loadTicket(id)
}

async function sendReply() {
  if (!replyText.value.trim() || !activeTicketId.value) return
  if (activeTicket.value?.status === 'closed') return

  loadingReply.value = true
  const text = replyText.value
  replyText.value = ''

  try {
    const res = await axios.post(`/tickets/${activeTicketId.value}/reply`, {
      message: text,
    })
    messages.value.push(res.data)
    await refreshTicketsList()
  } finally {
    loadingReply.value = false
  }
}

async function updateStatus(status) {
  if (!activeTicketId.value) return
  await axios.post(`/tickets/${activeTicketId.value}/status`, { status })
  if (activeTicket.value) activeTicket.value.status = status
  await refreshTicketsList()
}

async function assignAgent(userId) {
  if (!activeTicketId.value) return

  await axios.post(`/tickets/${activeTicketId.value}/assign`, {
    assigned_to: userId || null,
  })

  const a = agents.value.find(x => x.id === userId)
  if (activeTicket.value) {
    activeTicket.value.assigned_to   = userId
    activeTicket.value.assigned_name = a ? a.name : null
  }

  await refreshTicketsList()
}

async function refreshTicketsList() {
  await router.get('/tickets', {
    status: statusFilter.value,
    q: search.value,
  }, {
    preserveState: true,
    replace: true,
    only: ['tickets', 'counts'],
    onSuccess: res => {
      tickets.value = res.props.tickets
      counts.value  = res.props.counts
    },
  })
}

onMounted(() => {
  if (activeTicketId.value) loadTicket(activeTicketId.value)
})

watch(() => page.props.tickets, v => {
  if (v) tickets.value = v
})
</script>

<template>
  <Head title="Tickets" />

  <AdminLayout>
    <template #title>Tickets</template>

    <div class="tickets-root">

      <!-- SIDEBAR -->
      <aside class="tickets-sidebar">
        <div class="sidebar-top">
          <v-text-field
            v-model="search"
            placeholder="Search ticket..."
            density="compact"
            variant="solo"
            flat
            hide-details
            prepend-inner-icon="mdi-magnify"
            class="search"
            @keyup.enter="doSearch"
          />

          <!-- ➕ BUTTON CREATE -->
          <v-btn
            color="primary"
            block
            class="mt-3"
            @click="showCreate = true"
          >
            + New Ticket
          </v-btn>

          <v-chip-group
            v-model="statusFilter"
            mandatory
            class="filters"
            @update:modelValue="changeStatusFilter"
          >
            <v-chip value="all">All</v-chip>
            <v-chip value="pending">Pending</v-chip>
            <v-chip value="ongoing">Ongoing</v-chip>
            <v-chip value="closed">Closed</v-chip>
          </v-chip-group>
        </div>

        <div class="tickets-list">
          <div
            v-for="t in filteredTickets"
            :key="t.id"
            class="ticket"
            :class="{ active: t.id === activeTicketId }"
            @click="openTicket(t.id)"
          >
            <div class="line">
              <span class="name">{{ t.customer_name }}</span>
              <span class="time">{{ t.last_message_at }}</span>
            </div>
            <div class="line">
              <span class="subject">{{ t.subject }}</span>
              <span class="status" :class="t.status">{{ t.status }}</span>
            </div>
          </div>
        </div>
      </aside>

      <!-- CHAT -->
      <section class="tickets-chat">
        <div v-if="!activeTicketId" class="no-ticket">
          Select a ticket
        </div>

        <template v-else>
          <header class="chat-header" v-if="activeTicket">
            <div class="info">
              <div class="title">
                {{ activeTicket.customer_name }}
                <span class="badge" :class="activeTicket.status">
                  {{ activeTicket.status }}
                </span>
              </div>
              <div class="subtitle">
                {{ activeTicket.subject }}
                <span v-if="activeTicket.assigned_name">
                  · Assigned to {{ activeTicket.assigned_name }}
                </span>
              </div>
            </div>

            <div class="controls">
              <v-select
                density="compact"
                variant="outlined"
                hide-details
                class="control"
                :items="[
                  { title:'Pending', value:'pending' },
                  { title:'Ongoing', value:'ongoing' },
                  { title:'Closed', value:'closed' },
                ]"
                v-model="activeTicket.status"
                @update:modelValue="updateStatus"
              />

              <v-select
                density="compact"
                variant="outlined"
                hide-details
                clearable
                class="control"
                label="Assign"
                :items="agents.map(a => ({ title:a.name, value:a.id }))"
                :model-value="activeTicket.assigned_to"
                @update:modelValue="assignAgent"
              />
            </div>
          </header>

          <div class="chat-body">
            <div
              v-for="m in messages"
              :key="m.id"
              class="bubble"
              :class="m.sender_type"
            >
              <div class="meta">
                {{ m.sender_name ?? m.sender_type }} · {{ m.time }}
              </div>
              <div class="text">{{ m.message }}</div>
            </div>
          </div>

          <footer class="chat-input">
            <v-textarea
              v-model="replyText"
              auto-grow
              rows="1"
              hide-details
              placeholder="Type a reply..."
              :disabled="activeTicket?.status === 'closed'"
            />
            <v-btn
              color="primary"
              :disabled="activeTicket?.status === 'closed'"
              @click="sendReply"
            >
              SEND
            </v-btn>
          </footer>
        </template>
      </section>
    </div>

    <!-- CREATE TICKET MODAL -->
    <v-dialog v-model="showCreate" max-width="420">
      <v-card>
        <v-card-title>Create New Ticket</v-card-title>
        <v-card-text>
          <v-text-field label="Customer Name" v-model="newTicket.customer_name" />
          <v-text-field label="Customer Phone" v-model="newTicket.customer_phone" />
          <v-text-field label="Subject" v-model="newTicket.subject" />
          <v-select
            label="Priority"
            :items="['low','medium','high']"
            v-model="newTicket.priority"
          />
          <v-select
            label="Channel"
            :items="['whatsapp','email','phone','other']"
            v-model="newTicket.channel"
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn text @click="showCreate = false">Cancel</v-btn>
          <v-btn color="primary" @click="createTicket">Create</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </AdminLayout>
</template>

<style scoped>
/* ===============================
   ROOT LAYOUT
=============================== */
.tickets-root {
  height: calc(100vh - 120px);
  display: grid;
  grid-template-columns: 320px 1fr;
  gap: 14px;
  color: #e5e7eb;
}

/* ===============================
   SIDEBAR (CHAT STYLE)
=============================== */
.tickets-sidebar {
  background: linear-gradient(180deg, #020617, #020617);
  border-radius: 16px;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.sidebar-top {
  padding: 14px;
  border-bottom: 1px solid rgba(255,255,255,.06);
  display: flex;
  flex-direction: column;
  gap: 10px;
}

/* SEARCH */
.search .v-field {
  background: rgba(148,163,184,.12) !important;
  border-radius: 12px;
}

.search input {
  color: #f8fafc !important;
}

.search input::placeholder {
  color: #94a3b8 !important;
}

/* ===============================
   TICKET LIST (CHAT LIST FEEL)
=============================== */
.tickets-list {
  flex: 1;
  overflow-y: auto;
}

.ticket {
  padding: 12px 14px;
  border-bottom: 1px solid rgba(255,255,255,.04);
  cursor: pointer;
  transition: background .15s ease;
}

.ticket:hover {
  background: rgba(255,255,255,.04);
}

.ticket.active {
  background: rgba(59,130,246,.18);
  border-left: 3px solid #3b82f6;
}

.line {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.name {
  font-weight: 600;
  font-size: 14px;
}

.time {
  font-size: 11px;
  color: #94a3b8;
}

.subject {
  font-size: 12px;
  color: #cbd5f5;
  max-width: 75%;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.status {
  font-size: 10px;
  padding: 2px 8px;
  border-radius: 999px;
  text-transform: capitalize;
}

.status.pending {
  color: #fb923c;
  background: rgba(249,115,22,.15);
}

.status.ongoing {
  color: #60a5fa;
  background: rgba(59,130,246,.18);
}

.status.closed {
  color: #4ade80;
  background: rgba(34,197,94,.18);
}

/* ===============================
   CHAT PANEL
=============================== */
.tickets-chat {
  background: radial-gradient(circle at top, #0f172a, #020617);
  border-radius: 16px;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* HEADER */
.chat-header {
  height: 60px;
  padding: 10px 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid rgba(255,255,255,.06);
}

.chat-header .info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.chat-header .title {
  font-weight: 600;
  font-size: 14px;
}

.chat-header .subtitle {
  font-size: 12px;
  color: #94a3b8;
}

.controls {
  display: flex;
  gap: 10px;
}

.control {
  min-width: 130px;
}

/* BADGE */
.badge {
  font-size: 10px;
  margin-left: 8px;
  padding: 2px 8px;
  border-radius: 999px;
  text-transform: capitalize;
}

.badge.pending {
  color: #fb923c;
  background: rgba(249,115,22,.15);
}

.badge.ongoing {
  color: #60a5fa;
  background: rgba(59,130,246,.18);
}

.badge.closed {
  color: #4ade80;
  background: rgba(34,197,94,.18);
}

/* ===============================
   CHAT BODY
=============================== */
.chat-body {
  flex: 1;
  padding: 18px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
}

/* ===============================
   CHAT BUBBLE
=============================== */
.bubble {
  max-width: 64%;
  margin-bottom: 14px;
  word-break: break-word;
}

.bubble.agent {
  align-self: flex-end;
  text-align: right;
}

.bubble.customer {
  align-self: flex-start;
}

.bubble .meta {
  font-size: 11px;
  color: #94a3b8;
  margin-bottom: 4px;
}

.bubble .text {
  padding: 10px 14px;
  border-radius: 16px;
  font-size: 13px;
  line-height: 1.5;
}

/* Agent bubble */
.bubble.agent .text {
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  color: #fff;
}

/* Customer bubble */
.bubble.customer .text {
  background: rgba(255,255,255,.08);
  color: #e5e7eb;
}

/* ===============================
   CHAT INPUT
=============================== */
.chat-input {
  padding: 12px 14px;
  display: flex;
  gap: 10px;
  border-top: 1px solid rgba(255,255,255,.06);
  background: linear-gradient(
    180deg,
    rgba(2,6,23,.9),
    rgba(2,6,23,1)
  );
}

/* ===============================
   EMPTY STATE
=============================== */
.no-ticket {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #64748b;
  font-size: 13px;
}

</style>
