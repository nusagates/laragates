<script setup>
import { computed } from 'vue'
import { useChatStore } from '@/stores/chatStore'

const chatStore = useChatStore()

const activeRoom = computed(() => chatStore.activeRoom)
const typingUsers = computed(() => chatStore.activeTypingUsers)

const typingText = computed(() => {
  if (typingUsers.value.length === 0) return ''
  if (typingUsers.value.length === 1) return `${typingUsers.value[0].name} is typing...`
  if (typingUsers.value.length === 2) return `${typingUsers.value[0].name} and ${typingUsers.value[1].name} are typing...`
  return `${typingUsers.value.length} people are typing...`
})

const isOnline = computed(() => chatStore.isConnected)
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
      <v-btn icon size="small" variant="text">
        <v-icon>mdi-dots-vertical</v-icon>
      </v-btn>
    </div>
  </div>

  <div class="chat-header empty" v-else>
    <div class="empty-header-text">Select a chat to start messaging</div>
  </div>
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
</style>
