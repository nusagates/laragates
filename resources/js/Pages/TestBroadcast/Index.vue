<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head } from '@inertiajs/vue3'
import axios from 'axios'
import { useToast } from 'vue-toastification'

const toast = useToast()

const message = ref('Hello from Test Broadcast!')
const loading = ref(false)
const receivedMessages = ref([])
const isListening = ref(false)
const echoStatus = ref('Not Connected')

// Check Echo availability
onMounted(() => {
  if (window.Echo) {
    echoStatus.value = 'Echo Available'

    // Enable Pusher logging for debugging
    if (window.Pusher) {
      window.Pusher.logToConsole = true
    }

    startListening()
  } else {
    echoStatus.value = 'Echo Not Available'
    toast.error('Echo is not initialized')
  }
})

// Start listening to broadcast
function startListening() {
  const channel = window.Echo.channel('test-push-message')

  channel.subscribed(() => {
    console.log('[TEST] ✅ Subscribed to test-push-message')
  })

  channel.error((error) => {
    console.error('[TEST] ❌ Subscription error:', error)
  })

  channel.listen('.TestMessageReceived', (e) => {
    console.log('[TEST] Event received:', e)
    addMessage(e)
  })

  isListening.value = true
  echoStatus.value = 'Listening on test-push-message'
}

function addMessage(e) {
  const exists = receivedMessages.value.find(m =>
    m.message === e.message && m.timestamp === e.timestamp
  )

  if (!exists) {
    receivedMessages.value.unshift({
      message: e.message,
      timestamp: e.timestamp,
      receivedAt: new Date().toLocaleTimeString()
    })

    toast.success('Message received via broadcast!')
  }
}

// Stop listening
function stopListening() {
  window.Echo.leave('test-push-message')
  isListening.value = false
  echoStatus.value = 'Echo Available (Not Listening)'
}

// Trigger broadcast
async function triggerBroadcast() {
  if (!message.value.trim()) {
    toast.warning('Please enter a message')
    return
  }

  loading.value = true

  try {
    const response = await axios.post('/test/broadcast/trigger', {
      message: message.value
    })

    toast.success('Broadcast triggered successfully!')

  } catch (error) {
    console.error('[TEST] Failed to trigger:', error)
    toast.error(error.response?.data?.message || 'Failed to trigger broadcast')
  } finally {
    loading.value = false
  }
}

// Clear messages
function clearMessages() {
  receivedMessages.value = []
  toast.info('Messages cleared')
}

// Cleanup on unmount
onUnmounted(() => {
  if (isListening.value) {
    stopListening()
  }
})
</script>

<template>
  <Head title="Test Broadcast" />

  <AdminLayout>
    <template #title>Test Broadcast & Subscribe</template>

    <div class="test-container">

      <!-- STATUS PANEL -->
      <v-card class="mb-4">
        <v-card-title>Connection Status</v-card-title>
        <v-card-text>
          <div class="status-info">
            <v-chip
              :color="isListening ? 'success' : 'warning'"
              variant="flat"
            >
              {{ echoStatus }}
            </v-chip>

            <div class="mt-2">
              <small><strong>Channel:</strong> test-push-message</small><br>
              <small><strong>Event:</strong> TestMessageReceived</small>
            </div>
          </div>
        </v-card-text>
      </v-card>

      <!-- TRIGGER PANEL -->
      <v-card class="mb-4">
        <v-card-title>1. Trigger Broadcast</v-card-title>
        <v-card-text>
          <p class="mb-3">Send a test message to broadcast:</p>

          <v-text-field
            v-model="message"
            label="Test Message"
            variant="outlined"
            density="comfortable"
            hide-details
          />

          <v-btn
            color="primary"
            class="mt-3"
            :loading="loading"
            @click="triggerBroadcast"
          >
            <v-icon left>mdi-send</v-icon>
            Trigger Broadcast
          </v-btn>
        </v-card-text>
      </v-card>

      <!-- LISTENER PANEL -->
      <v-card class="mb-4">
        <v-card-title>
          2. Listener Status
          <v-chip
            :color="isListening ? 'success' : 'grey'"
            size="small"
            class="ml-2"
          >
            {{ isListening ? 'Active' : 'Inactive' }}
          </v-chip>
        </v-card-title>
        <v-card-text>
          <div class="d-flex gap-2">
            <v-btn
              color="success"
              variant="outlined"
              :disabled="isListening"
              @click="startListening"
            >
              Start Listening
            </v-btn>

            <v-btn
              color="error"
              variant="outlined"
              :disabled="!isListening"
              @click="stopListening"
            >
              Stop Listening
            </v-btn>
          </div>
        </v-card-text>
      </v-card>

      <!-- RECEIVED MESSAGES -->
      <v-card>
        <v-card-title class="d-flex justify-space-between align-center">
          <span>3. Received Messages ({{ receivedMessages.length }})</span>
          <v-btn
            size="small"
            color="error"
            variant="text"
            @click="clearMessages"
            v-if="receivedMessages.length"
          >
            Clear
          </v-btn>
        </v-card-title>
        <v-card-text>
          <div v-if="!receivedMessages.length" class="text-center py-4 text-grey">
            No messages received yet. Trigger a broadcast to test.
          </div>

          <v-list v-else density="compact">
            <v-list-item
              v-for="(msg, index) in receivedMessages"
              :key="index"
              class="message-item"
            >
              <template #prepend>
                <v-icon color="success">mdi-check-circle</v-icon>
              </template>

              <v-list-item-title>{{ msg.message }}</v-list-item-title>
              <v-list-item-subtitle>
                Sent: {{ msg.timestamp }} | Received: {{ msg.receivedAt }}
              </v-list-item-subtitle>
            </v-list-item>
          </v-list>
        </v-card-text>
      </v-card>

      <!-- INSTRUCTIONS -->
      <v-card class="mt-4" color="blue-grey-darken-4">
        <v-card-title>📋 How to Test</v-card-title>
        <v-card-text>
          <ol class="test-instructions">
            <li>Check that "Connection Status" shows "Listening on test-push-message"</li>
            <li>Enter a message and click "Trigger Broadcast"</li>
            <li>If broadcast works, you should see:
              <ul>
                <li>Success toast notification</li>
                <li>Console log: <code>[TEST] Event received:</code></li>
                <li>Message appears in "Received Messages" section</li>
              </ul>
            </li>
            <li><strong>Open browser console (F12)</strong> to see detailed logs</li>
          </ol>

          <v-divider class="my-3" />

          <p class="mb-1"><strong>Troubleshooting:</strong></p>
          <ul class="test-instructions">
            <li>Check .env has correct PUSHER credentials</li>
            <li>Verify BROADCAST_CONNECTION=pusher</li>
            <li>Check Laravel logs: <code>storage/logs/laravel.log</code></li>
            <li>Check Pusher dashboard for events</li>
          </ul>
        </v-card-text>
      </v-card>

    </div>
  </AdminLayout>
</template>

<style scoped>
.test-container {
  max-width: 900px;
  margin: 0 auto;
  padding: 20px;
}

.status-info {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.message-item {
  border-left: 3px solid #4caf50;
  margin-bottom: 8px;
  background: rgba(76, 175, 80, 0.05);
}

.test-instructions {
  padding-left: 20px;
  line-height: 1.8;
}

.test-instructions code {
  background: rgba(255, 255, 255, 0.1);
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 0.9em;
}

.gap-2 {
  gap: 8px;
}
</style>
