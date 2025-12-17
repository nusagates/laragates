<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link } from '@inertiajs/vue3'

const stats = {
  activeChats: 12,
  waitingChats: 4,
  agentsOnline: 3,
}

const waitingQueue = [
  { id: 101, customer: 'Andi', time: '2 min' },
  { id: 102, customer: 'Budi', time: '5 min' },
  { id: 103, customer: 'Siti', time: '7 min' },
]

const agents = [
  { name: 'Aldi', chats: 2, status: 'online' },
  { name: 'Rina', chats: 1, status: 'online' },
  { name: 'Bayu', chats: 0, status: 'online' },
]
</script>

<template>
  <Head title="Dashboard – WABA" />

  <AdminLayout>
    <template #title>Dashboard</template>

    <div class="pa-6 dashboard-bg">

      <!-- ================= KPI CARDS ================= -->
      <v-row class="mb-6">
        <v-col cols="12" md="4">
          <v-card class="pa-5 stat-card">
            <div class="d-flex align-center justify-space-between">
              <div>
                <div class="stat-label">Active Chats</div>
                <div class="stat-value">{{ stats.activeChats }}</div>
              </div>
              <v-icon color="primary" size="36">
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
                <div class="stat-value">{{ stats.waitingChats }}</div>
              </div>
              <v-icon color="warning" size="36">
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
                <div class="stat-value">{{ stats.agentsOnline }}</div>
              </div>
              <v-icon color="success" size="36">
                mdi-account-group-outline
              </v-icon>
            </div>
          </v-card>
        </v-col>
      </v-row>

      <!-- ================= MAIN PANELS ================= -->
      <v-row align="stretch">

        <!-- WAITING QUEUE -->
        <v-col cols="12" md="6" class="d-flex">
          <v-card class="pa-6 w-100 surface-card">
            <h3 class="panel-title">Waiting Queue</h3>
            <v-divider class="divider" />

            <div
              v-for="chat in waitingQueue"
              :key="chat.id"
              class="row-item"
            >
              <div>
                <div class="row-main">{{ chat.customer }}</div>
                <div class="row-sub">Waiting {{ chat.time }}</div>
              </div>

              <v-btn
                size="small"
                color="primary"
                variant="flat"
              >
                Take
              </v-btn>
            </div>

            <div v-if="!waitingQueue.length" class="empty-text">
              No waiting chats 🎉
            </div>
          </v-card>
        </v-col>

        <!-- AGENT STATUS -->
        <v-col cols="12" md="6" class="d-flex">
          <v-card class="pa-6 w-100 surface-card">
            <h3 class="panel-title">Agent Status</h3>
            <v-divider class="divider" />

            <div
              v-for="agent in agents"
              :key="agent.name"
              class="row-item"
            >
              <div>
                <div class="row-main">{{ agent.name }}</div>
                <div class="row-sub">{{ agent.chats }} active chats</div>
              </div>

              <v-chip
                size="small"
                color="success"
                variant="flat"
              >
                Online
              </v-chip>
            </div>
          </v-card>
        </v-col>

      </v-row>

      <!-- ================= QUICK ACTIONS ================= -->
      <v-card class="pa-6 mt-8 surface-card">
        <h3 class="panel-title">Quick Actions</h3>

        <div class="d-flex flex-wrap ga-3">
          <Link href="/chat">
            <v-btn color="primary" variant="flat">
              Open Chat
            </v-btn>
          </Link>

          <Link href="/broadcast">
            <v-btn color="secondary" variant="outlined">
              Broadcast
            </v-btn>
          </Link>

          <Link href="/tickets">
            <v-btn color="secondary" variant="outlined">
              Create Ticket
            </v-btn>
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
