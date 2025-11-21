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

/* ===== KIRIM PESAN ===== */
async function sendMessage() {
  if (!tempMessage.value.trim() || !activeRoomId.value) return

  const text = tempMessage.value
  tempMessage.value = ''

  try {
    const res = await axios.post(`/api/chats/${activeRoomId.value}/send`, {
      message: text
    })

    // Push pesan langsung ke Room.vue
    window.dispatchEvent(new CustomEvent('agent-message-sent', {
      detail: res.data
    }))
  } catch (err) {
    console.log(err)
    alert('Gagal mengirim pesan!')
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
          <v-text-field
            v-model="query"
            placeholder="Search customer or phone..."
            density="comfortable"
            rounded hide-details
            prepend-inner-icon="mdi-magnify"
            class="mb-3"
          />
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
                <v-list-item-title class="d-flex justify-space-between align-center">
                  <span>{{ room.customer_name }}</span>
                  <small class="text-body-2 text-grey">{{ room.time }}</small>
                </v-list-item-title>
                <v-list-item-subtitle class="text-truncate text-body-2">
                  {{ room.last_message }}
                </v-list-item-subtitle>
              </v-list-item-content>

              <v-list-item-action>
                <v-badge v-if="room.unread_count" :content="room.unread_count" color="red" />
              </v-list-item-action>
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
                <v-btn icon><v-icon>mdi-emoticon-outline</v-icon></v-btn>
              </v-col>

              <v-col>
                <v-textarea
                  v-model="tempMessage"
                  placeholder="Type a message"
                  rows="1" auto-grow
                  variant="outlined" class="me-2"
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

  </AdminLayout>
</template>

<style scoped>
.chat-flex { height: calc(100vh - 120px); display: flex; gap: 16px; }
.chat-sidebar { flex: 0 0 27%; max-width: 27%; }
.chat-list { height: 100%; overflow-y: auto; border-radius: 12px; background: #fff; }
.chat-room { flex: 1; max-width: 73%; }
.chat-window { height: 100%; display: flex; flex-direction: column; border-radius: 12px; overflow: hidden; }
.chat-body { flex: 1; overflow-y: auto; padding: 25px 30px; background: #f4f7fb; }
.chat-input { border-top: 1px solid rgba(0, 0, 0, 0.06); padding: 14px 18px; background: #fff; }
.bg-highlight { background-color: rgba(25, 118, 210, 0.08) !important; border-left: 4px solid #1976d2; }
</style>
