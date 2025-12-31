<script setup>
import { ref, watch } from 'vue'
import axios from 'axios'
import { useToast } from 'vue-toastification'

const props = defineProps({
  contactId: {
    type: Number,
    required: false,
  },
})

const toast = useToast()
const loading = ref(false)
const contact = ref(null)

const newTag = ref('')

/* ================= FETCH CONTACT ================= */
async function loadContact() {
  if (!props.contactId) return
  loading.value = true

  try {
    const res = await axios.get(`/contacts/${props.contactId}`)
    contact.value = res.data
  } catch (e) {
    toast.error('Gagal memuat contact')
  } finally {
    loading.value = false
  }
}

watch(() => props.contactId, loadContact, { immediate: true })

/* ================= UPDATE CONTACT ================= */
async function saveContact() {
  try {
    await axios.put(`/contacts/${contact.value.id}`, {
      notes: contact.value.notes,
      is_vip: contact.value.is_vip,
      is_blacklisted: contact.value.is_blacklisted,
      tags: contact.value.tags,
    })
    toast.success('Contact updated')
  } catch {
    toast.error('Gagal update contact')
  }
}

/* ================= TAGS ================= */
function addTag() {
  if (!newTag.value) return
  if (!contact.value.tags) contact.value.tags = []

  const tag = newTag.value.toLowerCase()
  if (!contact.value.tags.includes(tag)) {
    contact.value.tags.push(tag)
    saveContact()
  }
  newTag.value = ''
}

function removeTag(tag) {
  contact.value.tags = contact.value.tags.filter(t => t !== tag)
  saveContact()
}
</script>

<template>
  <div class="contact-detail">

    <div v-if="!contact" class="empty">
      Select a contact
    </div>

    <div v-else>

      <!-- HEADER -->
      <div class="header">
        <div>
          <h2>{{ contact.name || contact.phone }}</h2>
          <p class="phone">{{ contact.phone }}</p>
        </div>

        <div class="badges">
          <span v-if="contact.is_vip" class="badge vip">VIP</span>
          <span v-if="contact.is_blacklisted" class="badge blacklist">BLACKLIST</span>
        </div>
      </div>

      <!-- STATS -->
      <div class="stats">
        <div><b>{{ contact.total_chats }}</b><span>Chats</span></div>
        <div><b>{{ contact.total_messages }}</b><span>Messages</span></div>
        <div>
          <b>{{ contact.last_contacted_at ?? '-' }}</b>
          <span>Last Contact</span>
        </div>
      </div>

      <!-- TOGGLES -->
      <div class="toggles">
        <label>
          <input type="checkbox" v-model="contact.is_vip" @change="saveContact" />
          VIP
        </label>

        <label>
          <input type="checkbox" v-model="contact.is_blacklisted" @change="saveContact" />
          Blacklist
        </label>
      </div>

      <!-- TAGS -->
      <div class="section">
        <h4>Tags</h4>

        <div class="tags">
          <span
            v-for="tag in contact.tags || []"
            :key="tag"
            class="tag"
          >
            {{ tag }}
            <button @click="removeTag(tag)">×</button>
          </span>
        </div>

        <input
          v-model="newTag"
          @keyup.enter="addTag"
          placeholder="Add tag"
        />
      </div>

      <!-- NOTES -->
      <div class="section">
        <h4>Notes</h4>
        <textarea
          v-model="contact.notes"
          rows="4"
          placeholder="Internal notes..."
          @blur="saveContact"
        />
      </div>

      <!-- ACTIONS -->
      <div class="actions">
        <a :href="`/chat?phone=${contact.phone}`" class="btn">
          Open Chat
        </a>
      </div>

    </div>
  </div>
</template>

<style scoped>
.contact-detail {
  height: 100%;
  padding: 20px;
  color: #e5e7eb;
}

.empty {
  text-align: center;
  opacity: .6;
  margin-top: 80px;
}

.header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 16px;
}

.phone {
  font-size: 13px;
  color: #94a3b8;
}

.badges {
  display: flex;
  gap: 6px;
}

.badge {
  font-size: 11px;
  padding: 4px 8px;
  border-radius: 999px;
}

.vip {
  background: rgba(59,130,246,.2);
  color: #60a5fa;
}

.blacklist {
  background: rgba(239,68,68,.2);
  color: #f87171;
}

.stats {
  display: flex;
  gap: 16px;
  margin-bottom: 16px;
}

.stats div {
  text-align: center;
}

.stats span {
  font-size: 11px;
  color: #94a3b8;
}

.toggles {
  display: flex;
  gap: 16px;
  margin-bottom: 16px;
}

.section {
  margin-bottom: 16px;
}

.tags {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
  margin-bottom: 6px;
}

.tag {
  background: rgba(99,102,241,.2);
  color: #c7d2fe;
  padding: 4px 8px;
  border-radius: 999px;
  font-size: 11px;
}

.tag button {
  margin-left: 4px;
  background: none;
  border: none;
  color: inherit;
  cursor: pointer;
}

textarea,
input {
  width: 100%;
  background: rgba(255,255,255,.05);
  border: 1px solid rgba(255,255,255,.1);
  border-radius: 8px;
  padding: 8px;
  color: #e5e7eb;
}

.actions {
  margin-top: 20px;
}

.btn {
  background: linear-gradient(135deg,#6366f1,#3b82f6);
  padding: 8px 14px;
  border-radius: 10px;
  color: white;
  text-decoration: none;
}
</style>
