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
              <div class="title">{{ activeTicket.customer_name }}</div>
              <div class="subtitle">{{ activeTicket.subject }}</div>
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
            />
            <v-btn color="primary" @click="sendReply">
              SEND
            </v-btn>
          </footer>
        </template>
      </section>

    </div>
  </AdminLayout>
</template>

<style scoped>
/* ROOT */
.tickets-root {
  height: calc(100vh - 120px);
  display: grid;
  grid-template-columns: 300px 1fr;
  gap: 14px;
  color: #e5e7eb;
}

/* SIDEBAR */
.tickets-sidebar {
  background: #020617;
  border-radius: 14px;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.sidebar-top {
  padding: 12px;
  border-bottom: 1px solid rgba(255,255,255,.06);
}

/* SEARCH FIX */
.search .v-field {
  background: rgba(148,163,184,.12) !important;
  border-radius: 10px;
}

.search input {
  color: #f8fafc !important;
}

.search input::placeholder {
  color: #94a3b8 !important;
}

/* LIST */
.tickets-list {
  flex: 1;
  overflow-y: auto;
}

.ticket {
  padding: 12px;
  border-bottom: 1px solid rgba(255,255,255,.05);
  cursor: pointer;
}

.ticket.active {
  background: rgba(59,130,246,.18);
  border-left: 3px solid #3b82f6;
}

.line {
  display: flex;
  justify-content: space-between;
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
  max-width: 70%;
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

.status.pending { color:#fb923c; background:rgba(249,115,22,.15) }
.status.ongoing { color:#60a5fa; background:rgba(59,130,246,.18) }
.status.closed  { color:#4ade80; background:rgba(34,197,94,.18) }

/* CHAT */
.tickets-chat {
  background: #020617;
  border-radius: 14px;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.chat-header {
  height: 56px;
  padding: 8px 14px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid rgba(255,255,255,.06);
}

.controls {
  display: flex;
  gap: 8px;
}

.control {
  min-width: 130px;
}

/* BODY */
.chat-body {
  flex: 1;
  padding: 16px;
  overflow-y: auto;
}

/* BUBBLE FIX */
.bubble {
  max-width: 52%;
  margin-bottom: 12px;
  word-break: break-word;
}

.bubble.agent {
  margin-left: auto;
  text-align: right;
}

.bubble .meta {
  font-size: 11px;
  color: #94a3b8;
  margin-bottom: 3px;
}

.bubble .text {
  padding: 8px 12px;
  border-radius: 14px;
  font-size: 13px;
}

.bubble.agent .text {
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  color: #fff;
}

.bubble.customer .text {
  background: rgba(255,255,255,.08);
}

/* INPUT */
.chat-input {
  padding: 10px 12px;
  display: flex;
  gap: 10px;
  border-top: 1px solid rgba(255,255,255,.06);
}
</style>
