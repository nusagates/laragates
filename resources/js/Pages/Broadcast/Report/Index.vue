<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, usePage, router, Link } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const page = usePage()

const campaigns = computed(() => page.props.campaigns ?? {
  data: [], last_page: 1, current_page: 1
})

const filters = ref({
  search: page.props.filters?.search ?? '',
  status: page.props.filters?.status ?? ''
})

function applyFilter() {
  router.get(
    route('broadcast.report'),
    {
      search: filters.value.search,
      status: filters.value.status,
    },
    { preserveState: true, preserveScroll: true }
  )
}
</script>

<template>
  <Head title="Broadcast Report" />

  <AdminLayout>
    <template #title>Broadcast Report</template>

    <div class="report-dark">

      <!-- HEADER -->
      <div class="header-card mb-6">
        <div>
          <h3>Broadcast Campaign Report</h3>
          <p>Summary & performance of broadcast campaigns</p>
        </div>
      </div>

      <!-- FILTER -->
      <v-card class="card pa-4 mb-4">
        <div class="filters">
          <v-text-field
            v-model="filters.search"
            placeholder="Search campaign..."
            density="compact"
            hide-details
            @keyup.enter="applyFilter"
          />

          <v-select
            v-model="filters.status"
            :items="[
              'draft','pending_approval','approved',
              'scheduled','running','done','failed'
            ]"
            placeholder="Status"
            clearable
            density="compact"
            hide-details
          />

          <v-btn color="primary" @click="applyFilter">
            Filter
          </v-btn>
        </div>
      </v-card>

      <!-- TABLE -->
      <v-card class="card pa-0">
        <v-table>
          <thead>
            <tr>
              <th>Campaign</th>
              <th>Template</th>
              <th>Targets</th>
              <th>Sent</th>
              <th>Failed</th>
              <th>Date</th>
              <th class="text-right">Action</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="c in campaigns.data"
              :key="c.id"
              class="row-hover"
            >
              <td>
                <strong>{{ c.name }}</strong>
                <div class="muted text-caption">
                  by {{ c.created_by }}
                </div>
              </td>

              <td>{{ c.template?.name || '-' }}</td>
              <td>{{ c.targets_count ?? '-' }}</td>
              <td>{{ c.sent_count }}</td>
              <td class="text-danger">{{ c.failed_count }}</td>
              <td>{{ new Date(c.created_at).toLocaleString() }}</td>

              <td class="text-right">
                <Link :href="route('broadcast.report.show', c.id)">
                  <v-btn
                    size="small"
                    variant="tonal"
                    color="primary"
                  >
                    Detail
                  </v-btn>
                </Link>
              </td>
            </tr>

            <tr v-if="campaigns.data.length === 0">
              <td colspan="7" class="text-center muted pa-6">
                No campaigns found.
              </td>
            </tr>
          </tbody>
        </v-table>
      </v-card>

      <!-- PAGINATION -->
      <div class="d-flex justify-end mt-4">
        <v-pagination
          v-model="campaigns.current_page"
          :length="campaigns.last_page"
          @update:modelValue="p =>
            router.get(
              route('broadcast.report'),
              { page: p },
              { preserveState:true, preserveScroll:true }
            )
          "
        />
      </div>

    </div>
  </AdminLayout>
</template>

<style scoped>
/* =========================================================
   BROADCAST REPORT – DARK WABA
========================================================= */

:global(:root) {
  --bg-main: #020617;
  --bg-soft: #0f172a;
  --border-soft: rgba(255,255,255,.06);

  --text-main: #e5e7eb;
  --text-muted: #94a3b8;

  --blue-strong: #3b82f6;
  --blue-soft: rgba(59,130,246,.12);
  --danger: #f87171;
}

/* WRAPPER */
.report-dark {
  color: var(--text-main);
}

/* HEADER */
.header-card {
  background: linear-gradient(180deg, var(--bg-main), var(--bg-soft));
  padding: 20px;
  border-radius: 16px;
}

.header-card p {
  color: var(--text-muted);
}

/* CARD */
.card {
  background: linear-gradient(180deg, var(--bg-main), var(--bg-soft));
  border: 1px solid var(--border-soft);
  border-radius: 16px;
}

/* FILTER */
.filters {
  display: flex;
  gap: 10px;
  align-items: center;
}

/* TABLE FORCE DARK */
:deep(.v-table),
:deep(.v-table__wrapper),
:deep(table) {
  background: transparent !important;
}

:deep(thead th) {
  background: #020617 !important;
  color: var(--text-muted) !important;
  border-bottom: 1px solid var(--border-soft);
}

:deep(tbody td) {
  background: transparent !important;
  color: var(--text-main) !important;
  border-bottom: 1px solid var(--border-soft);
}

.row-hover:hover {
  background: var(--blue-soft);
}

/* TEXT */
.muted {
  color: var(--text-muted);
}

.text-danger {
  color: var(--danger);
}

/* BUTTON */
.v-btn {
  border-radius: 10px;
}

/* INPUT FIX */
:deep(.v-field__input input),
:deep(.v-field__input textarea),
:deep(.v-select__selection-text) {
  color: var(--text-main) !important;
}

:deep(.v-label) {
  color: var(--text-muted) !important;
}

:deep(.v-field--active .v-label) {
  color: var(--blue-strong) !important;
}
</style>
