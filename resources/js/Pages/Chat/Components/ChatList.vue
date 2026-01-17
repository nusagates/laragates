<script setup>
import { ref, computed } from 'vue'
import { useChatStore } from '@/stores/chatStore'

const emit = defineEmits(['new-chat'])

const chatStore = useChatStore()
const query = ref('')

const filtered = computed(() => {
  if (!query.value) return chatStore.sessions

  return chatStore.sessions.filter(session =>
    (session.customer_name ?? '').toLowerCase().includes(query.value.toLowerCase()) ||
    (session.phone ?? '').includes(query.value)
  )
})

function selectRoom(sessionId) {
  chatStore.setActiveRoom(sessionId)
}

function openNewChat() {
  emit('new-chat')
}
</script>

<template>
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
            v-for="session in filtered"
            :key="session.session_id"
            @click="selectRoom(session.session_id)"
            :class="{ active: session.session_id === chatStore.activeRoomId }"
          >
            <template #prepend>
              <v-avatar class="avatar">
                {{ (session.customer_name ?? 'U')[0].toUpperCase() }}
              </v-avatar>
            </template>

            <v-list-item-title class="list-title">
              {{ session.customer_name ?? session.phone }}
            </v-list-item-title>

            <v-list-item-subtitle class="list-subtitle">
              {{ session.last_message || '...' }}
            </v-list-item-subtitle>

            <template #append v-if="session.unread_count > 0">
              <v-badge
                :content="session.unread_count"
                color="error"
                inline
              />
            </template>
          </v-list-item>
        </v-list>

        <div v-if="chatStore.loadingSessions" class="loading-state">
          <v-progress-circular indeterminate color="primary" size="32" />
        </div>

        <div v-else-if="filtered.length === 0" class="empty-state">
          <v-icon size="48" color="grey">mdi-message-outline</v-icon>
          <p class="empty-text">No chats found</p>
        </div>
      </div>
    </div>
  </aside>
</template>

<style scoped>
.chat-sidebar {
  width: 28%;
  height: 100%;
  display: flex;
}

.chat-list {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: radial-gradient(circle at top, #0f172a, #020617);
  border-radius: 14px;
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

.search :deep(.v-field) {
  background: rgba(255,255,255,.06);
  border-radius: 10px;
  border: 1px solid rgba(99,102,241,.35);
}

.search :deep(input) {
  color: #f1f5f9;
}

.chat-scroll {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  background: transparent !important;
}

.chat-scroll :deep(.v-list) {
  background: transparent !important;
  min-height: 0 !important;
}

.chat-scroll :deep(.v-list-item) {
  background: transparent !important;
  transition: background .2s ease;
}

.chat-scroll :deep(.v-list-item:hover) {
  background: rgba(255,255,255,0.05) !important;
}

.chat-scroll :deep(.v-list-item.active),
.chat-scroll :deep(.v-list-item--active) {
  background: rgba(99,102,241,0.25) !important;
  border-left: 3px solid #6366f1;
}

.list-title {
  color: #f8fafc !important;
  font-weight: 500;
}

.list-subtitle {
  color: #c7d2fe !important;
  font-size: 12px;
}

.chat-scroll :deep(.v-list-item-title) {
  color: #f8fafc !important;
}

.chat-scroll :deep(.v-list-item-subtitle) {
  color: #c7d2fe !important;
}

.chat-scroll :deep(.v-list-item__content),
.chat-scroll :deep(.v-list-item__overlay) {
  background: transparent !important;
}

.avatar {
  background: linear-gradient(135deg, #6366f1, #3b82f6);
  color: white;
  font-weight: 600;
}

.chat-scroll :deep(.v-avatar) {
  background: linear-gradient(135deg, #6366f1, #3b82f6);
  color: #fff;
}

.btn-primary {
  background: linear-gradient(135deg, #6366f1, #3b82f6);
  color: white;
  font-weight: 600;
}

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

.loading-state,
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 32px 16px;
  gap: 12px;
}

.empty-text {
  color: #94a3b8;
  font-size: 14px;
  margin: 0;
}
</style>
