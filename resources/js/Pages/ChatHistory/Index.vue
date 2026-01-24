<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head } from '@inertiajs/vue3'
import { useChatStore } from '@/stores/chatStore'
import { useChat } from '@/composables/useChat'
import { useToast } from 'vue-toastification'

// Components
import ChatListClosed from './Components/ChatListClosed.vue'
import ChatRoomHeader from '@/Pages/Chat/Components/ChatRoomHeader.vue'
import MessagesList from '@/Pages/Chat/Components/MessagesList.vue'

const chatStore = useChatStore()
const { subscribeToRoom, subscribeToAllSessions, unsubscribeAll } = useChat()
const toast = useToast()

// Expose chatStore to window for AdminLayout
if (typeof window !== 'undefined') {
  window.chatStore = chatStore
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
  <Head title="Chat History" />

  <AdminLayout>
    <template #title>Chat History</template>

    <div class="chat-flex">
      <!-- SIDEBAR -->
      <ChatListClosed />

      <!-- CHAT ROOM -->
      <section class="chat-room">
        <div class="chat-window">
          <!-- Header -->
          <ChatRoomHeader />

          <!-- Messages Area -->
          <div class="chat-body">
            <MessagesList />
          </div>

          <!-- Read-only footer for closed chats -->
          <div class="chat-footer-readonly">
            <v-icon>mdi-lock</v-icon>
            <span>This chat is closed and archived</span>
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

.chat-footer-readonly {
  padding: 12px 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(0, 0, 0, 0.3);
  color: #94a3b8;
  font-size: 13px;
}

.chat-footer-readonly :deep(.v-icon) {
  font-size: 16px;
}
</style>
