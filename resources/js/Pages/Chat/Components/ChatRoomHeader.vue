<script setup>
import { computed, ref } from 'vue'
import { useChatStore } from '@/stores/chatStore'
import { useToast } from 'vue-toastification'

const chatStore = useChatStore()
const toast = useToast()

const activeRoom = computed(() => chatStore.activeRoom)
const typingUsers = computed(() => chatStore.activeTypingUsers)
const closingSession = ref(false)
const showCloseDialog = ref(false)

// Reassign states
const showReassignDialog = ref(false)
const reassigning = ref(false)
const selectedAgent = ref(null)
const availableAgents = ref([])
const loadingAgents = ref(false)

const typingText = computed(() => {
  if (typingUsers.value.length === 0) return ''
  if (typingUsers.value.length === 1) return `${typingUsers.value[0].name} is typing...`
  if (typingUsers.value.length === 2) return `${typingUsers.value[0].name} and ${typingUsers.value[1].name} are typing...`
  return `${typingUsers.value.length} people are typing...`
})

const isOnline = computed(() => chatStore.isConnected)

async function handleCloseSession() {
  if (!activeRoom.value) return

  closingSession.value = true
  try {
    await chatStore.closeSession(activeRoom.value.session_id)
    toast.success('Chat session closed successfully')
    showCloseDialog.value = false
  } catch (error) {
    const errorMsg = error.response?.data?.error || error.response?.data?.message || 'Failed to close session'
    toast.error(errorMsg)
  } finally {
    closingSession.value = false
  }
}

async function openReassignDialog() {
  showReassignDialog.value = true
  selectedAgent.value = null
  loadingAgents.value = true

  try {
    availableAgents.value = await chatStore.getAvailableAgents()
  } catch (error) {
    toast.error('Failed to load available agents')
    showReassignDialog.value = false
  } finally {
    loadingAgents.value = false
  }
}

async function handleReassignSession() {
  if (!activeRoom.value || !selectedAgent.value) return

  reassigning.value = true
  try {
    await chatStore.reassignSession(activeRoom.value.session_id, selectedAgent.value)
    toast.success('Chat session reassigned successfully')
    showReassignDialog.value = false
  } catch (error) {
    const errorMsg = error.response?.data?.error || error.response?.data?.message || 'Failed to reassign session'
    toast.error(errorMsg)
  } finally {
    reassigning.value = false
  }
}
</script>

<template>
  <div class="chat-header" v-if="activeRoom">
    <div class="header-left">
      <div class="avatar-wrapper">
        <v-avatar class="avatar">
          {{ (activeRoom.customer_name ?? 'U')[0].toUpperCase() }}
        </v-avatar>
        <span class="online-indicator" :class="{ online: isOnline }" />
      </div>

      <div class="header-info">
        <div class="header-name">
          {{ activeRoom.customer_name ?? activeRoom.phone }}
        </div>
        <div class="header-phone" v-if="!typingText">
          {{ activeRoom.phone }}
        </div>
        <div class="header-typing" v-else>
          <span class="typing-dots">
            <span></span>
            <span></span>
            <span></span>
          </span>
          {{ typingText }}
        </div>
      </div>
    </div>

    <div class="header-right">
      <v-btn icon size="small" variant="text">
        <v-icon>mdi-phone</v-icon>
      </v-btn>

      <!-- Menu Dropdown -->
      <v-menu>
        <template v-slot:activator="{ props }">
          <v-btn icon size="small" variant="text" v-bind="props">
            <v-icon>mdi-dots-vertical</v-icon>
          </v-btn>
        </template>

        <v-list density="compact" class="menu-dark bg-secondary">
          <v-list-item @click="openReassignDialog">
            <template v-slot:prepend>
              <v-icon size="small">mdi-account-switch</v-icon>
            </template>
            <v-list-item-title>Reassign to Agent</v-list-item-title>
          </v-list-item>

          <v-divider />

          <v-list-item @click="showCloseDialog = true">
            <template v-slot:prepend>
              <v-icon size="small">mdi-close-circle-outline</v-icon>
            </template>
            <v-list-item-title>Close Chat</v-list-item-title>
          </v-list-item>
        </v-list>
      </v-menu>
    </div>
  </div>

  <div class="chat-header empty" v-else>
    <div class="empty-header-text">Select a chat to start messaging</div>
  </div>

  <!-- Reassign Dialog -->
  <v-dialog v-model="showReassignDialog" max-width="500">
    <v-card class="dialog-dark">
      <v-card-title>Reassign Chat Session</v-card-title>

      <v-card-text>
        <p class="mb-4">
          Select an agent to reassign this chat session with <strong>{{ activeRoom?.customer_name }}</strong>
        </p>

        <v-select
          v-model="selectedAgent"
          :items="availableAgents"
          item-title="name"
          item-value="id"
          label="Select Agent"
          variant="outlined"
          density="compact"
          :loading="loadingAgents"
          :disabled="loadingAgents"
        >
          <template v-slot:item="{ props, item }">
            <v-list-item v-bind="props">
              <template v-slot:append>
                <v-chip size="x-small" color="primary">
                  {{ item.raw.sessions_count }} chats
                </v-chip>
              </template>
            </v-list-item>
          </template>
        </v-select>
      </v-card-text>

      <v-card-actions class="justify-end">
        <v-btn variant="text" @click="showReassignDialog = false" :disabled="reassigning">
          Cancel
        </v-btn>
        <v-btn
          color="primary"
          @click="handleReassignSession"
          :loading="reassigning"
          :disabled="!selectedAgent"
        >
          Reassign
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <!-- Close Confirmation Dialog -->
  <v-dialog v-model="showCloseDialog" max-width="420">
    <v-card class="dialog-dark">
      <v-card-title>Close Chat Session</v-card-title>

      <v-card-text>
        Are you sure you want to close this chat session with <strong>{{ activeRoom?.customer_name }}</strong>?
        This action will end the conversation.
      </v-card-text>

      <v-card-actions class="justify-end">
        <v-btn variant="text" @click="showCloseDialog = false" :disabled="closingSession">
          Cancel
        </v-btn>
        <v-btn
          color="error"
          @click="handleCloseSession"
          :loading="closingSession"
        >
          Close Session
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<style scoped>
.chat-header {
  padding: 14px 18px;
  border-bottom: 1px solid rgba(255,255,255,.06);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.chat-header.empty {
  justify-content: center;
}

.empty-header-text {
  color: #94a3b8;
  font-size: 14px;
}

.header-left {
  display: flex;
  gap: 12px;
  align-items: center;
}

.avatar-wrapper {
  position: relative;
}

.avatar {
  background: linear-gradient(135deg, #6366f1, #3b82f6);
  color: white;
  font-weight: 600;
}

.online-indicator {
  position: absolute;
  bottom: 2px;
  right: 2px;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: #64748b;
  border: 2px solid #0f172a;
  transition: background 0.3s ease;
}

.online-indicator.online {
  background: #22c55e;
}

.header-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.header-name {
  color: #f8fafc;
  font-weight: 600;
  font-size: 15px;
}

.header-phone {
  color: #c7d2fe;
  font-size: 12px;
}

.header-typing {
  color: #6366f1;
  font-size: 12px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.typing-dots {
  display: inline-flex;
  gap: 3px;
}

.typing-dots span {
  width: 4px;
  height: 4px;
  background: #6366f1;
  border-radius: 50%;
  display: inline-block;
  animation: typing 1.4s infinite;
}

.typing-dots span:nth-child(2) {
  animation-delay: 0.2s;
}

.typing-dots span:nth-child(3) {
  animation-delay: 0.4s;
}

@keyframes typing {
  0%, 60%, 100% {
    transform: translateY(0);
    opacity: 0.7;
  }
  30% {
    transform: translateY(-8px);
    opacity: 1;
  }
}

.header-right {
  display: flex;
  gap: 4px;
}

.header-right .v-btn {
  color: #94a3b8;
}

.header-right .v-btn:hover {
  color: #f8fafc;
}

/* Menu & Dialog Styling */
.menu-dark {
  background: linear-gradient(180deg, #020617, #0f172a);
  color: #e5e7eb;
  border-radius: 8px;
  border: 1px solid rgba(255,255,255,.06);
}

.menu-dark .v-list-item:hover {
  background: rgba(99, 102, 241, 0.1);
}

.dialog-dark {
  background: linear-gradient(180deg, #020617, #0f172a);
  color: #e5e7eb;
  border-radius: 14px;
}

.dialog-dark strong {
  color: #6366f1;
  font-weight: 600;
}
</style>
