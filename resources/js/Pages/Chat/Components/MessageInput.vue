<script setup>
import { ref, watch, computed } from 'vue'
import { useChatStore } from '@/stores/chatStore'
import { useChat } from '@/composables/useChat'
import { useToast } from 'vue-toastification'

const chatStore = useChatStore()
const { sendMessage, emitTyping } = useChat()
const toast = useToast()

const messageText = ref('')
const selectedFile = ref(null)
const filePicker = ref(null)
const isSending = ref(false)

const activeRoom = computed(() => chatStore.activeRoom)
const isClosed = computed(() => activeRoom.value?.status === 'closed')

async function handleSend() {
  if (!chatStore.activeRoomId) return

  // Check if session is closed
  if (isClosed.value) {
    toast.warning('This chat session is closed. Cannot send messages.')
    return
  }

  if (!messageText.value.trim() && !selectedFile.value) {
    toast.warning('Please type a message or select a file')
    return
  }

  const messageData = {
    message: messageText.value.trim(),
    media: selectedFile.value,
    media_url: selectedFile.value ? URL.createObjectURL(selectedFile.value) : null,
  }

  // Clear input immediately
  const tempMessage = messageText.value
  const tempFile = selectedFile.value
  messageText.value = ''
  selectedFile.value = null
  if (filePicker.value) filePicker.value.value = ''

  isSending.value = true

  try {
    await sendMessage(chatStore.activeRoomId, messageData)
  } catch (error) {
    // Restore message on error
    messageText.value = tempMessage
    selectedFile.value = tempFile
    toast.error(error.response?.data?.message || 'Failed to send message')
  } finally {
    isSending.value = false
  }
}

function handleKeyPress(event) {
  // Send on Enter (without Shift)
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault()
    handleSend()
  }
}

function handleFileSelect(event) {
  const file = event.target.files?.[0]
  if (file) {
    // Validate file size (max 10MB)
    if (file.size > 10 * 1024 * 1024) {
      toast.error('File size must be less than 10MB')
      event.target.value = ''
      return
    }
    selectedFile.value = file
  }
}

function removeFile() {
  selectedFile.value = null
  if (filePicker.value) filePicker.value.value = ''
}

function openFilePicker() {
  filePicker.value?.click()
}

// Watch message input and emit typing indicator
let typingTimer = null
watch(messageText, (newValue, oldValue) => {
  if (!chatStore.activeRoomId) return

  // Only emit if user is actually typing (not clearing)
  if (newValue && newValue !== oldValue) {
    emitTyping(chatStore.activeRoomId, true)
  }
})
</script>

<template>
  <div class="chat-input" :class="{ 'chat-input-disabled': isClosed }">
    <!-- Closed Session Banner -->
    <div v-if="isClosed" class="closed-banner">
      <v-icon size="18" class="mr-2">mdi-lock</v-icon>
      <span>This chat session is closed</span>
    </div>

    <!-- File Preview -->
    <div v-if="selectedFile" class="file-preview">
      <div class="file-preview-content">
        <v-icon size="20">mdi-file</v-icon>
        <span class="file-name">{{ selectedFile.name }}</span>
        <v-btn
          icon
          size="x-small"
          variant="text"
          @click="removeFile"
          class="remove-file-btn"
        >
          <v-icon size="16">mdi-close</v-icon>
        </v-btn>
      </div>
    </div>

    <div class="input-container">
      <!-- Hidden File Input -->
      <input
        type="file"
        ref="filePicker"
        hidden
        @change="handleFileSelect"
        accept="image/*,application/pdf,.doc,.docx,.xls,.xlsx"
      />

      <!-- Attach Button -->
      <v-btn
        icon
        @click="openFilePicker"
        :disabled="isSending || isClosed"
        class="attach-btn"
      >
        <v-icon>mdi-paperclip</v-icon>
      </v-btn>

      <!-- Text Input -->
      <v-text-field
        v-model="messageText"
        :placeholder="isClosed ? 'Session closed - cannot send messages' : 'Type a message...'"
        variant="solo"
        density="compact"
        hide-details
        class="message-input"
        :disabled="isSending || !chatStore.activeRoomId || isClosed"
        @keydown="handleKeyPress"
      />

      <!-- Send Button -->
      <v-btn
        class="btn-primary send-btn"
        @click="handleSend"
        :disabled="isSending || (!messageText.trim() && !selectedFile) || isClosed"
        :loading="isSending"
      >
        <v-icon>mdi-send</v-icon>
      </v-btn>
    </div>
  </div>
</template>

<style scoped>
.chat-input {
  border-top: 1px solid rgba(255,255,255,.06);
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  transition: opacity 0.3s;
}

.chat-input-disabled {
  opacity: 0.6;
}

.closed-banner {
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid rgba(239, 68, 68, 0.3);
  border-radius: 8px;
  padding: 10px 14px;
  display: flex;
  align-items: center;
  color: #fca5a5;
  font-size: 13px;
  font-weight: 500;
  gap: 4px;
}

.closed-banner .v-icon {
  color: #fca5a5;
}

.file-preview {
  background: rgba(99,102,241,0.15);
  border: 1px solid rgba(99,102,241,0.3);
  border-radius: 8px;
  padding: 8px 12px;
}

.file-preview-content {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #c7d2fe;
}

.file-name {
  flex: 1;
  font-size: 13px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.remove-file-btn {
  color: #94a3b8;
}

.remove-file-btn:hover {
  color: #ef4444;
}

.input-container {
  display: flex;
  gap: 10px;
  align-items: center;
}

.attach-btn {
  color: #94a3b8;
  transition: color 0.2s;
}

.attach-btn:hover {
  color: #c7d2fe;
}

.message-input {
  flex: 1;
}

.message-input :deep(.v-field) {
  background: rgba(255,255,255,.06);
  border-radius: 10px;
  border: 1px solid rgba(99,102,241,.35);
}

.message-input :deep(input) {
  color: #f1f5f9;
}

.message-input :deep(.v-field--disabled) {
  opacity: 0.5;
}

.send-btn {
  background: linear-gradient(135deg, #6366f1, #3b82f6);
  color: white;
  font-weight: 600;
  min-width: 48px;
}

.send-btn:disabled {
  opacity: 0.4;
}
</style>
