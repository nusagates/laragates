<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import ContactDetail from './ContactDetail.vue'
import axios from 'axios'

/* ================= STATE ================= */
const contacts = ref([])
const query = ref('')
const activeContactId = ref(null)
const loading = ref(false)

/* ================= FETCH ================= */
async function loadContacts() {
  loading.value = true
  try {
    const res = await axios.get('/contacts')
    contacts.value = res.data.data ?? res.data
  } finally {
    loading.value = false
  }
}

onMounted(loadContacts)

/* ================= FILTER ================= */
const filteredContacts = computed(() => {
  if (!query.value) return contacts.value

  const q = query.value.toLowerCase()
  return contacts.value.filter(c =>
    (c.name ?? '').toLowerCase().includes(q) ||
    (c.phone ?? '').includes(q)
  )
})

/* ================= ACTION ================= */
function selectContact(contact) {
  activeContactId.value = contact.id
}
</script>

<template>
  <Head title="Contacts" />

  <AdminLayout>
    <template #title>Contacts</template>

    <div class="contacts-layout">

      <!-- ================= LEFT : LIST ================= -->
      <aside class="contacts-sidebar">
        <div class="sidebar-inner">

          <div class="search-box">
            <input
              v-model="query"
              placeholder="Search contact..."
            />
          </div>

          <div class="contact-list">
            <div
              v-for="c in filteredContacts"
              :key="c.id"
              class="contact-item"
              :class="{ active: c.id === activeContactId }"
              @click="selectContact(c)"
            >
              <div class="avatar">
                {{ (c.name ?? c.phone ?? 'U')[0] }}
              </div>

              <div class="meta">
                <div class="name">
                  {{ c.name || c.phone }}
                  <span v-if="c.is_vip" class="badge vip">VIP</span>
                  <span v-if="c.is_blacklisted" class="badge blacklist">BL</span>
                </div>

                <div class="phone">
                  {{ c.phone }}
                </div>
              </div>
            </div>

            <div v-if="!filteredContacts.length && !loading" class="empty">
              No contacts
            </div>
          </div>

        </div>
      </aside>

      <!-- ================= RIGHT : DETAIL ================= -->
      <section class="contacts-detail">
        <ContactDetail :contact-id="activeContactId" />
      </section>

    </div>
  </AdminLayout>
</template>

<style scoped>
/* ================= LAYOUT ================= */
.contacts-layout {
  height: calc(100vh - 120px);
  display: flex;
  gap: 16px;
}

/* ================= SIDEBAR ================= */
.contacts-sidebar {
  width: 320px;
  min-width: 320px;
  display: flex;
}

.sidebar-inner {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: radial-gradient(circle at top, #0f172a, #020617);
  border-radius: 14px;
  overflow: hidden;
}

/* ================= SEARCH ================= */
.search-box {
  padding: 12px;
}

.search-box input {
  width: 100%;
  padding: 8px 12px;
  border-radius: 10px;
  background: rgba(255,255,255,.06);
  border: 1px solid rgba(99,102,241,.35);
  color: #f1f5f9;
}

/* ================= LIST ================= */
.contact-list {
  flex: 1;
  overflow-y: auto;
}

/* ================= ITEM ================= */
.contact-item {
  display: flex;
  gap: 10px;
  padding: 10px 14px;
  cursor: pointer;
  transition: background .15s ease;
}

.contact-item:hover {
  background: rgba(255,255,255,.05);
}

.contact-item.active {
  background: rgba(99,102,241,.25);
  border-left: 3px solid #6366f1;
}

/* ================= AVATAR ================= */
.avatar {
  width: 36px;
  height: 36px;
  border-radius: 999px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #6366f1, #3b82f6);
  color: #fff;
  font-weight: 600;
}

/* ================= META ================= */
.meta {
  flex: 1;
  min-width: 0;
}

.name {
  font-size: 13px;
  font-weight: 600;
  color: #f8fafc;
  display: flex;
  align-items: center;
  gap: 6px;
}

.phone {
  font-size: 12px;
  color: #94a3b8;
}

/* ================= BADGES ================= */
.badge {
  font-size: 10px;
  padding: 2px 6px;
  border-radius: 999px;
  font-weight: 700;
}

.vip {
  background: rgba(59,130,246,.15);
  color: #60a5fa;
  border: 1px solid rgba(59,130,246,.35);
}

.blacklist {
  background: rgba(239,68,68,.15);
  color: #f87171;
  border: 1px solid rgba(239,68,68,.35);
}

/* ================= DETAIL ================= */
.contacts-detail {
  flex: 1;
  background: radial-gradient(circle at top, #0f172a, #020617);
  border-radius: 14px;
  overflow: hidden;
}

/* ================= EMPTY ================= */
.empty {
  text-align: center;
  opacity: .6;
  padding: 20px;
}
</style>
