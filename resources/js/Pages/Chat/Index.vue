<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head } from '@inertiajs/vue3'
import { useChatStore } from '@/stores/chatStore'
import { useChat } from '@/composables/useChat'
import { useToast } from 'vue-toastification'
import axios from 'axios'

// Components
import ChatList from './Components/ChatList.vue'
import ChatRoomHeader from './Components/ChatRoomHeader.vue'
import MessagesList from './Components/MessagesList.vue'
import MessageInput from './Components/MessageInput.vue'

const chatStore = useChatStore()
const { subscribeToRoom, subscribeToAllSessions, unsubscribeAll } = useChat()
const toast = useToast()

// Expose chatStore to window for AdminLayout
if (typeof window !== 'undefined') {
  window.chatStore = chatStore
  console.log('chatStore exposed to window for AdminLayout', chatStore.activeRoom)
}

/* ================= NEW CHAT DIALOG ================= */
const newChatDialog = ref(false)
const newChatLoading = ref(false)
const newChatForm = ref({
  name: '',
  phone: '',
  message: '',
})

function openNewChat() {
  newChatForm.value = { name: '', phone: '', message: '' }
  newChatDialog.value = true
}

async function submitNewChat() {
  if (!newChatForm.value.phone) {
    toast.error('WhatsApp number is required')
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

    if (!res.data?.session_id) {
      throw new Error('Invalid response')
    }

    newChatDialog.value = false
    await chatStore.loadSessions()
    chatStore.setActiveRoom(res.data.session_id)

    toast.success('Chat created successfully')
  } catch (error) {
    toast.error(error.response?.data?.message || 'Failed to create chat')
  } finally {
    newChatLoading.value = false
  }
}

/* ================= LIFECYCLE ================= */
onMounted(async () => {
  // Load initial sessions
  await chatStore.loadSessions()

  // Subscribe to all sessions for incoming messages
  subscribeToAllSessions()

  // If there's an active room, subscribe to its typing indicators
  if (chatStore.activeRoomId) {
    subscribeToRoom(chatStore.activeRoomId)
    await chatStore.loadMessages(chatStore.activeRoomId)
  }
})

onUnmounted(() => {
  // Clean up all subscriptions
  unsubscribeAll()
})
</script>

<template>
  <Head title="Chat" />

  <AdminLayout>
    <template #title>Chat = {{  chatStore.activeRoom?.status }}</template>

    <div class="chat-flex">
      <!-- SIDEBAR -->
      <ChatList @new-chat="openNewChat" />

      <!-- CHAT ROOM -->
      <section class="chat-room">
        <div class="chat-window">
          <!-- Header -->
          <ChatRoomHeader />

          <!-- Messages Area -->
          <div class="chat-body">
            <MessagesList />
          </div>

          <!-- Input Area -->
           <div v-if="chatStore.activeRoom?.status == 'open'">
             <MessageInput />
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

.chat-room {
  flex: 1;
}

.chat-window {
  height: 100%;
  display: flex;
  flex-direction: column;
  background: radial-gradient(circle at top, #0f172a, #020617);
  border-radius: 14px;
  overflow: hidden;
  box-shadow: 0 20px 40px rgba(0,0,0,.35);
}

.chat-body {
  flex: 1;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.btn-primary {
  background: linear-gradient(135deg, #6366f1, #3b82f6);
  color: white;
  font-weight: 600;
}

.dialog-dark {
  background: linear-gradient(180deg, #020617, #0f172a);
  color: #e5e7eb;
  border-radius: 14px;
}
</style>
