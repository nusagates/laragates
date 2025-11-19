<script setup>
import { ref, computed, onMounted } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head } from '@inertiajs/vue3'
import ChatRoom from './Room.vue'

// ----- DATA DUMMY (sementara, nanti dari backend) -----
const rooms = ref([
  { id: 1, name: 'Customer 1', last: "Hi, I'd like to ask about ...", unread: 2, time: '10:23' },
  { id: 2, name: 'Customer 2', last: "Thanks! I'll pay later", unread: 0, time: '09:55' },
  { id: 3, name: 'Customer 3', last: "Where is my order?", unread: 1, time: '08:12' },
  { id: 4, name: 'Customer 4', last: "Can I change address?", unread: 0, time: 'Yesterday' },
])

const query = ref('')
const activeRoomId = ref(rooms.value[0].id)
const tempMessage = ref('')

// filter pencarian
const filtered = computed(() => {
  if (!query.value) return rooms.value
  return rooms.value.filter(r =>
    r.name.toLowerCase().includes(query.value.toLowerCase())
  )
})

// ganti room chat
function openRoom(id) {
  activeRoomId.value = id
}

// kirim pesan (sementara console)
function sendMessage() {
  if (!tempMessage.value.trim()) return
  console.log('Send to:', activeRoomId.value, 'Message:', tempMessage.value)
  tempMessage.value = ''
}

// ----- ATTACH FILE -----
const fileInput = ref(null)
const showPreview = ref(false)
const previewFile = ref('')

function chooseFile() {
  if (fileInput.value) {
    fileInput.value.click()
  }
}

function handleFile(event) {
  const file = event.target.files[0]
  if (!file) return
  previewFile.value = URL.createObjectURL(file)
  showPreview.value = true
}

// infinite scroll dummy
function onScroll(e) {
  if (e.target.scrollTop === 0) {
    console.log('Load older messages...')
  }
}

// ----- MENU TITIK 3 -----
function assignAgent() {
  console.log('Assign chat to other agent:', activeRoomId.value)
}

function markUnread() {
  console.log('Mark unread:', activeRoomId.value)
  const room = rooms.value.find(r => r.id === activeRoomId.value)
  if (room) room.unread = (room.unread || 0) + 1
}

function deleteChat() {
  console.log('Delete chat:', activeRoomId.value)
  rooms.value = rooms.value.filter(r => r.id !== activeRoomId.value)
  if (rooms.value.length) {
    activeRoomId.value = rooms.value[0].id
  } else {
    activeRoomId.value = null
  }
}

onMounted(() => {
  // nanti tempat inisialisasi realtime (Echo/WebSocket)
})
</script>

<template>
  <Head title="Chat" />

  <AdminLayout>
    <template #title>Chat</template>

    <!-- WRAPPER FLEX: LIST KIRI + ROOM KANAN -->
    <div class="chat-flex">
      <!-- SIDEBAR LIST -->
      <div class="chat-sidebar">
        <v-card class="pa-4 chat-list" elevation="2">
          <v-text-field
            v-model="query"
            placeholder="Search customer or phone..."
            density="comfortable"
            rounded
            hide-details
            prepend-inner-icon="mdi-magnify"
            class="mb-3"
          />

          <v-divider></v-divider>

          <v-list two-line density="comfortable">
            <v-list-item
              v-for="room in filtered"
              :key="room.id"
              @click="openRoom(room.id)"
              :class="{ 'bg-highlight': room.id === activeRoomId }"
              style="cursor: pointer;"
            >
              <v-list-item-avatar>
                <v-avatar color="blue">
                  <span class="white--text">{{ room.name.charAt(0) }}</span>
                </v-avatar>
              </v-list-item-avatar>

              <v-list-item-content>
                <v-list-item-title class="d-flex justify-space-between align-center">
                  <span>{{ room.name }}</span>
                  <small class="text-body-2 text-grey">{{ room.time }}</small>
                </v-list-item-title>
                <v-list-item-subtitle class="text-truncate text-body-2">
                  {{ room.last }}
                </v-list-item-subtitle>
              </v-list-item-content>

              <v-list-item-action>
                <v-badge v-if="room.unread" :content="room.unread" color="red" />
              </v-list-item-action>
            </v-list-item>
          </v-list>
        </v-card>
      </div>

      <!-- CHAT ROOM -->
      <div class="chat-room">
        <v-card class="chat-window" elevation="2">

          <!-- HEADER -->
          <div class="chat-header">
            <v-row align="center" justify="space-between">
              <v-col cols="auto" class="d-flex align-center">
                <v-avatar color="blue" size="40">
                  <span class="white--text">A</span>
                </v-avatar>
                <div class="ms-3">
                  <div class="font-weight-medium" style="font-size: 16px;">
                    {{ rooms.find(r => r.id === activeRoomId)?.name }}
                  </div>
                  <div class="text-body-2 text-grey">
                    Active · last seen 2m ago
                  </div>
                </div>
              </v-col>

              <v-col cols="auto" class="d-flex gap-2">
                <v-btn icon><v-icon>mdi-phone</v-icon></v-btn>
                <v-btn icon><v-icon>mdi-information-outline</v-icon></v-btn>

                <!-- MENU TITIK 3 -->
                <v-menu>
                  <template #activator="{ props }">
                    <v-btn v-bind="props" icon>
                      <v-icon>mdi-dots-vertical</v-icon>
                    </v-btn>
                  </template>

                  <v-list>
                    <v-list-item @click="assignAgent">
                      <v-list-item-title>Assign to other agent</v-list-item-title>
                    </v-list-item>

                    <v-list-item @click="markUnread">
                      <v-list-item-title>Mark as unread</v-list-item-title>
                    </v-list-item>

                    <v-divider />

                    <v-list-item @click="deleteChat">
                      <v-list-item-title style="color:red;">Delete chat</v-list-item-title>
                    </v-list-item>
                  </v-list>
                </v-menu>
              </v-col>
            </v-row>
          </div>

          <!-- BODY -->
          <div class="chat-body" @scroll="onScroll">
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

              <v-col cols="auto" class="d-flex gap-2">
                <v-btn icon title="Attach" @click="chooseFile">
                  <v-icon>mdi-paperclip</v-icon>
                </v-btn>
                <v-btn color="primary" @click="sendMessage">
                  Send
                </v-btn>
              </v-col>
            </v-row>
          </div>
        </v-card>
      </div>
    </div>

    <!-- HIDDEN FILE INPUT -->
    <input
      type="file"
      ref="fileInput"
      @change="handleFile"
      hidden
    />

    <!-- PREVIEW DIALOG -->
    <v-dialog v-model="showPreview" max-width="500px">
      <v-card>
        <v-img :src="previewFile" height="300" cover></v-img>
        <v-card-actions class="justify-end">
          <v-btn variant="text" @click="showPreview = false">Close</v-btn>
          <v-btn color="primary">Send</v-btn>
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

/* Kiri */
.chat-sidebar {
  flex: 0 0 27%;
  max-width: 27%;
}

.chat-list {
  height: 100%;
  overflow-y: auto;
  border-radius: 12px;
  border: 1px solid rgba(0, 0, 0, 0.06);
  background: #fff;
}

/* Kanan */
.chat-room {
  flex: 1;
  max-width: 73%;
}

.chat-window {
  height: 100%;
  display: flex;
  flex-direction: column;
  border-radius: 12px;
  border: 1px solid rgba(0, 0, 0, 0.06);
  overflow: hidden;
}

.chat-header {
  padding: 18px 20px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
  background: #fff;
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

/* Highlight room aktif */
.bg-highlight {
  background-color: rgba(25, 118, 210, 0.08) !important;
  border-left: 4px solid #1976d2;
}

/* Scrollbar manis */
.chat-body::-webkit-scrollbar,
.chat-list::-webkit-scrollbar {
  width: 6px;
}
.chat-body::-webkit-scrollbar-thumb,
.chat-list::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.1);
  border-radius: 6px;
}
</style>
