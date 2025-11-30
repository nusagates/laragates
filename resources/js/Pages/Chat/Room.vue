<script setup>
import { ref, watch, onMounted, nextTick } from 'vue'
import axios from 'axios'

const props = defineProps({
  roomId: Number
})

const loading = ref(false)
const messages = ref([])
const panel = ref(null)

/* LOAD MESSAGES */
async function loadMessages() {
  if (!props.roomId) return (messages.value = [])

  loading.value = true
  try {
    const res = await axios.get(`/chat/sessions/${props.roomId}/messages`)
    messages.value = res.data

    await nextTick()
    scrollBottom()
  } catch (e) { console.error(e) }
  finally { loading.value = false }
}

function scrollBottom() {
  if (panel.value) {
    panel.value.scrollTop = panel.value.scrollHeight
  }
}

watch(() => props.roomId, () => loadMessages())
onMounted(() => loadMessages())

/* BUBBLE CLASS */
function bubbleClass(m) {
  return m.is_outgoing ? 'bubble-me' : 'bubble-you'
}
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

      <div v-for="m in messages" :key="m.id" :class="['bubble', bubbleClass(m)]">

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

        <div class="time">{{ m.created_at }}</div>
      </div>

      <div v-if="!messages.length && !loading" class="no-msg">
        <small>No messages.</small>
      </div>
    </div>
  </div>
</template>

<style scoped>
.wrapper {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.messages {
  flex: 1;
  overflow-y: auto;
  padding: 18px 22px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

/* BUBBLE */
.bubble {
  max-width: 65%;
  padding: 10px 14px;
  border-radius: 14px;
  display: flex;
  flex-direction: column;
  line-height: 1.45;
  font-size: 14px;
}

/* CUSTOMER (LEFT) */
.bubble-you {
  align-self: flex-start;
  background: #ffffff;
  border: 1px solid #e0e0e0;
  color: #111;
}

/* AGENT (RIGHT) */
.bubble-me {
  align-self: flex-end;
  background: #d0f5c7;
  border: 1px solid #b6e8b3;
  color: #0a3b21;
}

.time {
  font-size: 11px;
  margin-top: 6px;
  opacity: 0.55;
  text-align: right;
}

.img {
  max-width: 220px;
  border-radius: 10px;
  margin-bottom: 6px;
}

.file {
  color: #2979ff;
  text-decoration: none;
}

.no-room, .no-msg, .loading {
  text-align: center;
  margin-top: 25px;
  color: #888;
}
</style>
