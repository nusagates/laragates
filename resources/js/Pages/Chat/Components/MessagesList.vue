<script setup>
import { ref, computed, watch, nextTick, onMounted } from 'vue'
import { useChatStore } from '@/stores/chatStore'
import { useChat } from '@/composables/useChat'

const chatStore = useChatStore()
const { addReaction } = useChat()

const activeRoom = computed(() => chatStore.activeRoom)
const isClosed = computed(() => activeRoom.value?.status === 'closed')

const messagesPanel = ref(null)
const currentPage = ref(1)
const loadingMore = ref(false)
const hasMore = ref(true)
const isNearBottom = ref(true)
const showNewMessageIndicator = ref(false)
const unreadCount = ref(0)

const messages = computed(() => {
  // Filter out internal messages and delivery receipts
  return chatStore.activeMessages
})

const REACTION_EMOJIS = ['👍', '❤️', '😂', '🎉']

// Group messages by date
const groupedMessages = computed(() => {
  return chatStore.activeGroupedMessages
})

function formatDateSeparator(dateString) {
  const date = new Date(dateString)
  const today = new Date()
  const yesterday = new Date(today)
  yesterday.setDate(yesterday.getDate() - 1)

  if (date.toDateString() === today.toDateString()) {
    return 'Today'
  } else if (date.toDateString() === yesterday.toDateString()) {
    return 'Yesterday'
  } else {
    return date.toLocaleDateString('en-US', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    })
  }
}

function formatTime(dateString) {
  return new Date(dateString).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
  })
}

function getDeliveryIcon(status) {
  switch (status) {
    case 'sent':
      return 'mdi-check' // Single check - sent to server
    case 'delivered':
      return 'mdi-check-all' // Double check - delivered to recipient
    case 'read':
      return 'mdi-check-all' // Double check - read by recipient
    case 'failed':
    case 'failed_final':
      return 'mdi-alert-circle' // Failed to send
    case 'pending':
    case 'queued':
    case 'sending':
      return 'mdi-clock-outline' // Waiting to be sent
    default:
      return 'mdi-clock-outline'
  }
}

function getDeliveryColor(status) {
  switch (status) {
    case 'read':
      return '#18ec0b' // Light green - message was read
    case 'delivered':
      return '#ffffff' // white - delivered but not read
    case 'sent':
      return '#ffffff' // white - sent but not delivered
    case 'failed':
    case 'failed_final':
      return '#ef4444' // Red - failed
    case 'pending':
    case 'queued':
    case 'sending':
      return '#ffffff' // white - pending
    default:
      return '#ffffff'
  }
}

async function handleReaction(messageId, emoji) {
  try {
    await addReaction(messageId, emoji)
  } catch (error) {
    console.error('Failed to add reaction:', error)
  }
}

function getReactionCount(reactions, emoji) {
  if (!reactions || typeof reactions !== 'object') return 0
  return reactions[emoji] || 0
}

function hasReactions(message) {
  if (!message.reactions || typeof message.reactions !== 'object') return false
  return Object.keys(message.reactions).length > 0
}

async function scrollToBottom() {
  await nextTick()
  if (messagesPanel.value) {
    messagesPanel.value.scrollTop = messagesPanel.value.scrollHeight
    showNewMessageIndicator.value = false
    unreadCount.value = 0
  }
}

function checkIfNearBottom() {
  if (!messagesPanel.value) return true

  const element = messagesPanel.value
  const threshold = 150 // pixels from bottom
  const distanceFromBottom = element.scrollHeight - element.scrollTop - element.clientHeight

  const isNear = distanceFromBottom < threshold

  return isNear
}

async function loadMore() {
  if (loadingMore.value || !hasMore.value || !chatStore.activeRoomId) return

  loadingMore.value = true
  currentPage.value++

  try {
    const oldScrollHeight = messagesPanel.value.scrollHeight
    const newMessages = await chatStore.loadMessages(chatStore.activeRoomId, currentPage.value)

    if (!newMessages || newMessages.length === 0) {
      hasMore.value = false
    }

    // Stop loading indicator BEFORE measuring new height so spinner is removed
    // AND prevent scroll event from triggering loadMore again
    isRestoringScroll.value = true
    loadingMore.value = false

    await nextTick()

    // Maintain scroll position
    const newScrollHeight = messagesPanel.value.scrollHeight
    messagesPanel.value.scrollTop = newScrollHeight - oldScrollHeight

    // Allow scroll handling again after a short delay
    setTimeout(() => {
      isRestoringScroll.value = false
    }, 100)

  } catch (error) {
    console.error('Failed to load more messages:', error)
    loadingMore.value = false
    isRestoringScroll.value = false
  }
}

function handleScroll(event) {
  if (isRestoringScroll.value) return

  const element = event.target

  // Check if user is near bottom
  isNearBottom.value = checkIfNearBottom()

  // Hide indicator if user scrolls to bottom
  if (isNearBottom.value) {
    showNewMessageIndicator.value = false
    unreadCount.value = 0
  }

  // Load more when scrolled to top
  if (element.scrollTop === 0 && hasMore.value && !loadingMore.value) {
    loadMore()
  }
}

// Watch for new messages and smart scroll
watch(messages, async (newMessages, oldMessages) => {
  if (newMessages.length > oldMessages.length) {
    const lastMessage = newMessages[newMessages.length - 1]

    // Only auto-scroll if it's a new message (not from pagination)
    if (!oldMessages.find(m => m.id === lastMessage.id)) {
      // Wait for DOM update then check position
      await nextTick()

      // System messages should ALWAYS scroll to bottom (important notifications)
      if (lastMessage.sender === 'system') {
        scrollToBottom()
        return
      }

      // Update isNearBottom status before checking
      const wasNearBottom = checkIfNearBottom()

      // Check if user is at bottom or near bottom
      if (wasNearBottom) {
        // Auto-scroll only if user is at bottom
        scrollToBottom()
      } else {
        // User is scrolled up, show indicator instead
        showNewMessageIndicator.value = true
        unreadCount.value++
      }
    }
  }
}, { deep: true })

// Watch for active room changes
watch(() => chatStore.activeRoomId, () => {
  currentPage.value = 1
  hasMore.value = true
  showNewMessageIndicator.value = false
  unreadCount.value = 0
  scrollToBottom()
})

onMounted(() => {
  scrollToBottom()

  // Update isNearBottom on mount
  nextTick(() => {
    isNearBottom.value = checkIfNearBottom()
  })
})
</script>

<template>
  <div class="messages-panel" ref="messagesPanel" @scroll="handleScroll">
    <div v-if="loadingMore" class="loading-more">
      <v-progress-circular indeterminate size="24" color="primary" />
    </div>

    <!-- New Messages Indicator -->
    <transition name="slide-up">
      <div
        v-if="showNewMessageIndicator"
        class="new-message-indicator"
        @click="scrollToBottom"
      >
        <v-btn
          color="primary"
          elevation="4"
          rounded="pill"
          size="small"
        >
          <v-icon start>mdi-arrow-down</v-icon>
          {{ unreadCount }} new message{{ unreadCount > 1 ? 's' : '' }}
        </v-btn>
      </div>
    </transition>

    <div class="messages-list">

      <template v-for="(item, index) in groupedMessages" :key="index">
        <!-- Date Separator -->
        <div v-if="item.type === 'date'" class="date-separator">
          <span class="date-text">{{ item.date }}</span>
        </div>

        <div v-else-if="item.data.sender === 'system'" class="info-separator">
          <div class="system-message">
            <v-icon 
              :icon="item.data.message.includes('reassigned') ? 'mdi-account-switch' : 'mdi-information'"
              size="18"
            />
            <span class="system-text">{{ item.data.message }}</span>
            <span class="system-time">{{ item.date }}</span>
          </div>
        </div>

        <!-- Message Bubble -->
        <div
          v-else
          class="message-wrapper"
          :class="{
            'message-agent': item.data.sender === 'agent' || item.data.is_outgoing,
            'message-customer': item.data.sender === 'customer' && !item.data.is_outgoing
          }"
        >
          <div class="message-bubble">
            <!-- Media (Image/File) -->
            <div v-if="item.data.media_url" class="message-media">
              <img
                v-if="item.data.media_type?.startsWith('image')"
                :src="item.data.media_url"
                class="message-image"
                @click="$emit('view-image', item.data.media_url)"
              />
              <div v-else class="message-file">
                <v-icon>mdi-file</v-icon>
                <span>{{ item.data.file_name || 'File' }}</span>
              </div>
            </div>

            <!-- Text Message -->
            <div v-if="item.data.message" class="message-text">
              {{ item.data.message }}
            </div>

            <!-- Message Meta -->
            <div class="message-meta">
              <span class="message-time">{{ formatTime(item.data.created_at) }}</span>

              <!-- Delivery Status (agent messages only) -->
              <v-icon
                v-show="item.data.sender === 'agent'"
                :icon="getDeliveryIcon(item.data.delivery_status)"
                :color="getDeliveryColor(item.data.delivery_status)"
                size="16"
                class="delivery-icon"
              />
            </div>

            <!-- Reactions Display -->
            <div v-if="false && hasReactions(item.data)" class="message-reactions-display">
              <span
                v-for="(count, emoji) in item.data.reactions"
                :key="emoji"
                class="reaction-pill"
              >
                {{ emoji }} {{ count }}
              </span>
            </div>
          </div>

          <!-- Reaction Buttons -->
          <div v-if="false" class="reaction-buttons">
            <button
              v-for="emoji in REACTION_EMOJIS"
              :key="emoji"
              @click="handleReaction(item.data.id, emoji)"
              class="reaction-btn"
              :title="`React with ${emoji}`"
            >
              {{ emoji }}
            </button>
          </div>
        </div>
      </template>


      <!-- Closed Session Notice (at the bottom of messages) -->
      <div v-if="isClosed" class="session-closed-notice">
        <v-icon size="20" color="error">mdi-lock-outline</v-icon>
        <div class="closed-notice-text">
          <strong>This chat session has been closed</strong>
          <span>No new messages can be sent</span>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="messages.length === 0 && !chatStore.loadingMessages[chatStore.activeRoomId]" class="empty-messages">
      <v-icon size="64" color="grey">mdi-message-text-outline</v-icon>
      <p class="empty-text">No messages yet</p>
      <p class="empty-subtext">Start a conversation</p>
    </div>

    <!-- Loading State -->
    <div v-if="chatStore.loadingMessages[chatStore.activeRoomId]" class="loading-messages">
      <v-progress-circular indeterminate size="48" color="primary" />
    </div>
  </div>
</template>

<style scoped>
.messages-panel {
  position: relative;
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 16px;
  display: flex;
  flex-direction: column;
}

.messages-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.loading-more {
  display: flex;
  justify-content: center;
  padding: 12px;
}

/* Closed Session Notice */
.session-closed-notice {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  margin-bottom: 16px;
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.3);
  border-radius: 12px;
  color: #fca5a5;
}

.closed-notice-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.closed-notice-text strong {
  font-size: 14px;
  font-weight: 600;
  color: #ef4444;
}

.closed-notice-text span {
  font-size: 12px;
  color: #fca5a5;
}

/* Date Separator */
.date-separator {
  display: flex;
  justify-content: center;
  margin: 16px 0;
}

.info-separator {
  display: flex;
  justify-content: center;
  margin: 16px 0;
}

.date-text {
  background: rgba(99,102,241,0.15);
  color: #c7d2fe;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

/* System Message */
.system-message {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(59, 130, 246, 0.1);
  border: 1px solid rgba(59, 130, 246, 0.3);
  border-radius: 12px;
  padding: 10px 14px;
  color: #93c5fd;
  animation: systemMessageSlide 0.4s ease-out;
}

.system-message :deep(.v-icon) {
  color: #3b82f6;
  flex-shrink: 0;
}

.system-text {
  font-size: 13px;
  font-weight: 500;
  flex: 1;
  color: #93c5fd;
}

.system-time {
  font-size: 11px;
  color: rgba(147, 197, 253, 0.7);
  white-space: nowrap;
}

@keyframes systemMessageSlide {
  from {
    opacity: 0;
    transform: translateY(-8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Message Wrapper */
.message-wrapper {
  display: flex;
  flex-direction: column;
  position: relative;
  max-width: 70%;
  align-items: flex-start;
  align-self: flex-start;
}

.message-wrapper.message-customer {
  align-items: flex-start;
  align-self: flex-start;
}

.message-wrapper.message-agent {
  align-items: flex-end;
  align-self: flex-end;
}

.message-wrapper:hover .reaction-buttons {
  opacity: 1;
  pointer-events: all;
}

/* Message Bubble */
.message-bubble {
  background: rgba(255,255,255,0.08);
  border-radius: 12px;
  padding: 10px 14px;
  position: relative;
  word-wrap: break-word;
  max-width: 100%;
}

.message-customer .message-bubble {
  background: rgba(255,255,255,0.08);
}

.message-agent .message-bubble {
  background: linear-gradient(135deg, #6366f1, #3b82f6);
}

.message-text {
  color: #f8fafc;
  font-size: 14px;
  line-height: 1.5;
  margin-bottom: 4px;
}

.message-meta {
  display: flex;
  align-items: center;
  gap: 6px;
  justify-content: flex-end;
}

.message-time {
  color: rgba(255,255,255,0.6);
  font-size: 11px;
}

.delivery-icon {
  opacity: 0.8;
}

/* Media */
.message-media {
  margin-bottom: 8px;
}

.message-image {
  max-width: 100%;
  border-radius: 8px;
  cursor: pointer;
  transition: opacity 0.2s;
}

.message-image:hover {
  opacity: 0.9;
}

.message-file {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px;
  background: rgba(255,255,255,0.1);
  border-radius: 8px;
  color: #f8fafc;
  font-size: 13px;
}

/* Reactions Display */
.message-reactions-display {
  display: flex;
  gap: 4px;
  margin-top: 6px;
  flex-wrap: wrap;
}

.reaction-pill {
  background: rgba(255,255,255,0.15);
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 12px;
  color: #f8fafc;
}

/* Reaction Buttons */
.reaction-buttons {
  display: flex;
  gap: 4px;
  margin-top: 4px;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.2s;
}

.reaction-btn {
  background: rgba(99,102,241,0.2);
  border: 1px solid rgba(99,102,241,0.4);
  border-radius: 16px;
  padding: 4px 8px;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
}

.reaction-btn:hover {
  background: rgba(99,102,241,0.4);
  transform: scale(1.1);
}

/* Empty State */
.empty-messages,
.loading-messages {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex: 1;
  gap: 12px;
  padding: 48px 16px;
}

.empty-text {
  color: #94a3b8;
  font-size: 16px;
  font-weight: 500;
  margin: 0;
}

.empty-subtext {
  color: #64748b;
  font-size: 14px;
  margin: 0;
}

/* Scrollbar */
.messages-panel::-webkit-scrollbar {
  width: 6px;
}

.messages-panel::-webkit-scrollbar-thumb {
  background: rgba(99,102,241,0.4);
  border-radius: 6px;
}

.messages-panel::-webkit-scrollbar-track {
  background: transparent;
}

/* New Message Indicator */
.new-message-indicator {
  position: absolute;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 10;
  animation: bounce 0.5s ease-in-out;
}

.new-message-indicator .v-btn {
  box-shadow: 0 4px 12px rgba(99,102,241,0.4) !important;
}

/* Slide Up Animation */
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s ease;
}

.slide-up-enter-from {
  opacity: 0;
  transform: translateX(-50%) translateY(20px);
}

.slide-up-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(20px);
}

@keyframes bounce {
  0%, 100% { transform: translateX(-50%) translateY(0); }
  50% { transform: translateX(-50%) translateY(-10px); }
}
</style>
