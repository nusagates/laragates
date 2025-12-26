<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link, usePage, router } from '@inertiajs/vue3'
import { computed } from 'vue'

/**
 * ===============================
 * INERTIA PAGE PROPS (SAFE)
 * ===============================
 */
const page = usePage()

const stats = computed(() => page.props.stats ?? {
  active_chats: 0,
  waiting_queue: 0,
  agents_online: 0,
})

const waitingQueue = computed(() => page.props.waiting_queue_list ?? [])

/**
 * ===============================
 * ACTIONS
 * ===============================
 */
function takeChat(chatId) {
  router.post(
    route('dashboard.chat.take', chatId),
    {},
    {
      preserveScroll: true,
      onSuccess: () => {
        // reload halaman agar KPI & queue update
        router.reload()
      },
    }
  )
}

/**
 * ===============================
 * HELPERS
 * ===============================
 */
function formatWaiting(minutes) {
  if (!minutes || minutes <= 0) return '0 min'
  if (minutes < 60) return `${minutes} min`

  const h = Math.floor(minutes / 60)
  const m = minutes % 60
  return `${h}h ${m}m`
}
</script>

<template>
  <Head title="Dashboard – WABA" />

  <AdminLayout>
    <template #title>Dashboard</template>

    <div class="pa-6 dashboard-bg">

      <!-- ================= KPI ================= -->
      <v-row class="mb-6">
        <v-col cols="12" md="4">
          <v-card class="pa-5 stat-card">
            <div class="stat-label">Active Chats</div>
            <div class="stat-value">{{ stats.active_chats }}</div>
          </v-card>
        </v-col>

        <v-col cols="12" md="4">
          <v-card class="pa-5 stat-card warning">
            <div class="stat-label">Waiting Queue</div>
            <div class="stat-value">{{ stats.waiting_queue }}</div>
          </v-card>
        </v-col>

        <v-col cols="12" md="4">
          <v-card class="pa-5 stat-card success">
            <div class="stat-label">Agents Online</div>
            <div class="stat-value">{{ stats.agents_online }}</div>
          </v-card>
        </v-col>
      </v-row>

      <!-- ================= WAITING QUEUE ================= -->
      <v-row>
        <v-col cols="12" md="6">
          <v-card class="pa-6 surface-card">
            <h3 class="panel-title">Waiting Queue</h3>
            <v-divider class="divider" />

            <div
              v-for="chat in waitingQueue"
              :key="chat.id"
              class="row-item"
            >
              <div>
                <div class="row-main">{{ chat.customer }}</div>
                <div class="row-sub">
                  Waiting {{ formatWaiting(chat.waiting) }}
                </div>
              </div>

              <v-btn
                size="small"
                color="primary"
                variant="flat"
                @click="takeChat(chat.id)"
              >
                Take
              </v-btn>
            </div>

            <div
              v-if="waitingQueue.length === 0"
              class="empty-text"
            >
              No waiting chats 🎉
            </div>
          </v-card>
        </v-col>
      </v-row>

      <!-- ================= QUICK ACTIONS ================= -->
      <v-card class="pa-6 mt-8 surface-card">
        <h3 class="panel-title">Quick Actions</h3>

        <div class="d-flex flex-wrap ga-3">
          <Link href="/chat">
            <v-btn color="primary">Open Chat</v-btn>
          </Link>

          <Link href="/broadcast">
            <v-btn variant="outlined">Broadcast</v-btn>
          </Link>

          <Link href="/tickets">
            <v-btn variant="outlined">Create Ticket</v-btn>
          </Link>
        </div>
      </v-card>

    </div>
  </AdminLayout>
</template>

<style scoped>
.dashboard-bg {
  background: #0f172a;
}

/* KPI */
.stat-card {
  background: #111827;
  border: 1px solid #1f2937;
  border-radius: 14px;
}

.stat-card.warning {
  border-left: 4px solid #f59e0b;
}

.stat-card.success {
  border-left: 4px solid #22c55e;
}

.stat-label {
  font-size: 13px;
  color: #94a3b8;
}

.stat-value {
  font-size: 28px;
  font-weight: 700;
  color: #e5e7eb;
}

/* PANELS */
.surface-card {
  background: #111827;
  border: 1px solid #1f2937;
  border-radius: 14px;
}

.panel-title {
  font-weight: 600;
  margin-bottom: 14px;
  color: #e5e7eb;
}

.divider {
  border-color: #1f2937;
  margin-bottom: 16px;
}

/* ROW ITEM */
.row-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 14px;
}

.row-main {
  font-weight: 500;
  color: #e5e7eb;
}

.row-sub {
  font-size: 12px;
  color: #94a3b8;
}

.empty-text {
  font-size: 13px;
  color: #94a3b8;
}
</style>
