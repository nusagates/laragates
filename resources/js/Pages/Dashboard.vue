<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, router } from '@inertiajs/vue3'

const props = defineProps({
  stats: {
    type: Object,
    required: true,
  },
  waiting_queue_list: {
    type: Array,
    default: () => [],
  },
  agents: {
    type: Array,
    default: () => [],
  },
})

function takeChat(sessionId) {
  router.post(route('dashboard.take-chat', sessionId), {}, {
    preserveScroll: true,
  })
}

function formatWaiting(minutes) {
  if (!minutes || minutes < 1) return 'just now'

  const h = Math.floor(minutes / 60)
  const m = minutes % 60

  if (h > 0) return `Waiting ${h}h ${m}m`
  return `Waiting ${m}m`
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
            <div class="d-flex align-center justify-space-between">
              <div>
                <div class="stat-label">Active Chats</div>
                <div class="stat-value">{{ stats.active_chats }}</div>
              </div>
              <v-icon size="36" color="primary">
                mdi-message-processing-outline
              </v-icon>
            </div>
          </v-card>
        </v-col>

        <v-col cols="12" md="4">
          <v-card class="pa-5 stat-card warning">
            <div class="d-flex align-center justify-space-between">
              <div>
                <div class="stat-label">Waiting Queue</div>
                <div class="stat-value">{{ stats.waiting_queue }}</div>
              </div>
              <v-icon size="36" color="warning">
                mdi-timer-sand
              </v-icon>
            </div>
          </v-card>
        </v-col>

        <v-col cols="12" md="4">
          <v-card class="pa-5 stat-card success">
            <div class="d-flex align-center justify-space-between">
              <div>
                <div class="stat-label">Agents Online</div>
                <div class="stat-value">{{ stats.agents_online }}</div>
              </div>
              <v-icon size="36" color="success">
                mdi-account-group-outline
              </v-icon>
            </div>
          </v-card>
        </v-col>
      </v-row>

      <!-- ================= MAIN ================= -->
      <v-row>

        <!-- WAITING QUEUE -->
        <v-col cols="12" md="6">
          <v-card class="pa-6 surface-card">
            <h3 class="panel-title">Waiting Queue</h3>
            <v-divider class="divider" />

            <template v-if="waiting_queue_list.length">
              <div
                v-for="chat in waiting_queue_list"
                :key="chat.id"
                class="row-item"
              >
                <div>
                  <div class="row-main">{{ chat.customer }}</div>
                  <div class="row-sub">
                    {{ formatWaiting(chat.waiting) }}
                  </div>
                </div>

                <v-btn
                  size="small"
                  color="primary"
                  variant="flat"
                  @click="takeChat(chat.id)"
                >
                  TAKE
                </v-btn>
              </div>
            </template>

            <div v-else class="empty-text">
              No waiting chats 🎉
            </div>
          </v-card>
        </v-col>

        <!-- AGENT STATUS -->
        <v-col cols="12" md="6">
          <v-card class="pa-6 surface-card">
            <h3 class="panel-title">Agent Status</h3>
            <v-divider class="divider" />

            <template v-if="agents.length">
              <div
                v-for="agent in agents"
                :key="agent.id"
                class="row-item"
              >
                <div>
                  <div class="row-main">{{ agent.name }}</div>
                  <div class="row-sub">
                    {{ agent.active_chats }} active chats
                  </div>
                </div>

                <v-chip
                  size="small"
                  :color="agent.status === 'online' ? 'success' : 'grey'"
                  variant="flat"
                >
                  {{ agent.status }}
                </v-chip>
              </div>
            </template>

            <div v-else class="empty-text">
              No agents online
            </div>
          </v-card>
        </v-col>

      </v-row>

      <!-- ================= QUICK ACTIONS ================= -->
      <v-card class="pa-6 mt-8 surface-card">
        <h3 class="panel-title">Quick Actions</h3>

        <div class="d-flex flex-wrap ga-3">
          <v-btn
            color="primary"
            variant="flat"
            @click="router.visit('/chat')"
          >
            Open Chat
          </v-btn>

          <v-btn
            color="secondary"
            variant="outlined"
            @click="router.visit('/broadcast')"
          >
            Broadcast
          </v-btn>

          <v-btn
            color="secondary"
            variant="outlined"
            @click="router.visit('/tickets')"
          >
            Create Ticket
          </v-btn>
        </div>
      </v-card>

    </div>
  </AdminLayout>
</template>

<style scoped>
.dashboard-bg {
  background: radial-gradient(circle at top, #0f172a, #020617);
  min-height: 100vh;
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

/* ROW */
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
