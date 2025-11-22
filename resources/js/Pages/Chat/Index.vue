<script setup>
import { ref, computed, onMounted } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head } from '@inertiajs/vue3'
import ChatRoom from './Room.vue'
import axios from 'axios'

/* ====== DATA ROOM DARI BACKEND ====== */
const rooms = ref([])
const query = ref('')
const activeRoomId = ref(null)
const tempMessage = ref('')

/* ===== MODAL NEW CHAT ===== */
const newChatDialog = ref(false)
const newChatLoading = ref(false)
const newChatForm = ref({
  name: '',
  phone: '',
  message: '',
  create_ticket: false,
})

/* ===== Fetch Rooms ===== */
async function loadRooms() {
  const res = await axios.get('/api/chats')
  rooms.value = res.data
  if (!activeRoomId.value && rooms.value.length) {
    activeRoomId.value = rooms.value[0].session_id
  }
}

onMounted(() => {
  loadRooms()
})

/* ===== Filter Search ===== */
const filtered = computed(() => {
  if (!query.value) return rooms.value
  return rooms.value.filter(r =>
    r.customer_name.toLowerCase().includes(query.value.toLowerCase())
  )
})

/* ===== Ganti Room ===== */
function openRoom(id) {
  activeRoomId.value = id
}

/* ===== KIRIM PESAN DI ROOM AKTIF ===== */
async function sendMessage() {
  if (!tempMessage.value.trim() || !activeRoomId.value) return

  const text = tempMessage.value
  tempMessage.value = ''

  try {
    const res = await axios.post(`/api/chats/${activeRoomId.value}/send`, {
      message: text,
    })

    // Push pesan langsung ke Room.vue
    window.dispatchEvent(
      new CustomEvent('agent-message-sent', {
        detail: res.data,
      })
    )
  } catch (err) {
    console.log(err)
    alert('Gagal mengirim pesan!')
  }
}

/* ====== NEW CHAT LOGIC ====== */

/**
 * Format nomor saat user mengetik:
 * 0812xxxx -> +62812xxxx
 */
function formatPhone() {
  let val = newChatForm.value.phone || ''

  // hapus spasi
  val = val.trim()

  if (!val) {
    newChatForm.value.phone = ''
    return
  }

  // buang semua kecuali angka & +
  val = val.replace(/[^0-9+]/g, '')

  // sudah + di depan, biarin
  if (val.startsWith('+')) {
    newChatForm.value.phone = val
    return
  }

  // 0xxxx -> +62xxxx
  if (val.startsWith('0')) {
    newChatForm.value.phone = '+62' + val.slice(1)
    return
  }

  // 62xxxx -> +62xxxx
  if (val.startsWith('62')) {
    newChatForm.value.phone = '+' + val
    return
  }

  // default: tambah +
  newChatForm.value.phone = '+' + val
}

/**
 * Open New Chat modal
 */
function openNewChat() {
  newChatForm.value = {
    name: '',
    phone: '',
    message: '',
    create_ticket: false,
  }
  newChatDialog.value = true
}

/**
 * Submit New Chat
 */
async function submitNewChat() {
  if (!newChatForm.value.phone || !newChatForm.value.message) {
    alert('Phone dan message wajib diisi.')
    return
  }

  newChatLoading.value = true

  try {
    // pastikan sudah terformat
    formatPhone()

    const res = await axios.post('/api/chats/outbound', {
      name: newChatForm.value.name || null,
      phone: newChatForm.value.phone,
      message: newChatForm.value.message,
      create_ticket: newChatForm.value.create_ticket,
    })

    // Tutup modal
    newChatDialog.value = false

    // Reload list chat, lalu set active ke session baru
    await loadRooms()

    if (res.data?.session_id) {
      activeRoomId.value = res.data.session_id
    }
  } catch (e) {
    console.error(e)
    alert('Gagal membuat chat baru.')
  } finally {
    newChatLoading.value = false
  }
}
</script>

<template>
  <Head title="Chat" />

  <AdminLayout>
    <template #title>Chat</template>

    <div class="chat-flex">
      <!-- ===== SIDEBAR ===== -->
      <div class="chat-sidebar">
        <v-card class="pa-4 chat-list" elevation="2">
          <div class="d-flex align-center mb-3">
            <v-text-field
              v-model="query"
              placeholder="Search customer or phone..."
              density="comfortable"
              rounded
              hide-details
              prepend-inner-icon="mdi-magnify"
              class="flex-grow-1 mr-2"
            />
            <v-btn
              color="primary"
              icon
              title="New Chat"
              @click="openNewChat"
            >
              <v-icon>mdi-message-plus</v-icon>
            </v-btn>
          </div>

          <v-divider />

          <v-list two-line density="comfortable">
            <v-list-item
              v-for="room in filtered"
              :key="room.session_id"
              @click="openRoom(room.session_id)"
              :class="{ 'bg-highlight': room.session_id === activeRoomId }"
              style="cursor: pointer;"
            >
              <v-list-item-avatar>
                <v-avatar color="blue">
                  <span class="white--text">
                    {{ room.customer_name.charAt(0) }}
                  </span>
                </v-avatar>
              </v-list-item-avatar>

              <v-list-item-content>
                <v-list-item-title
                  class="d-flex justify-space-between align-center"
                >
                  <span>{{ room.customer_name }}</span>
                  <small class="text-body-2 text-grey">{{ room.time }}</small>
                </v-list-item-title>
                <v-list-item-subtitle class="text-truncate text-body-2">
                  {{ room.last_message }}
                </v-list-item-subtitle>
              </v-list-item-content>

              <v-list-item-action>
                <v-badge
                  v-if="room.unread_count"
                  :content="room.unread_count"
                  color="red"
                />
              </v-list-item-action>
            </v-list-item>

            <v-list-item v-if="!filtered.length">
              <v-list-item-title class="text-center text-grey">
                No chats
              </v-list-item-title>
            </v-list-item>
          </v-list>
        </v-card>
      </div>

      <!-- ===== CHAT ROOM ===== -->
      <div class="chat-room">
        <v-card class="chat-window" elevation="2">
          <div class="chat-body">
            <ChatRoom :room-id="activeRoomId" />
          </div>

          <!-- INPUT -->
          <div class="chat-input">
            <v-row align="center" class="no-gutters">
              <v-col cols="auto">
                <v-btn icon>
                  <v-icon>mdi-emoticon-outline</v-icon>
                </v-btn>
              </v-col>

              <v-col>
                <v-textarea
                  v-model="tempMessage"
                  placeholder="Type a message"
                  rows="1"
                  auto-grow
                  variant="outlined"
                  class="me-2"
                />
              </v-col>

              <v-col cols="auto">
                <v-btn color="primary" @click="sendMessage">SEND</v-btn>
              </v-col>
            </v-row>
          </div>
        </v-card>
      </div>
    </div>

    <!-- ===== NEW CHAT DIALOG ===== -->
    <v-dialog v-model="newChatDialog" max-width="480">
      <v-card class="pa-4">
        <h3 class="text-h6 mb-3">New Chat</h3>
        <p class="text-body-2 text-grey-darken-1 mb-4">
          Kirim pesan WhatsApp pertama ke customer baru atau existing.
        </p>

        <v-text-field
          v-model="newChatForm.name"
          label="Customer Name (optional)"
          density="comfortable"
          class="mb-3"
        />

        <v-text-field
          v-model="newChatForm.phone"
          label="WhatsApp Number"
          density="comfortable"
          placeholder="0812xxxx / +62812xxxx"
          class="mb-3"
          @blur="formatPhone"
        />

        <v-textarea
          v-model="newChatForm.message"
          label="First Message"
          rows="2"
          auto-grow
          density="comfortable"
          variant="outlined"
          class="mb-3"
        />

        <v-checkbox
          v-model="newChatForm.create_ticket"
          label="Create Ticket from this chat"
          density="comfortable"
          class="mb-2"
        />

        <v-divider class="my-3" />

        <div class="d-flex justify-end gap-2">
          <v-btn variant="text" @click="newChatDialog = false">
            Cancel
          </v-btn>
          <v-btn
            color="primary"
            :loading="newChatLoading"
            @click="submitNewChat"
          >
            Start Chat
          </v-btn>
        </div>
      </v-card>
    </v-dialog>
  </AdminLayout>
</template>

<style scoped>
.chat-flex {
  height: calc(100vh - 120px);
  display: flex;
  gap: 16px;
}
.chat-sidebar {
  flex: 0 0 27%;
  max-width: 27%;
}
.chat-list {
  height: 100%;
  overflow-y: auto;
  border-radius: 12px;
  background: #fff;
}
.chat-room {
  flex: 1;
  max-width: 73%;
}
.chat-window {
  height: 100%;
  display: flex;
  flex-direction: column;
  border-radius: 12px;
  overflow: hidden;
}
.chat-body {
  flex: 1;
  overflow-y: auto;
  padding: 25px 30px;
  background: #f4f7fb;
}
.chat-input {
  border-top: 1px solid rgba(0, 0, 0, 0.06);
  padding: 14px 18px;
  background: #fff;
}
.bg-highlight {
  background-color: rgba(25, 118, 210, 0.08) !important;
  border-left: 4px solid #1976d2;
}
</style>
