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

/* NEW CHAT FORM */
const newChatDialog = ref(false)
const newChatLoading = ref(false)
const newChatForm = ref({
  name: '',
  phone: '',
  message: '',
  create_ticket: false,
})

/* ====== FETCH ROOMS ====== */
async function loadRooms() {
  try {
    const res = await axios.get('/chat/sessions')
    rooms.value = res.data

    if (!activeRoomId.value && rooms.value?.length) {
      activeRoomId.value = rooms.value[0]?.session_id
    }
  } catch (e) {
    console.error(e)
  }
}

onMounted(() => {
  loadRooms()

  // ====== REALTIME LISTENER (Assigned to Agent) ======
  const userId = window?.Laravel?.user?.id ?? document.querySelector('meta[name="user-id"]')?.content ?? null
  if (!userId) return

  window.Echo.private(`agent.${userId}`)
    .listen(".session.assigned", (e) => {
      loadRooms()

      toast.success(`New chat assigned from ${e.session.customer?.name ?? e.session.customer?.phone ?? 'Unknown'}`, {
        timeout: 3200,
        icon: "💬",
      })
    })
})

/* SEARCH FILTER */
const filtered = computed(() => {
  if (!query.value) return rooms.value
  return rooms.value.filter(r => {
    const name = r.customer_name ?? ''
    const phone = r.phone ?? ''
    return name.toLowerCase().includes(query.value.toLowerCase()) ||
      phone.toLowerCase().includes(query.value.toLowerCase())
  })
})

/* SELECT ROOM */
function openRoom(id) {
  activeRoomId.value = id
}

/* ========== PICK FILE ========== */
function pickFile(e) {
  selectedFile.value = e.target.files[0] || null
}

/* ========== SEND MESSAGE + MEDIA ========== */
async function sendMessage() {
  if (!activeRoomId.value) return
  if (!tempMessage.value.trim() && !selectedFile.value) return

  let form = new FormData()
  form.append('message', tempMessage.value || '')
  if (selectedFile.value) {
    form.append('media', selectedFile.value)
  }

  tempMessage.value = ''
  selectedFile.value = null
  if (filePicker.value) filePicker.value.value = ''

  try {
    const res = await axios.post(
      `/chat/sessions/${activeRoomId.value}/messages`,
      form,
      { headers: { 'Content-Type': 'multipart/form-data' } }
    )

    window.dispatchEvent(new CustomEvent('agent-message-sent', {
      detail: res.data?.data ?? res.data,
    }))
  } catch (e) {
    console.error(e)
    alert('Gagal mengirim pesan!')
  }
}

/* ========== NEW CHAT ========== */
function formatPhone() {
  let v = newChatForm.value.phone || ''
  v = v.trim().replace(/[^0-9+]/g, '')
  if (!v) return (newChatForm.value.phone = '')
  if (v.startsWith('+')) return (newChatForm.value.phone = v)
  if (v.startsWith('0')) return (newChatForm.value.phone = '+62' + v.slice(1))
  if (v.startsWith('62')) return (newChatForm.value.phone = '+' + v)
  newChatForm.value.phone = '+' + v
}

function openNewChat() {
  newChatForm.value = { name: '', phone: '', message: '', create_ticket: false }
  newChatDialog.value = true
}

async function submitNewChat() {
  if (!newChatForm.value.phone || !newChatForm.value.message)
    return alert('Phone dan message wajib diisi.')

  newChatLoading.value = true
  formatPhone()

  try {
    const res = await axios.post('/chat/sessions/outbound', newChatForm.value)
    newChatDialog.value = false
    await loadRooms()
    if (res.data?.session_id) activeRoomId.value = res.data.session_id
  } catch (e) {
    console.error(e)
    alert('Gagal membuat chat baru.')
  } finally {
    newChatLoading.value = false
  }
}

/* ========== TICKET ========== */
async function convertToTicket() {
  if (!activeRoomId.value) return
  try {
    const res = await axios.post(`/chat/sessions/${activeRoomId.value}/convert-ticket`)
    alert('Ticket berhasil dibuat/tersedia. ID: ' + res.data?.ticket_id)
    loadRooms()
  } catch (e) {
    console.error(e)
    alert('Gagal membuat ticket.')
  }
}
</script>

<template>
  <Head title="Chat" />

  <AdminLayout>
    <template #title>Chat</template>

    <div class="chat-flex">
      <!-- SIDEBAR -->
      <div class="chat-sidebar">
        <v-card class="pa-4 chat-list" elevation="2">
          <div class="d-flex align-center mb-3">
            <v-text-field
              v-model="query"
              placeholder="Search customer or phone..."
              density="comfortable"
              rounded hide-details
              prepend-inner-icon="mdi-magnify"
              class="flex-grow-1 mr-2"
            />
            <v-btn color="primary" icon @click="openNewChat">
              <v-icon>mdi-message-plus</v-icon>
            </v-btn>
          </div>

          <v-divider />

          <v-list two-line density="comfortable">
            <v-list-item
              v-for="room in filtered" :key="room.session_id"
              @click="openRoom(room.session_id)"
              :class="{ 'bg-highlight': room.session_id === activeRoomId }" style="cursor:pointer"
            >
              <v-list-item-avatar>
                <v-avatar color="blue">
                  <span class="white--text">{{ (room.customer_name ?? 'U').charAt(0) }}</span>
                </v-avatar>
              </v-list-item-avatar>

              <v-list-item-content>
                <v-list-item-title class="d-flex justify-space-between">
                  <span>{{ room.customer_name ?? room.phone }}</span>
                  <small class="text-grey">{{ room.time }}</small>
                </v-list-item-title>
                <v-list-item-subtitle class="text-truncate">
                  {{ room.last_message || '...' }}
                </v-list-item-subtitle>
              </v-list-item-content>

              <v-list-item-action>
                <v-badge v-if="room.has_ticket" color="green" icon="mdi-ticket-confirmation" />
                <v-badge v-if="room.unread_count" :content="room.unread_count" color="red" />
              </v-list-item-action>
            </v-list-item>

            <v-list-item v-if="!filtered.length">
              <v-list-item-title class="text-center text-grey">No chats</v-list-item-title>
            </v-list-item>
          </v-list>
        </v-card>
      </div>

      <!-- CHAT PANEL -->
      <div class="chat-room">
        <v-card class="chat-window" elevation="2">
          <div class="chat-body"><ChatRoom :room-id="activeRoomId" /></div>

          <div class="px-4 py-2 d-flex justify-end">
            <v-btn v-if="activeRoomId" color="green" variant="outlined" @click="convertToTicket">
              <v-icon start>mdi-ticket-confirmation-outline</v-icon> Convert to Ticket
            </v-btn>
          </div>

          <!-- INPUT -->
          <div class="chat-input">
            <v-row align="center" class="no-gutters">
              <v-col cols="auto">
                <input type="file" ref="filePicker" style="display:none"
                       @change="pickFile" accept="image/*,.pdf,.doc,.docx" />
                <v-btn icon @click="filePicker?.click()"><v-icon>mdi-paperclip</v-icon></v-btn>
              </v-col>

              <v-col>
                <v-textarea v-model="tempMessage" placeholder="Type a message"
                  rows="1" auto-grow variant="outlined" class="me-2" />
              </v-col>

              <v-col cols="auto">
                <v-btn color="primary" @click="sendMessage">SEND</v-btn>
              </v-col>
            </v-row>
          </div>
        </v-card>
      </div>
    </div>

    <!-- NEW CHAT DIALOG -->
    <v-dialog v-model="newChatDialog" max-width="480">
      <v-card class="pa-4">
        <h3 class="text-h6 mb-3">New Chat</h3>
        <p class="text-body-2 text-grey-darken-1 mb-4">Kirim pesan WhatsApp pertama ke customer.</p>

        <v-text-field v-model="newChatForm.name" label="Customer Name (optional)" class="mb-3" />
        <v-text-field v-model="newChatForm.phone" label="WhatsApp Number"
          placeholder="0812xxxx / +62812xxxx" class="mb-3" @blur="formatPhone" />
        <v-textarea v-model="newChatForm.message" label="First Message" rows="2" auto-grow class="mb-3" />
        <v-checkbox v-model="newChatForm.create_ticket" label="Create Ticket from this chat" class="mb-2" />

        <v-divider class="my-3" />

        <div class="d-flex justify-end gap-2">
          <v-btn variant="text" @click="newChatDialog = false">Cancel</v-btn>
          <v-btn color="primary" :loading="newChatLoading" @click="submitNewChat">Start Chat</v-btn>
        </div>
      </v-card>
    </v-dialog>
  </AdminLayout>
</template>

<style scoped>
.chat-flex { height:calc(100vh - 120px); display:flex; gap:16px; }
.chat-sidebar { flex:0 0 27%; max-width:27%; }
.chat-list { height:100%; overflow-y:auto; border-radius:12px; background:#fff; }
.chat-room { flex:1; max-width:73%; }
.chat-window { height:100%; display:flex; flex-direction:column; border-radius:12px; overflow:hidden; }
.chat-body { flex:1; overflow-y:auto; padding:25px 30px; background:#f4f7fb; }
.chat-input { border-top:1px solid rgba(0,0,0,0.06); padding:14px 18px; background:#fff; }
.bg-highlight { background:rgba(25,118,210,0.08) !important; border-left:4px solid #1976d2; }
</style>
