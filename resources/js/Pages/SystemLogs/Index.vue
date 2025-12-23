<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  logs: Object,
  filters: Object,
})

const source = ref(props.filters?.source || '')
const q = ref(props.filters?.q || '')

/**
 * Apply filter & search
 */
watch([source, q], () => {
  router.get(
    route('system.logs'),
    {
      source: source.value || undefined,
      q: q.value || undefined,
    },
    {
      preserveState: true,
      replace: true,
    }
  )
})
</script>

<template>
  <AdminLayout title="System Logs">

    <div class="system-logs-root">

      <!-- HEADER -->
      <div class="header-card">
        <h1 class="title">System Logs</h1>
        <p class="subtitle">
          Aggregated logs from system, IAM, ticket, SLA, and user behavior
        </p>
      </div>

      <!-- FILTER BAR -->
      <div class="filter-bar">
  <!-- SOURCE FILTER -->
  <select v-model="source" class="filter-select">
    <option value="">All Sources</option>
    <option value="system">System</option>
    <option value="iam">IAM</option>
    <option value="ticket">Ticket</option>
    <option value="sla">SLA</option>
    <option value="behavior">Behavior</option>
  </select>

  <!-- SEARCH -->
  <input
    v-model="q"
    type="text"
    placeholder="Search event or description..."
    class="filter-input"
  />

  <!-- EXPORT -->
  <a
    :href="route('system.logs.export', {
      source: source || undefined,
      q: q || undefined
    })"
    class="export-btn"
  >
    Export CSV
  </a>
</div>


      <!-- TABLE CARD -->
      <div class="table-card">
        <div class="table-wrap">
          <table class="system-log-table">
            <thead>
              <tr>
                <th>Time</th>
                <th>Source</th>
                <th>Event</th>
                <th>Description</th>
                <th>User / Role</th>
                <th>IP</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="log in logs.data"
                :key="log.source + '-' + log.id"
              >
                <td class="mono">{{ log.created_at }}</td>

                <td>
                  <span class="badge" :class="'badge-' + log.source">
                    {{ log.source }}
                  </span>
                </td>

                <td class="mono event">
                  {{ log.event }}
                  <span
                    class="severity"
                    :class="'sev-' + log.severity"
                  >
                    {{ log.severity }}
                  </span>
                </td>

                <td class="desc">
                  {{ log.description ?? '-' }}
                </td>

                <td>
                  {{ log.role ?? log.user_id ?? '-' }}
                </td>

                <td class="mono">
                  {{ log.ip ?? '-' }}
                </td>
              </tr>

              <tr v-if="logs.data.length === 0">
                <td colspan="6" class="empty">
                  No logs found
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- PAGINATION -->
      <div class="pagination">
        <button
          v-for="link in logs.links"
          :key="link.label"
          v-html="link.label"
          :disabled="!link.url"
          @click="router.visit(link.url)"
          :class="['pager', { active: link.active }]"
        />
      </div>

    </div>

  </AdminLayout>
</template>

<style scoped>
/* ===============================
   ROOT OVERRIDE
=============================== */
.system-logs-root {
  color: #e5e7eb;
}

/* HEADER */
.header-card {
  margin-bottom: 20px;
}
.title {
  font-size: 26px;
  font-weight: 700;
  color: #ffffff;
}
.subtitle {
  font-size: 14px;
  color: #cbd5f5;
}

/* FILTER BAR */
.filter-bar {
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
}
.filter-select,
.filter-input {
  background: #020617;
  border: 1px solid #1e293b;
  color: #e5e7eb;
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 14px;
}
.filter-input {
  flex: 1;
}
.filter-select:focus,
.filter-input:focus {
  outline: none;
  border-color: #2563eb;
}
.export-btn {
  background: #2563eb;
  color: white;
  padding: 8px 14px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
  white-space: nowrap;
}

.export-btn:hover {
  background: #1d4ed8;
}


/* CARD */
.table-card {
  background: #020617;
  border: 1px solid #1e293b;
  border-radius: 14px;
  overflow: hidden;
}

/* TABLE */
.system-log-table {
  width: 100%;
  border-collapse: collapse;
}
.system-log-table thead {
  background: #020617;
}
.system-log-table th {
  padding: 14px 16px;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: .08em;
  color: #94a3b8;
  border-bottom: 1px solid #1e293b;
  text-align: left;
}
.system-log-table td {
  padding: 14px 16px;
  border-bottom: 1px solid #0f172a;
  color: #e5e7eb;
}
.system-log-table tbody tr:hover {
  background: rgba(30, 41, 59, 0.6);
}

/* TEXT */
.mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
  font-size: 12px;
}
.event {
  white-space: nowrap;
}
.desc {
  color: #cbd5f5;
}

/* BADGES */
.badge {
  padding: 4px 10px;
  border-radius: 9999px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
}
.badge-system { background: rgba(59,130,246,.25); color: #93c5fd; }
.badge-iam { background: rgba(168,85,247,.25); color: #d8b4fe; }
.badge-ticket { background: rgba(34,197,94,.25); color: #86efac; }
.badge-sla { background: rgba(239,68,68,.25); color: #fca5a5; }
.badge-behavior { background: rgba(249,115,22,.25); color: #fdba74; }

/* SEVERITY */
.severity {
  margin-left: 6px;
  padding: 2px 6px;
  border-radius: 6px;
  font-size: 10px;
  font-weight: 700;
}
.sev-info { background: rgba(34,197,94,.2); color: #86efac; }
.sev-warning { background: rgba(234,179,8,.25); color: #fde047; }
.sev-critical { background: rgba(239,68,68,.25); color: #fca5a5; }

/* EMPTY */
.empty {
  padding: 40px;
  text-align: center;
  color: #94a3b8;
}

/* PAGINATION */
.pagination {
  margin-top: 20px;
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
.pager {
  padding: 6px 14px;
  border-radius: 8px;
  border: 1px solid #1e293b;
  background: #020617;
  color: #e5e7eb;
  font-size: 13px;
}
.pager.active {
  background: #2563eb;
  border-color: #2563eb;
  color: #ffffff;
}
</style>
