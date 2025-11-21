<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

// ====================== DUMMY TICKETS ======================
const tickets = ref([
  { id: 1, customer: "John Doe", subject: "Pesanan belum diterima", status: "ongoing", time: "10:20" },
  { id: 2, customer: "Aldi Widakdo", subject: "Minta Invoice Pembayaran", status: "pending", time: "09:38" },
  { id: 3, customer: "Michelle", subject: "Barang saya salah", status: "closed", time: "Kemarin" },
])

// ====================== DUMMY MESSAGES ======================
const messages = ref([
  { id: 1, type: "customer", name: "John Doe", text: "Halo kak, pesanan saya belum datang", at: "10:15" },
  { id: 2, type: "agent", name: "Agent Bale", text: "Baik kak, saya cek dulu ya 🙏", at: "10:17" },
])

// ====================== STATE ======================
const query = ref("")
const activeTicketId = ref(tickets.value[0].id)
const replyMessage = ref("")
const showAssign = ref(false)
const showDetail = ref(false)
const showStatus = ref(false)
const selectedStatus = ref(null)

// ====================== FILTER & FUNCTIONS ======================
const filtered = computed(() => {
  if (!query.value) return tickets.value
  return tickets.value.filter(t =>
    t.customer.toLowerCase().includes(query.value.toLowerCase())
  )
})

function openTicket(id) {
  activeTicketId.value = id
}

function sendReply() {
  if (!replyMessage.value.trim()) return
  messages.value.push({
    id: Date.now(),
    type: "agent",
    name: "You",
    text: replyMessage.value,
    at: "Now"
  })
  replyMessage.value = ""
}

function changeTicketStatus(status) {
  const ticket = tickets.value.find(t => t.id === activeTicketId.value)
  if (ticket) ticket.status = status
  showStatus.value = false
}

function assignAgent(agent) {
  console.log("Assign to:", agent)
  showAssign.value = false
}
</script>

<template>
  <Head title="Tickets" />

  <AdminLayout>
    <template #title>Tickets</template>

    <div class="ticket-flex">
      <!-- ===================== SIDEBAR ===================== -->
      <div class="ticket-sidebar">
        <v-card class="pa-4 scroll-box" elevation="2">
          <v-text-field
            v-model="query"
            placeholder="Search ticket..."
            prepend-inner-icon="mdi-magnify"
            hide-details
            density="comfortable"
            rounded
            class="mb-3"
          />

          <v-divider></v-divider>

          <v-list>
            <v-list-item
              v-for="t in filtered"
              :key="t.id"
              @click="openTicket(t.id)"
              :class="{ 'bg-active': t.id === activeTicketId }"
              style="cursor: pointer;"
            >
              <v-list-item-content>
                <div class="d-flex justify-space-between">
                  <span class="font-weight-medium">{{ t.customer }}</span>
                  <small>{{ t.time }}</small>
                </div>
                <div class="text-grey-darken-1 text-caption">{{ t.subject }}</div>
                <v-chip
                  size="x-small"
                  class="mt-1"
                  :color="t.status === 'pending' ? 'red' : t.status === 'closed' ? 'green' : 'orange'"
                  dark
                >{{ t.status }}</v-chip>
              </v-list-item-content>
            </v-list-item>
          </v-list>
        </v-card>
      </div>

      <!-- ===================== MAIN PANEL ===================== -->
      <div class="ticket-panel">
        <v-card class="ticket-content" elevation="2">

          <!-- ===== HEADER ===== -->
          <div class="ticket-header">
            <div>
              <h3 class="text-h6 font-weight-bold mb-1">
                {{ tickets.find(t => t.id === activeTicketId)?.customer }}
              </h3>
              <span class="text-caption">
                {{ tickets.find(t => t.id === activeTicketId)?.subject }}
              </span>
            </div>

            <div class="d-flex align-center gap-2">
              <v-btn
                color="primary"
                variant="outlined"
                size="small"
                @click="showAssign = true"
                prepend-icon="mdi-account-switch"
              >Assign</v-btn>

              <v-btn
                color="grey"
                variant="outlined"
                size="small"
                @click="showStatus = true"
                prepend-icon="mdi-circle-slice-6"
              >Status</v-btn>

              <v-btn
                icon
                variant="text"
                color="grey"
                @click="showDetail = true"
              ><v-icon>mdi-information-outline</v-icon></v-btn>
            </div>
          </div>

          <!-- ===== MESSAGES ===== -->
          <div class="ticket-body scroll-box">
            <div
              v-for="m in messages"
              :key="m.id"
              class="msg"
              :class="[m.type === 'agent' ? 'agent' : 'customer']"
            >
              <div class="msg-header">
                <v-avatar size="26">
                  <v-icon v-if="m.type === 'agent'">mdi-headset</v-icon>
                  <v-icon v-else>mdi-account</v-icon>
                </v-avatar>
                <span class="name">{{ m.name }}</span>
                <small class="time text-grey-darken-1">{{ m.at }}</small>
              </div>
              <div class="msg-bubble">{{ m.text }}</div>
            </div>
          </div>

          <!-- ===== REPLY ===== -->
          <div class="ticket-reply">
            <v-row no-gutters align="center">
              <v-col>
                <v-textarea
                  v-model="replyMessage"
                  placeholder="Type a reply..."
                  auto-grow
                  rows="1"
                  variant="outlined"
                />
              </v-col>

              <v-col cols="auto" class="d-flex gap-2">
                <v-btn icon><v-icon>mdi-paperclip</v-icon></v-btn>
                <v-btn color="primary" @click="sendReply">Send</v-btn>
              </v-col>
            </v-row>
          </div>
        </v-card>
      </div>
    </div>

    <!-- ===================== ASSIGN MODAL ===================== -->
    <v-dialog v-model="showAssign" width="400">
      <v-card class="pa-4">
        <h3 class="text-h6 mb-3 font-weight-bold">Assign Ticket</h3>
        <v-select
          :items="['Renaldi','Jonathan','Michelle','Aldi']"
          label="Choose Agent"
          prepend-inner-icon="mdi-account-outline"
        />
        <div class="d-flex justify-end mt-4 gap-2">
          <v-btn variant="text" @click="showAssign = false">Cancel</v-btn>
          <v-btn color="primary" @click="assignAgent">Assign</v-btn>
        </div>
      </v-card>
    </v-dialog>

    <!-- ===================== STATUS MODAL ===================== -->
    <v-dialog v-model="showStatus" width="380">
      <v-card class="pa-4">
        <h3 class="text-h6 mb-3 font-weight-bold">Change Status</h3>
        <v-list>
          <v-list-item @click="changeTicketStatus('pending')">Pending</v-list-item>
          <v-list-item @click="changeTicketStatus('ongoing')">On Going</v-list-item>
          <v-list-item @click="changeTicketStatus('closed')">Closed</v-list-item>
        </v-list>
      </v-card>
    </v-dialog>

    <!-- ===================== DETAIL MODAL ===================== -->
    <v-dialog v-model="showDetail" width="420">
      <v-card class="pa-4">
        <h3 class="text-h6 mb-3 font-weight-bold">Customer Details</h3>
        <p>Name: {{ tickets.find(t => t.id === activeTicketId)?.customer }}</p>
        <p>Subject: {{ tickets.find(t => t.id === activeTicketId)?.subject }}</p>
        <p>Status: {{ tickets.find(t => t.id === activeTicketId)?.status }}</p>

        <div class="d-flex justify-end mt-4">
          <v-btn variant="text" @click="showDetail = false">Close</v-btn>
        </div>
      </v-card>
    </v-dialog>
  </AdminLayout>
</template>

<!-- ===================== STYLE ===================== -->
<style scoped>
.ticket-flex { height: calc(100vh - 120px); display: flex; gap: 16px; }
.ticket-sidebar { flex: 0 0 27%; max-width: 27%; }
.ticket-panel { flex: 1; }

.ticket-content { height: 100%; display: flex; flex-direction: column; border-radius: 12px; overflow: hidden; }

.ticket-header { padding: 16px 20px; border-bottom: 1px solid rgba(0,0,0,0.08); display: flex; justify-content: space-between; align-items: center; }

.ticket-body { flex: 1; padding: 20px; background: #f6f8fb; }

.msg { margin-bottom: 14px; }
.msg-header { display: flex; align-items: center; gap: 6px; margin-bottom: 4px; font-size: 13px; }
.msg-bubble { background: white; padding: 10px 14px; border-radius: 12px; max-width: 65%; font-size: 14px; }
.msg.agent .msg-bubble { background:#e3f2fd; margin-left:auto; }
.msg.agent .msg-header { justify-content: flex-end; flex-direction: row-reverse; }
.ticket-reply { border-top: 1px solid rgba(0,0,0,0.08); padding: 12px 16px; background: white; }

.bg-active { background-color: rgba(25,118,210,0.1) !important; border-left: 4px solid #1976d2; }
.scroll-box { overflow-y: auto; border-radius: 12px; }
</style>
