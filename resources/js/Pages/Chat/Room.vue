<script setup>
import { ref, nextTick, onMounted } from 'vue'

/* ===== Dummy messages + status ===== */
const messages = ref([
  { id: 1, from: 'customer', text: 'Halo, apakah produk tersedia?', time: '10:20', status: 'read' },
  { id: 2, from: 'agent', text: 'Halo! Tersedia, mau ukuran berapa?', time: '10:21', status: 'delivered' }
])

/* ===== File Upload (phase 1) ===== */
const fileInput = ref(null)
const uploadQueue = ref([])

function openFileDialog() {
  fileInput.value.click()
}
function attachFile(e) {
  const files = Array.from(e.target.files)
  files.forEach(file => {
    uploadQueue.value.push({
      file,
      name: file.name,
      type: file.type,
      progress: 0,
      previewUrl: file.type.includes('image') ? URL.createObjectURL(file) : null,
    })
  })
  simulateUpload()
}
function simulateUpload() {
  uploadQueue.value.forEach(f => {
    const interval = setInterval(() => {
      if (f.progress < 100) f.progress += 10
      else clearInterval(interval)
    }, 150)
  })
}

/* ===== Preview Modal ===== */
const showPreview = ref(false)
const previewImg = ref(null)
function openPreview(img) {
  previewImg.value = img
  showPreview.value = true
}

/* ===== Typing + Auto Scroll ===== */
const typing = ref(false)
const messageBox = ref(null)
const newMessage = ref('')

function autoScroll() {
  nextTick(() => {
    const box = messageBox.value
    box.scrollTop = box.scrollHeight
  })
}

function sendMessage() {
  if (!newMessage.value.trim()) return

  messages.value.push({
    id: Date.now(),
    from: 'agent',
    text: newMessage.value,
    time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
    status: 'sent'
  })

  newMessage.value = ''
  typing.value = false

  // Fake status change (delivered → read)
  setTimeout(() => messages.value[messages.value.length-1].status = 'delivered', 800)
  setTimeout(() => messages.value[messages.value.length-1].status = 'read', 1800)

  autoScroll()
}

/* Trigger typing indicator */
function onTyping() {
  typing.value = true
  setTimeout(() => typing.value = false, 1200)
}

onMounted(() => {
  autoScroll()
})
</script>

<template>
  <div>
    <!-- ===== MESSAGE AREA ===== -->
    <div ref="messageBox" style="max-height: calc(100vh - 230px); overflow-y:auto; padding-bottom:10px;">

      <div v-for="m in messages" :key="m.id" class="mb-2">
        <!-- Customer -->
        <div v-if="m.from === 'customer'" class="d-flex">
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
            <div class="d-flex justify-end align-center" style="gap:6px;">
              <small class="text-grey text-caption">{{ m.time }}</small>

              <v-icon v-if="m.status === 'sent'" size="16">mdi-check</v-icon>
              <v-icon v-if="m.status === 'delivered'" size="16">mdi-check-all</v-icon>
              <v-icon v-if="m.status === 'read'" size="16" color="#0b93f6">mdi-check-all</v-icon>
            </div>
          </v-sheet>
        </div>
      </div>

      <!-- Typing Indicator -->
      <div v-if="typing" class="d-flex mt-1">
        <v-avatar color="blue" size="22" class="me-2"></v-avatar>
        <v-sheet class="px-3 py-2" rounded color="#e9f1ff">
          <v-progress-circular indeterminate size="14" width="2" color="blue" />
        </v-sheet>
      </div>

    </div>

    <!-- ===== INPUT AREA ===== -->
    

    <!-- ===== IMAGE PREVIEW DIALOG ===== -->
    <v-dialog v-model="showPreview" width="380">
      <v-card>
        <v-img :src="previewImg" height="300" cover></v-img>
        <v-card-actions class="justify-end">
          <v-btn text @click="showPreview = false">Close</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>
