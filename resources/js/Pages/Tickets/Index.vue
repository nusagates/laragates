<script setup>
/* === LOGIC TETAP, HANYA DIRAPIKAN === */
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'

const page    = usePage()
const tickets = ref(page.props.tickets || [])
const counts  = ref(page.props.counts || {})
const agents  = ref(page.props.agents || [])
const filters = ref(page.props.filters || { status: 'all', q: '' })

// Sidebar state
const search       = ref(filters.value.q || '')
const statusFilter = ref(filters.value.status || 'all')

// Ticket aktif
const activeTicketId = ref(tickets.value[0]?.id || null)
const activeTicket   = ref(null)
const messages       = ref([])

// Form reply
const replyText    = ref('')
const loadingReply = ref(false)
const loadingTicket = ref(false)

// Warna status badge
const badgeColor = (status) => {
  if (status === 'pending') return 'orange'
  if (status === 'ongoing') return 'blue'
  if (status === 'closed')  return 'green'
  return 'grey'
}

// Filter ticket (front-end, sync sama backend)
const filteredTickets = computed(() => {
  let data = tickets.value

  if (statusFilter.value && statusFilter.value !== 'all') {
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

// Load detail ticket
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

// Kirim balasan
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

// Update status ticket
async function updateStatus(newStatus) {
  if (!activeTicketId.value) return

  await axios.post(`/tickets/${activeTicketId.value}/status`, {
    status: newStatus,
  })

  if (activeTicket.value) {
    activeTicket.value.status = newStatus
  }

  await refreshTicketsList()
}

// Assign agent
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

// Refresh list di sidebar setelah ada perubahan
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

// Init
onMounted(() => {
  if (activeTicketId.value) {
    loadTicket(activeTicketId.value)
  }
})

// Sinkron kalau ada props baru dari Inertia
watch(
  () => page.props.tickets,
  val => { if (val) tickets.value = val },
)
</script>

<template>
  <Head title="Tickets" />

  <AdminLayout>
    <template #title>Tickets</template>

    <div class="tickets-flex">
      <!-- ================= SIDEBAR ================= -->
      <div class="tickets-sidebar">
        <v-card class="tickets-sidebar-card" elevation="1">
          <!-- Search -->
          <v-text-field
            v-model="search"
            placeholder="Search ticket..."
            density="compact"
            variant="solo"
            flat
            hide-details
            rounded
            clearable
            prepend-inner-icon="mdi-magnify"
            @keyup.enter="doSearch"
            class="tickets-search"
          />

          <!-- Filter status -->
          <v-chip-group
            v-model="statusFilter"
            mandatory
            class="tickets-chip-group"
            @update:modelValue="changeStatusFilter"
          >
            <v-chip value="all" filter>
              All ({{ counts.all ?? 0 }})
            </v-chip>
            <v-chip value="pending" filter color="orange">
              Pending ({{ counts.pending ?? 0 }})
            </v-chip>
            <v-chip value="ongoing" filter color="blue">
              Ongoing ({{ counts.ongoing ?? 0 }})
            </v-chip>
            <v-chip value="closed" filter color="green">
              Closed ({{ counts.closed ?? 0 }})
            </v-chip>
          </v-chip-group>

          <v-divider />

          <!-- List tiket -->
          <div class="tickets-scroll">
            <v-list density="compact">
              <v-list-item
                v-for="t in filteredTickets"
                :key="t.id"
                :class="{ 'active-ticket': t.id === activeTicketId }"
                class="tickets-item"
                @click="openTicket(t.id)"
              >
                <div class="ticket-line">
                  <span class="ticket-name">{{ t.customer_name }}</span>
                  <small class="ticket-time">{{ t.last_message_at }}</small>
                </div>
                <div class="ticket-sub">
                  <span class="ticket-subject">
                    {{ t.subject }}
                  </span>
                  <v-chip
                    :color="badgeColor(t.status)"
                    size="x-small"
                    label
                    class="ticket-badge text-capitalize"
                  >
                    {{ t.status }}
                  </v-chip>
                </div>
              </v-list-item>
            </v-list>

            <div v-if="!filteredTickets.length" class="no-tickets">
              No tickets
            </div>
          </div>
        </v-card>
      </div>

      <!-- ================= DETAIL / CHAT ================= -->
      <div class="tickets-detail">
        <v-card class="tickets-window" elevation="1">
          <div
            v-if="!activeTicketId"
            class="no-select"
          >
            Select a ticket to view detail
          </div>

          <template v-else>
            <!-- Header detail -->
            <div
              v-if="activeTicket"
              class="tickets-header"
            >
              <div>
                <h3 class="ticket-title">
                  {{ activeTicket.customer_name }}
                </h3>
                <div class="ticket-desc">
                  {{ activeTicket.subject }}
                </div>
              </div>

              <div class="ticket-actions">
                <v-select
                  :items="[
                    { value: 'pending', title: 'Pending' },
                    { value: 'ongoing', title: 'Ongoing' },
                    { value: 'closed',  title: 'Closed'  },
                  ]"
                  v-model="activeTicket.status"
                  density="compact"
                  variant="outlined"
                  hide-details
                  class="mr-2"
                  style="max-width: 140px"
                  @update:modelValue="updateStatus"
                />

                <v-select
                  :items="agents.map(a => ({ value: a.id, title: a.name }))"
                  :model-value="activeTicket.assigned_to"
                  density="compact"
                  variant="outlined"
                  hide-details
                  clearable
                  label="Assign"
                  style="max-width: 180px"
                  @update:modelValue="assignAgent"
                />
              </div>
            </div>

            <v-divider />

            <!-- Body chat -->
            <div
              v-if="!loadingTicket"
              class="tickets-body"
            >
              <div
                v-for="m in messages"
                :key="m.id"
                :class="['bubble-wrapper', m.sender_type]"
              >
                <div class="bubble-meta">
                  {{ m.sender_name ?? m.sender_type }} · {{ m.time }}
                </div>

                <div
                  class="bubble"
                  :class="m.sender_type"
                >
                  {{ m.message }}
                </div>
              </div>

              <div
                v-if="!messages.length"
                class="no-messages"
              >
                No messages yet
              </div>
            </div>

            <div
              v-else
              class="tickets-body loading-center"
            >
              Loading...
            </div>

            <v-divider />

            <!-- Input reply -->
            <div class="tickets-input">
              <v-textarea
                v-model="replyText"
                placeholder="Type a reply..."
                rows="1"
                auto-grow
                variant="outlined"
                hide-details
                class="reply-text"
              />
              <v-btn
                color="primary"
                :loading="loadingReply"
                @click="sendReply"
                class="reply-btn"
              >
                SEND
              </v-btn>
            </div>
          </template>
        </v-card>
      </div>
    </div>
  </AdminLayout>
</template>

<style scoped>
/* Layout utama */
.tickets-flex {
  height: calc(100vh - 120px);
  display: flex;
  gap: 12px;
}

.tickets-sidebar {
  flex: 0 0 26%;
  max-width: 26%;
}

.tickets-detail {
  flex: 1;
}

/* Sidebar card */
.tickets-sidebar-card {
  height: 100%;
  display: flex;
  flex-direction: column;
  padding: 10px;
}

.tickets-search {
  margin-bottom: 6px;
}

.tickets-search input {
  font-size: 13px !important;
}

/* Chip filter */
.tickets-chip-group {
  margin: 4px 0 8px;
}

.tickets-chip-group .v-chip {
  font-size: 11px !important;
}

/* List tiket */
.tickets-scroll {
  flex: 1;
  overflow-y: auto;
  padding-right: 4px;
}

.tickets-item {
  cursor: pointer !important;
  padding: 8px 6px !important;
  border-radius: 6px;
}

.tickets-item:hover {
  background: rgba(0, 0, 0, 0.04);
}

.active-ticket {
  background-color: rgba(25, 118, 210, 0.1) !important;
  border-left: 3px solid #1976d2;
}

/* Isi item tiket */
.ticket-line {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.ticket-name {
  font-weight: 600;
  font-size: 14px;
}

.ticket-time {
  font-size: 11px;
  color: #888;
}

.ticket-sub {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 2px;
}

.ticket-subject {
  font-size: 12px;
  max-width: 75%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.ticket-badge {
  font-size: 10px;
}

.no-tickets {
  padding: 16px;
  text-align: center;
  font-size: 12px;
  color: #777;
}

/* Detail window */
.tickets-window {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.no-select {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #777;
  font-size: 14px;
}

/* Header detail */
.tickets-header {
  padding: 12px 18px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.ticket-title {
  margin: 0;
  font-size: 17px;
  font-weight: 600;
}

.ticket-desc {
  font-size: 14px;
  color: #777;
}

.ticket-actions {
  display: flex;
  align-items: center;
}

/* Body chat */
.tickets-body {
  flex: 1;
  padding: 10px 20px;
  overflow-y: auto;
  background: #f8fafc;
}

.loading-center {
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Bubble chat */
.bubble-wrapper {
  margin-bottom: 10px;
  max-width: 78%;
}

.bubble-wrapper.agent {
  margin-left: auto;
  text-align: right;
}

.bubble-meta {
  font-size: 11px;
  margin-bottom: 2px;
  color: #666;
}

.bubble {
  display: inline-block;
  padding: 8px 12px;
  border-radius: 16px;
  white-space: pre-wrap;
  font-size: 13px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.bubble.agent {
  background: #d1f2d6;
  border-radius: 14px 14px 4px 14px;
}

.bubble.customer {
  background: #ffffff;
  border-radius: 14px 14px 14px 4px;
}

.bubble.system {
  background: #e0e0e0;
  font-style: italic;
  border-radius: 12px;
}

.no-messages {
  color: #777;
  text-align: center;
  margin-top: 20px;
  font-size: 13px;
}

/* Input reply */
.tickets-input {
  padding: 10px 14px;
  display: flex;
  gap: 10px;
  background: #fff;
}

.reply-text {
  font-size: 13px;
}

.reply-btn {
  height: 38px;
  align-self: flex-end;
}

/* Sedikit responsif untuk layar kecil */
@media (max-width: 1024px) {
  .tickets-flex {
    flex-direction: column;
    height: auto;
  }

  .tickets-sidebar,
  .tickets-detail {
    max-width: 100%;
    flex: 1 1 auto;
  }

  .tickets-window {
    min-height: 400px;
  }
}
</style>
