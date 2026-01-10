<script setup>
import { ref, watch, onMounted, nextTick } from 'vue'
import axios from 'axios'

const props = defineProps({
  roomId: Number
})

const loading = ref(false)
const messages = ref([])
const panel = ref(null)
let echoChannel = null

/* ===============================
   LOAD MESSAGES
   =============================== */
async function loadMessages() {
  if (!props.roomId) return (messages.value = [])

  loading.value = true
  try {
    const res = await axios.get(`/chat/sessions/${props.roomId}/messages`)
    messages.value = res.data

    await nextTick()
    scrollBottom()
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function scrollBottom() {
  if (panel.value) {
    panel.value.scrollTop = panel.value.scrollHeight
  }
}

/* ===============================
   REALTIME LISTENER
   =============================== */
function setupRealtimeListener() {
  // Leave previous channel
  if (echoChannel) {
    window.Echo.leave(`chat-session.${echoChannel}`)
  }

  if (!props.roomId) return

  // Subscribe to new channel
  echoChannel = props.roomId
  window.Echo.private(`chat-session.${props.roomId}`)
    .listen('.MessageSent', (e) => {
      // Check if message already exists
      const exists = messages.value.find(m => m.id === e.message.id)
      if (!exists) {
        messages.value.push(e.message)
        nextTick(() => scrollBottom())
      }
    })
}

watch(() => props.roomId, () => {
  loadMessages()
  setupRealtimeListener()
})

onMounted(() => {
  loadMessages()
  setupRealtimeListener()
  console.log('mounted')
})

/* ===============================
   BUBBLE CLASS
   =============================== */
function bubbleClass(m) {
  return m.is_outgoing ? 'bubble-me' : 'bubble-you'
}

/* ===============================
   DATE SEPARATOR
   =============================== */
function formatDate(date) {
  const d = new Date(date)
  const today = new Date()
  const yesterday = new Date()
  yesterday.setDate(today.getDate() - 1)

  if (d.toDateString() === today.toDateString()) return 'Today'
  if (d.toDateString() === yesterday.toDateString()) return 'Yesterday'
  return d.toLocaleDateString()
}

function shouldShowDate(index) {
  if (index === 0) return true
  const cur = formatDate(messages.value[index].created_at)
  const prev = formatDate(messages.value[index - 1].created_at)
  return cur !== prev
}

/* ===============================
   SOUND NOTIFICATION
   =============================== */
const audio = new Audio('/sounds/message.mp3')

watch(messages, (newVal, oldVal) => {
  if (!oldVal.length) return
  if (newVal.length > oldVal.length) {
    const last = newVal[newVal.length - 1]
    if (!last.is_outgoing) {
      audio.play().catch(() => {})
    }
  }
})
</script>

<template>
  <div class="wrapper">

    <div v-if="!roomId" class="no-room">
      <small>Select a chat...</small>
    </div>

    <div v-else ref="panel" class="messages">

      <div v-if="loading" class="loading">
        <small>Loading...</small>
      </div>

      <!-- MESSAGES -->
      <template v-for="(m, index) in messages" :key="m.id">

        <!-- DATE SEPARATOR -->
        <div
          v-if="shouldShowDate(index)"
          class="date-separator"
        >
          {{ formatDate(m.created_at) }}
        </div>

        <!-- MESSAGE BUBBLE -->
        <div :class="['bubble', bubbleClass(m)]">

          <!-- IMAGE -->
          <template v-if="m.media_type && m.media_type.startsWith('image')">
            <img :src="m.media_url" class="img" />
          </template>

          <!-- FILE -->
          <template v-else-if="m.media_type">
            <a :href="m.media_url" target="_blank" class="file">
              📎 {{ m.message ?? 'Attachment' }}
            </a>
          </template>

          <!-- TEXT -->
          <template v-else>
            <span>{{ m.message }}</span>
          </template>

          <!-- TIME + STATUS -->
          <div class="time">
            {{ m.created_at }}
            <span
              v-if="m.is_outgoing"
              class="status"
              :class="{ seen: m.is_seen }"
            >
              ✔✔
            </span>
          </div>

        </div>
      </template>

      <!-- TYPING INDICATOR (UI ONLY) -->
      <div class="typing" v-if="false">
        <span></span><span></span><span></span>
      </div>

      <div v-if="!messages.length && !loading" class="no-msg">
        <small>No messages.</small>
      </div>

    </div>
  </div>
</template>

<style scoped>
/* ===============================
   WRAPPER
   =============================== */
.wrapper {
  height: 100%;
  display: flex;
  flex-direction: column;
}

/* ===============================
   MESSAGE PANEL
   =============================== */
.messages {
  flex: 1;
  overflow-y: auto;
  padding: 18px 22px;
  display: flex;
  flex-direction: column;
  gap: 10px;

  background-image:
    radial-gradient(rgba(255,255,255,0.035) 1px, transparent 1px);
  background-size: 22px 22px;
}

/* ===============================
   DATE SEPARATOR
   =============================== */
.date-separator {
  align-self: center;
  font-size: 12px;
  color: #94a3b8;
  margin: 14px 0;
  padding: 4px 12px;
  border-radius: 999px;
  background: rgba(255,255,255,0.06);
}

/* ===============================
   BUBBLE BASE
   =============================== */
.bubble {
  max-width: 72%;
  padding: 10px 14px;
  border-radius: 16px;
  display: flex;
  flex-direction: column;
  line-height: 1.45;
  font-size: 14px;
  animation: fadeUp .25s ease;
  word-break: break-word;
}

/* ===============================
   CUSTOMER (LEFT)
   =============================== */
.bubble-you {
  align-self: flex-start;
  background: rgba(255,255,255,0.08);
  color: #f8fafc;
  border-top-left-radius: 6px;
  box-shadow: 0 6px 14px rgba(0,0,0,0.25);
}

/* ===============================
   AGENT (RIGHT)
   =============================== */
.bubble-me {
  align-self: flex-end;
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  color: white;
  border-top-right-radius: 6px;
  box-shadow: 0 6px 18px rgba(59,130,246,0.45);
  margin-right: 6px;
}

/* ===============================
   GROUPING
   =============================== */
.bubble + .bubble {
  margin-top: 2px;
}

/* ===============================
   TIME + STATUS
   =============================== */
.time {
  font-size: 10px;
  margin-top: 6px;
  opacity: 0.45;
  text-align: right;
}

.status {
  margin-left: 6px;
  font-size: 10px;
  opacity: 0.8;
}

.status.seen {
  color: #60a5fa;
}

/* ===============================
   IMAGE
   =============================== */
.img {
  max-width: 240px;
  border-radius: 10px;
  margin-bottom: 6px;
  box-shadow: 0 6px 16px rgba(0,0,0,0.35);
}

/* ===============================
   FILE
   =============================== */
.file {
  color: #93c5fd;
  text-decoration: none;
  font-weight: 500;
}

.file:hover {
  text-decoration: underline;
}

/* ===============================
   EMPTY / LOADING
   =============================== */
.no-room,
.no-msg,
.loading {
  text-align: center;
  margin-top: 25px;
  color: #94a3b8;
  font-size: 13px;
}

/* ===============================
   TYPING INDICATOR
   =============================== */
.typing {
  align-self: flex-start;
  background: rgba(255,255,255,0.08);
  padding: 10px 14px;
  border-radius: 14px;
  display: inline-flex;
  gap: 6px;
}

.typing span {
  width: 6px;
  height: 6px;
  background: #c7d2fe;
  border-radius: 50%;
  animation: blink 1.4s infinite both;
}

.typing span:nth-child(2) { animation-delay: .2s }
.typing span:nth-child(3) { animation-delay: .4s }

/* ===============================
   MOBILE
   =============================== */
@media (max-width: 768px) {
  .bubble {
    max-width: 88%;
  }

  .messages {
    padding: 12px;
  }
}

/* ===============================
   ANIMATION
   =============================== */
@keyframes fadeUp {
  from {
    opacity: 0;
    transform: translateY(6px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes blink {
  0% { opacity: .2 }
  20% { opacity: 1 }
  100% { opacity: .2 }
}
</style>
