<script setup>
import { ref, computed, onMounted } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head } from '@inertiajs/vue3'
import ChatRoom from './Room.vue'
import axios from 'axios'
import { useToast } from 'vue-toastification'

const toast = useToast()

/* ================= STATE ================= */
const rooms = ref([])
const query = ref('')
const activeRoomId = ref(null)

/* MESSAGE */
const tempMessage = ref('')
const selectedFile = ref(null)
const filePicker = ref(null)
const sendingMessage = ref(false)
const chatRoomRef = ref(null)

/* NEW CHAT */
const newChatDialog = ref(false)
const newChatLoading = ref(false)
const newChatForm = ref({
  name: '',
  phone: '',
  message: '',
})

/* ================= FETCH ROOMS ================= */
async function loadRooms() {
  const res = await axios.get('/chat/sessions')
  rooms.value = res.data
  if (!activeRoomId.value && rooms.value.length) {
    activeRoomId.value = rooms.value[0].session_id
  }
}

onMounted(loadRooms)

/* ================= ACTIVE ROOM ================= */
const activeRoom = computed(() =>
  rooms.value.find(r => r.session_id === activeRoomId.value)
)

/* ================= FILTER ================= */
const filtered = computed(() => {
  if (!query.value) return rooms.value
  return rooms.value.filter(r =>
    (r.customer_name ?? '').toLowerCase().includes(query.value.toLowerCase()) ||
    (r.phone ?? '').includes(query.value)
  )
})

function openRoom(id) {
  activeRoomId.value = id
}

/* ================= SEND MESSAGE ================= */
async function sendMessage() {
  if (!activeRoomId.value) return
  if (!tempMessage.value && !selectedFile.value) {
    toast.warning('Pesan atau file harus diisi')
    return
  }

  const messageText = tempMessage.value
  const file = selectedFile.value
  const mediaUrl = file ? URL.createObjectURL(file) : null

  // Add optimistic message immediately
  if (chatRoomRef.value) {
    chatRoomRef.value.addOptimisticMessage(messageText, mediaUrl)
  }

  const form = new FormData()
  form.append('message', messageText || '')
  if (file) form.append('media', file)

  // Clear input immediately
  tempMessage.value = ''
  selectedFile.value = null
  if (filePicker.value) filePicker.value.value = ''

  sendingMessage.value = true

  try {
    await axios.post(`/chat/sessions/${activeRoomId.value}/messages`, form)
    // Backend will broadcast MessageSent event with real message data
    // WebSocket listener will replace optimistic message
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal mengirim pesan')
    // Restore message on error
    tempMessage.value = messageText
    selectedFile.value = file
  } finally {
    sendingMessage.value = false
  }
}

/* ================= NEW CHAT ================= */
function openNewChat() {
  newChatForm.value = { name: '', phone: '', message: '' }
  newChatDialog.value = true
}

async function submitNewChat() {
  if (!newChatForm.value.phone) {
    toast.error('Nomor WhatsApp wajib diisi')
    return
  }

  newChatLoading.value = true

  try {
    const res = await axios.post('/chat/sessions/outbound', {
      name: newChatForm.value.name || newChatForm.value.phone,
      phone: newChatForm.value.phone,
      message: newChatForm.value.message || '',
      create_ticket: false,
    })

    if (!res.data?.session_id) throw new Error('Invalid response')

    newChatDialog.value = false
    await loadRooms()
    activeRoomId.value = res.data.session_id

    toast.success('Chat berhasil dibuat')
  } catch (e) {
    toast.error('Gagal membuat chat baru')
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

      <!-- SIDEBAR -->
      <aside class="chat-sidebar">
        <div class="chat-list">

          <div class="chat-sidebar-title">DAFTAR CHAT</div>

          <div class="sidebar-search">
            <v-text-field
              v-model="query"
              placeholder="Search chat"
              prepend-inner-icon="mdi-magnify"
              variant="solo"
              density="compact"
              hide-details
              class="search"
            />
            <v-btn icon class="btn-primary" @click="openNewChat">
              <v-icon>mdi-plus</v-icon>
            </v-btn>
          </div>

          <div class="chat-scroll">
            <v-list density="compact">
              <v-list-item
                v-for="room in filtered"
                :key="room.session_id"
                @click="openRoom(room.session_id)"
                :class="{ active: room.session_id === activeRoomId }"
              >
                <template #prepend>
                  <v-avatar class="avatar">
                    {{ (room.customer_name ?? 'U')[0] }}
                  </v-avatar>
                </template>

                <v-list-item-title class="list-title">
                  {{ room.customer_name ?? room.phone }}
                </v-list-item-title>

                <v-list-item-subtitle class="list-subtitle">
                  {{ room.last_message || '...' }}
                </v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </div>

        </div>
      </aside>

      <!-- CHAT ROOM -->
      <section class="chat-room">
        <div class="chat-window">

          <div class="chat-header" v-if="activeRoom">
            <div class="header-left">
              <v-avatar class="avatar">
                {{ (activeRoom.customer_name ?? 'U')[0] }}
              </v-avatar>
              <div>
                <div class="header-name">
                  {{ activeRoom.customer_name ?? activeRoom.phone }}
                </div>
                <div class="header-phone">
                  {{ activeRoom.phone }}
                </div>
              </div>
            </div>
          </div>

          <div class="chat-body">
            <ChatRoom ref="chatRoomRef" :room-id="activeRoomId" />
          </div>

          <div class="chat-input">
            <input
              type="file"
              ref="filePicker"
              hidden
              @change="e => selectedFile = e.target.files[0]"
            />
            <v-btn icon @click="filePicker?.click()" :disabled="sendingMessage">
              <v-icon>mdi-paperclip</v-icon>
            </v-btn>

            <v-text-field
              v-model="tempMessage"
              placeholder="Type a message..."
              variant="solo"
              density="compact"
              hide-details
              class="message-input"
              :disabled="sendingMessage"
              @keyup.enter="sendMessage"
            />

            <v-btn
              class="btn-primary"
              @click="sendMessage"
              :disabled="sendingMessage"
              :loading="sendingMessage"
            >
              SEND
            </v-btn>
          </div>

        </div>
      </section>
    </div>

    <!-- NEW CHAT DIALOG -->
    <v-dialog v-model="newChatDialog" max-width="420">
      <v-card class="dialog-dark">
        <v-card-title>Add New Chat</v-card-title>

        <v-card-text>
          <v-text-field
            v-model="newChatForm.name"
            label="Customer Name"
            variant="outlined"
            density="compact"
          />
          <v-text-field
            v-model="newChatForm.phone"
            label="WhatsApp Number"
            variant="outlined"
            density="compact"
            required
          />
          <v-textarea
            v-model="newChatForm.message"
            label="Initial Message"
            rows="3"
            variant="outlined"
            density="compact"
          />
        </v-card-text>

        <v-card-actions class="justify-end">
          <v-btn variant="text" @click="newChatDialog=false">Cancel</v-btn>
          <v-btn class="btn-primary" :loading="newChatLoading" @click="submitNewChat">
            Create
          </v-btn>
        </v-card-actions>
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

.chat-sidebar { width: 28%; }
.chat-list,
.chat-window {
  background: radial-gradient(circle at top, #0f172a, #020617);
  border-radius: 14px;
  overflow: hidden;
  box-shadow: 0 20px 40px rgba(0,0,0,.35);
}

.chat-sidebar-title {
  padding: 14px 16px 6px;
  font-size: 13px;
  font-weight: 600;
  color: #c7d2fe;
}

.sidebar-search {
  display: flex;
  gap: 8px;
  padding: 8px 10px;
}

.search :deep(.v-field),
.message-input :deep(.v-field) {
  background: rgba(255,255,255,.06);
  border-radius: 10px;
  border: 1px solid rgba(99,102,241,.35);
}

.search :deep(input),
.message-input :deep(input) {
  color: #f1f5f9;
}

.chat-scroll { flex:1; overflow-y:auto; }

.v-list-item {
  transition: background .2s ease;
}
.v-list-item.active {
  background: rgba(99,102,241,.25);
  border-left: 3px solid #6366f1;
}

.list-title { color:#f8fafc; font-weight:500; }
.list-subtitle { color:#cbd5f5; font-size:12px; }

.chat-room { flex:1; }
.chat-window { height:100%; display:flex; flex-direction:column; }

.chat-header {
  padding:14px 18px;
  border-bottom:1px solid rgba(255,255,255,.06);
}

.header-left {
  display:flex;
  gap:12px;
  align-items:center;
}

.header-name { color:#f8fafc; font-weight:600; }
.header-phone { color:#c7d2fe; font-size:12px; }

.chat-body { flex:1; padding:14px; overflow-y:auto; }

.chat-input {
  display:flex;
  gap:10px;
  padding:12px;
  border-top:1px solid rgba(255,255,255,.06);
}

.btn-primary {
  background: linear-gradient(135deg, #6366f1, #3b82f6);
  color: white;
  font-weight: 600;
}

.avatar {
  background: linear-gradient(135deg, #6366f1, #3b82f6);
  color: white;
  font-weight: 600;
}

.dialog-dark {
  background: linear-gradient(180deg, #020617, #0f172a);
  color: #e5e7eb;
  border-radius: 14px;
}

/* ===============================
   FIX PUTIH LIST CHAT (FINAL)
   =============================== */

/* background list */
.chat-scroll :deep(.v-list) {
  background: transparent !important;
}

/* item normal */
.chat-scroll :deep(.v-list-item) {
  background: transparent !important;
  color: #e5e7eb;
}

/* hover */
.chat-scroll :deep(.v-list-item:hover) {
  background: rgba(255,255,255,0.05) !important;
}

/* active item */
.chat-scroll :deep(.v-list-item.active),
.chat-scroll :deep(.v-list-item--active) {
  background: rgba(99,102,241,0.28) !important;
  border-left: 3px solid #6366f1;
}

/* title & subtitle */
.chat-scroll :deep(.v-list-item-title) {
  color: #f8fafc !important;
}

.chat-scroll :deep(.v-list-item-subtitle) {
  color: #c7d2fe !important;
}

/* matiin background default vuetify */
.chat-scroll :deep(.v-list-item__content),
.chat-scroll :deep(.v-list-item__overlay) {
  background: transparent !important;
}

/* avatar tetap kontras */
.chat-scroll :deep(.v-avatar) {
  background: linear-gradient(135deg, #6366f1, #3b82f6);
  color: white;
}

/* =========================================
   FIX AREA PUTIH & SCROLL LIST CHAT
   ========================================= */

/* Sidebar harus full tinggi */
.chat-sidebar {
  height: 100%;
  display: flex;
}

/* Wrapper list chat wajib column */
.chat-list {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* Area scroll HARUS dibatasi */
.chat-scroll {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  background: transparent !important;
}

/* Matikan background putih Vuetify */
.chat-scroll :deep(.v-list) {
  background: transparent !important;
  min-height: 0 !important;
}

.chat-scroll :deep(.v-list-item) {
  background: transparent !important;
}

/* Jangan biarkan list maksa tinggi */
.chat-scroll :deep(.v-list-item__content),
.chat-scroll :deep(.v-list-item__overlay) {
  background: transparent !important;
}

/* Hover & active */
.chat-scroll :deep(.v-list-item:hover) {
  background: rgba(255,255,255,0.05) !important;
}

.chat-scroll :deep(.v-list-item.active),
.chat-scroll :deep(.v-list-item--active) {
  background: rgba(99,102,241,0.25) !important;
  border-left: 3px solid #6366f1;
}

/* Text */
.chat-scroll :deep(.v-list-item-title) {
  color: #f8fafc !important;
}

.chat-scroll :deep(.v-list-item-subtitle) {
  color: #c7d2fe !important;
}

/* Avatar konsisten */
.chat-scroll :deep(.v-avatar) {
  background: linear-gradient(135deg, #6366f1, #3b82f6);
  color: #fff;
}

/* Scrollbar halus */
.chat-scroll::-webkit-scrollbar {
  width: 6px;
}
.chat-scroll::-webkit-scrollbar-thumb {
  background: rgba(99,102,241,0.4);
  border-radius: 6px;
}
.chat-scroll::-webkit-scrollbar-track {
  background: transparent;
}

</style>
