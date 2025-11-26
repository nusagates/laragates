<script setup>
import { ref, watch, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({ roomId: Number })

const loading = ref(false)
const messages = ref([])

async function loadMessages() {
  if (!props.roomId) return (messages.value = [])
  loading.value = true
  try {
    const res = await axios.get(`/chat/sessions/${props.roomId}/messages`)
    messages.value = res.data
  } catch (e) { console.error(e) }
  finally { loading.value = false }
}

watch(() => props.roomId, () => loadMessages())
onMounted(() => loadMessages())

function bubbleClass(m) { return m.is_outgoing ? 'bubble-me' : 'bubble-you' }
</script>

<template>
  <div class="room-wrapper">
    <div v-if="!roomId" class="text-center mt-10 text-grey"><small>Select a chat...</small></div>

    <div v-else class="messages-container">
      <div v-if="loading" class="text-center mt-4"><small>Loading...</small></div>

      <div v-else>
        <div v-for="m in messages" :key="m.id" :class="['bubble', bubbleClass(m)]">
          
          <!-- gambar -->
          <template v-if="m.media_type && m.media_type.startsWith('image')">
            <img :src="m.media_url" class="chat-img" />
          </template>

          <!-- file dokumen -->
          <template v-else-if="m.media_type && !m.media_type.startsWith('image')">
            <a :href="m.media_url" target="_blank" class="file-link">
              📎 {{ m.message || 'File Attachment' }}
            </a>
          </template>

          <!-- text -->
          <template v-else>
            <span>{{ m.message }}</span>
          </template>

          <div class="time">{{ m.created_at }}</div>
        </div>

        <div v-if="!messages.length" class="text-center mt-10 text-grey">
          <small>No messages</small>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.room-wrapper { height:100%; display:flex; flex-direction:column; }
.messages-container { flex:1; overflow-y:auto; padding:10px 20px; display:flex; flex-direction:column; gap:8px; }
.bubble { max-width:60%; padding:10px 14px; border-radius:12px; background:#fff; border:1px solid #e6e6e6; }
.bubble-me { background:#d0f5c7; align-self:flex-end; }
.time { font-size:11px; text-align:right; opacity:0.6; margin-top:3px; }
.chat-img { max-width:200px; border-radius:10px; display:block; margin-bottom:6px; }
.file-link { text-decoration:none; font-size:14px; }
</style>
