<script setup>
import { ref, onMounted, nextTick, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
  roomId: Number
})

const messages = ref([])
const typing = ref(false)
const messageBox = ref(null)

/* ======================= LOAD CHAT ======================= */
async function loadMessages() {
  if (!props.roomId) return
  const res = await axios.get(`/api/chats/${props.roomId}`)
  messages.value = res.data.messages
  autoScroll()
}

/* ======================= REALTIME LISTEN ======================= */
function listenRealtime() {
  if (!props.roomId) return

  window.Echo.private(`chat.${props.roomId}`)
    .listen('MessageSent', (e) => {
      messages.value.push({
        id: e.id,
        sender: e.sender,
        text: e.text,
        time: e.time,
        is_me: e.is_me
      })
      autoScroll()
    })
}

/* ======================= AUTO SCROLL ======================= */
function autoScroll() {
  nextTick(() => {
    if (!messageBox.value) return
    messageBox.value.scrollTop = messageBox.value.scrollHeight
  })
}

watch(() => props.roomId, () => {
  loadMessages()
  listenRealtime()
})

onMounted(() => {
  loadMessages()
  listenRealtime()
})
</script>

<template>
  <div ref="messageBox" style="max-height: calc(100vh - 230px); overflow-y:auto;">
    <div v-for="m in messages" :key="m.id" class="mb-2">

      <!-- Customer -->
      <div v-if="m.sender === 'customer'" class="d-flex">
        <v-avatar color="blue" size="26" class="me-2"><span class="white--text">C</span></v-avatar>
        <v-sheet class="pa-2 px-3" color="#e9f1ff" rounded style="max-width: 70%;">
          <div class="text-body-2">{{ m.text }}</div>
          <small class="text-grey text-caption">{{ m.time }}</small>
        </v-sheet>
      </div>

      <!-- Agent -->
      <div v-else class="d-flex justify-end">
        <v-sheet class="pa-2 px-3 d-flex flex-column" color="#dcf8c6" rounded style="max-width: 70%;">
          <div class="text-body-2">{{ m.text }}</div>
          <small class="text-grey text-caption">{{ m.time }}</small>
        </v-sheet>
      </div>

    </div>
  </div>
</template>
