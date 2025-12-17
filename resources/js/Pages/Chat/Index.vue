<script setup>
import { ref, computed, onMounted } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head } from '@inertiajs/vue3'
import ChatRoom from './Room.vue'
import axios from 'axios'
import { useToast } from "vue-toastification";

const toast = useToast();

/* ====== STATE ====== */
const rooms = ref([])
const query = ref('')
const activeRoomId = ref(null)

/* MESSAGE + MEDIA */
const tempMessage = ref('')
const selectedFile = ref(null)
const filePicker = ref(null)

/* NEW CHAT */
const newChatDialog = ref(false)
const newChatLoading = ref(false)
const newChatForm = ref({
  name: '',
  phone: '',
  message: '',
  create_ticket: false,
})

/* ===== FETCH ROOMS ===== */
async function loadRooms() {
  const res = await axios.get('/chat/sessions')
  rooms.value = res.data
  if (!activeRoomId.value && rooms.value.length) {
    activeRoomId.value = rooms.value[0].session_id
  }
}

onMounted(loadRooms)

/* ===== ACTIVE ROOM ===== */
const activeRoom = computed(() =>
  rooms.value.find(r => r.session_id === activeRoomId.value)
)

/* ===== FILTER ===== */
const filtered = computed(() => {
  if (!query.value) return rooms.value
  return rooms.value.filter(r =>
    (r.customer_name ?? '').toLowerCase().includes(query.value.toLowerCase()) ||
    (r.phone ?? '').toLowerCase().includes(query.value.toLowerCase())
  )
})

function openRoom(id) {
  activeRoomId.value = id
}

/* ===== SEND MESSAGE ===== */
async function sendMessage() {
  if (!activeRoomId.value) return
  if (!tempMessage.value && !selectedFile.value) return

  const form = new FormData()
  form.append('message', tempMessage.value || '')
  if (selectedFile.value) form.append('media', selectedFile.value)

  tempMessage.value = ''
  selectedFile.value = null
  if (filePicker.value) filePicker.value.value = ''

  await axios.post(`/chat/sessions/${activeRoomId.value}/messages`, form)
}

/* ===== NEW CHAT ===== */
function openNewChat() {
  newChatForm.value = { name: '', phone: '', message: '', create_ticket: false }
  newChatDialog.value = true
}

async function submitNewChat() {
  newChatLoading.value = true
  try {
    const res = await axios.post('/chat/sessions/outbound', newChatForm.value)
    newChatDialog.value = false
    await loadRooms()
    activeRoomId.value = res.data.session_id
  } catch {
    alert('Gagal membuat chat')
  } finally {
    newChatLoading.value = false
  }
}

/* ===== CONVERT TICKET ===== */
async function convertToTicket() {
  if (!activeRoomId.value) return
  try {
    await axios.post(`/chat/sessions/${activeRoomId.value}/convert-ticket`)
    toast.success('Ticket berhasil dibuat')
    loadRooms()
  } catch {
    toast.error('Gagal convert ke ticket')
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
            <v-btn icon color="primary" @click="openNewChat">
              <v-icon>mdi-plus</v-icon>
            </v-btn>
          </div>

          <div class="chat-scroll">
            <v-list density="compact" class="list-dark">
              <v-list-item
                v-for="room in filtered"
                :key="room.session_id"
                @click="openRoom(room.session_id)"
                :class="{ active: room.session_id === activeRoomId }"
              >
                <template #prepend>
                  <v-avatar color="primary" size="34">
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
              <v-avatar color="primary">
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

            <v-btn color="green" size="small" @click="convertToTicket">
              Convert to Ticket
            </v-btn>
          </div>

          <div class="chat-body">
            <ChatRoom :room-id="activeRoomId" />
          </div>

          <div class="chat-input">
            <input type="file" ref="filePicker" hidden />
            <v-btn icon @click="filePicker?.click()">
              <v-icon>mdi-paperclip</v-icon>
            </v-btn>

            <v-text-field
              v-model="tempMessage"
              placeholder="Type a message..."
              variant="solo"
              density="compact"
              hide-details
              class="message-input"
            />

            <v-btn color="primary" @click="sendMessage">SEND</v-btn>
          </div>

        </div>
      </section>

    </div>
  </AdminLayout>
</template>

<style scoped>
.chat-flex {
  height: calc(100vh - 120px);
  display: flex;
  gap: 16px;
}

/* SIDEBAR */
.chat-sidebar { width: 28%; }
.chat-list {
  height: 100%;
  display: flex;
  flex-direction: column;
  background: radial-gradient(circle at top, #0f172a, #020617);
  border-radius: 14px;
  overflow: hidden;
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
  background: rgba(255,255,255,0.06) !important;
  border-radius: 10px;
  border: 1px solid rgba(99,102,241,0.35);
}

.search :deep(input),
.message-input :deep(input) {
  color: #f1f5f9 !important;
}

.chat-scroll { flex:1; overflow-y:auto; }

/* ===== 🔥 FIX PUTIH DI DAFTAR CHAT (ONLY THIS) ===== */
.chat-scroll :deep(.v-list) {
  background: transparent !important;
}

.chat-scroll :deep(.v-list-item) {
  background: transparent !important;
}

.v-list-item:hover {
  background: rgba(255,255,255,0.04) !important;
}

.v-list-item.active {
  background: rgba(59,130,246,0.25) !important;
  border-left: 3px solid #3b82f6;
}

.list-title {
  color: #f8fafc !important;
  font-weight: 500;
}

.list-subtitle {
  color: #cbd5f5 !important;
  font-size: 12px;
}

/* CHAT */
.chat-room { flex:1; }
.chat-window {
  height:100%;
  display:flex;
  flex-direction:column;
  background: radial-gradient(circle at top, #0f172a, #020617);
  border-radius:14px;
}

.chat-header {
  display:flex;
  justify-content:space-between;
  align-items:center;
  padding:14px 18px;
  border-bottom:1px solid rgba(255,255,255,0.06);
}

.header-name {
  font-size:15px;
  font-weight:600;
  color:#f8fafc;
}

.header-phone {
  font-size:12px;
  color:#c7d2fe;
}

.chat-body { flex:1; padding:14px; overflow-y:auto; }

.chat-input {
  display:flex;
  gap:10px;
  padding:12px;
  border-top:1px solid rgba(255,255,255,0.06);
}
.message-input { flex:1; }

/* ===============================
   FIX HEADER ROOM CHAT LAYOUT
   (AVATAR DI SAMPING NAMA)
   =============================== */
.header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.header-left .v-avatar {
  flex-shrink: 0;
}

.header-left > div {
  display: flex;
  flex-direction: column;
  justify-content: center;
}

/* ===============================
   ✨ GLOBAL POLISH
   =============================== */
.chat-list,
.chat-window {
  box-shadow: 0 20px 40px rgba(0,0,0,0.35);
  transition: all .25s ease;
}

/* ===============================
   ✨ SCROLLBAR (HALUS & DARK)
   =============================== */
.chat-scroll::-webkit-scrollbar,
.chat-body::-webkit-scrollbar {
  width: 6px;
}

.chat-scroll::-webkit-scrollbar-thumb,
.chat-body::-webkit-scrollbar-thumb {
  background: rgba(99,102,241,0.4);
  border-radius: 6px;
}

.chat-scroll::-webkit-scrollbar-track,
.chat-body::-webkit-scrollbar-track {
  background: transparent;
}

/* ===============================
   ✨ LIST ITEM INTERACTION
   =============================== */
.v-list-item {
  transition: background .2s ease, transform .15s ease;
}

.v-list-item:hover {
  transform: translateX(2px);
}

/* ===============================
   ✨ CHAT HEADER GLOW
   =============================== */
.chat-header {
  backdrop-filter: blur(8px);
  background: linear-gradient(
    to right,
    rgba(99,102,241,0.08),
    rgba(2,6,23,0.85)
  );
}

/* ===============================
   ✨ INPUT AREA IMPROVEMENT
   =============================== */
.chat-input {
  background: linear-gradient(
    to top,
    rgba(2,6,23,0.9),
    rgba(15,23,42,0.85)
  );
}

.chat-input .v-btn {
  transition: transform .15s ease, box-shadow .15s ease;
}

.chat-input .v-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 14px rgba(59,130,246,0.35);
}

/* ===============================
   ✨ SEND BUTTON EMPHASIS
   =============================== */
.chat-input .v-btn[color="primary"] {
  font-weight: 600;
  letter-spacing: .4px;
}

/* ===============================
   ✨ AVATAR POLISH
   =============================== */
.v-avatar {
  box-shadow: 0 4px 12px rgba(59,130,246,0.45);
  font-weight: 600;
}

/* ===============================
   ✨ CHAT BODY SOFT GRID FEEL
   =============================== */
.chat-body {
  background-image:
    radial-gradient(rgba(255,255,255,0.04) 1px, transparent 1px);
  background-size: 22px 22px;
}

/* ===============================
   ✨ EMPTY STATE FEEL (ROOM BELUM ADA)
   =============================== */
.chat-body:empty::after {
  content: "Select a chat to start conversation";
  color: #64748b;
  font-size: 13px;
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100%;
  opacity: .6;
}

/* ===============================
   ✨ GLOBAL POLISH
   =============================== */
.chat-list,
.chat-window {
  box-shadow: 0 20px 40px rgba(0,0,0,0.35);
  transition: all .25s ease;
}

/* ===============================
   ✨ SCROLLBAR DARK
   =============================== */
.chat-scroll::-webkit-scrollbar,
.chat-body::-webkit-scrollbar {
  width: 6px;
}

.chat-scroll::-webkit-scrollbar-thumb,
.chat-body::-webkit-scrollbar-thumb {
  background: rgba(99,102,241,0.4);
  border-radius: 6px;
}

.chat-scroll::-webkit-scrollbar-track,
.chat-body::-webkit-scrollbar-track {
  background: transparent;
}

/* ===============================
   ✨ LIST ITEM INTERACTION
   =============================== */
.v-list-item {
  transition: background .2s ease, transform .15s ease;
}

.v-list-item:hover {
  transform: translateX(2px);
}

/* ===============================
   ✨ CHAT HEADER GLOW
   =============================== */
.chat-header {
  backdrop-filter: blur(8px);
  background: linear-gradient(
    to right,
    rgba(99,102,241,0.08),
    rgba(2,6,23,0.85)
  );
}

/* ===============================
   ✨ INPUT AREA IMPROVEMENT
   =============================== */
.chat-input {
  background: linear-gradient(
    to top,
    rgba(2,6,23,0.9),
    rgba(15,23,42,0.85)
  );
}

.chat-input .v-btn {
  transition: transform .15s ease, box-shadow .15s ease;
}

.chat-input .v-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 14px rgba(59,130,246,0.35);
}

/* ===============================
   ✨ SEND BUTTON EMPHASIS
   =============================== */
.chat-input .v-btn[color="primary"] {
  font-weight: 600;
  letter-spacing: .4px;
}

/* ===============================
   ✨ AVATAR POLISH
   =============================== */
.v-avatar {
  box-shadow: 0 4px 12px rgba(59,130,246,0.45);
  font-weight: 600;
}

/* ===============================
   ✨ CHAT BODY GRID FEEL
   =============================== */
.chat-body {
  background-image:
    radial-gradient(rgba(255,255,255,0.04) 1px, transparent 1px);
  background-size: 22px 22px;
}

/* ===============================
   💬 CHAT BUBBLE POLISH
   =============================== */
.chat-body :deep(.message) {
  max-width: 72%;
  padding: 10px 14px;
  border-radius: 14px;
  font-size: 14px;
  line-height: 1.45;
  word-break: break-word;
  animation: fadeUp .25s ease;
}

.chat-body :deep(.message.incoming) {
  background: rgba(255,255,255,0.08);
  color: #f8fafc;
  border-top-left-radius: 6px;
}

.chat-body :deep(.message.outgoing) {
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  color: white;
  margin-left: auto;
  border-top-right-radius: 6px;
}

/* ===============================
   🕒 TIMESTAMP MINI
   =============================== */
.chat-body :deep(.message-time) {
  display: block;
  margin-top: 4px;
  font-size: 10px;
  opacity: .6;
  text-align: right;
}

/* ===============================
   🔔 SIDEBAR UNREAD FEEL
   =============================== */
.v-list-item.active .list-title {
  font-weight: 600;
}

.v-list-item:not(.active) .list-title {
  opacity: .9;
}

/* ===============================
   ✍️ INPUT FOCUS FEEL
   =============================== */
.message-input :deep(.v-field) {
  transition: border .2s ease, box-shadow .2s ease;
}

.message-input :deep(.v-field--active) {
  border: 1px solid rgba(99,102,241,0.8);
  box-shadow: 0 0 0 2px rgba(99,102,241,0.15);
}

/* ===============================
   ✨ EMPTY CHAT STATE
   =============================== */
.chat-body:empty::after {
  content: "Select a chat to start conversation";
  color: #64748b;
  font-size: 13px;
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100%;
  opacity: .6;
}

/* ===============================
   🎬 ANIMATION
   =============================== */
@keyframes fadeUp {
  from {
    opacity: 0;
    transform: translateY(6px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}


</style>
