<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, usePage, router, Link } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const page = usePage()

/* ================= DATA ================= */
const campaigns = computed(() => page.props.campaigns ?? {
  data: [],
  current_page: 1,
  last_page: 1,
})

const filters = ref({
  search: page.props.filters?.search ?? '',
  status: page.props.filters?.status ?? '',
})

/* ================= ACTIONS ================= */
function applyFilter() {
  router.get(
    route('broadcast.reports'),
    {
      search: filters.value.search || undefined,
      status: filters.value.status || undefined,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  )
}

function goPage(pageNumber) {
  router.get(
    route('broadcast.reports'),
    {
      page: pageNumber,
      search: filters.value.search || undefined,
      status: filters.value.status || undefined,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  )
}
</script>

<template>
  <Head title="Broadcast Report" />

  <AdminLayout>
    <template #title>Broadcast Report</template>

    <div class="report-dark">

      <!-- ================= FILTER ================= -->
      <v-card class="card pa-4 mb-4">
        <div class="filters">
          <v-text-field
            v-model="filters.search"
            placeholder="Search campaign name..."
            density="compact"
            hide-details
            clearable
            @keyup.enter="applyFilter"
          />

          <v-select
            v-model="filters.status"
            :items="[
              'draft',
              'pending_approval',
              'approved',
              'scheduled',
              'running',
              'done',
              'failed'
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

      <!-- ================= TABLE ================= -->
      <v-card class="card pa-0">
        <v-table>
          <thead>
            <tr>
              <th>Campaign</th>
              <th>Template</th>
              <th class="text-center">Targets</th>
              <th class="text-center">Sent</th>
              <th class="text-center">Failed</th>
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
                  by {{ c.created_by ?? '-' }}
                </div>
              </td>

              <td>{{ c.template?.name ?? '-' }}</td>

              <td class="text-center">
                {{ c.targets_count ?? 0 }}
              </td>

              <td class="text-center">
                {{ c.sent_count ?? 0 }}
              </td>

              <td class="text-center danger">
                {{ c.failed_count ?? 0 }}
              </td>

              <td>
                {{ new Date(c.created_at).toLocaleString() }}
              </td>

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

      <!-- ================= PAGINATION ================= -->
      <div class="d-flex justify-end mt-4">
        <v-pagination
          :model-value="campaigns.current_page"
          :length="campaigns.last_page"
          @update:modelValue="goPage"
        />
      </div>

    </div>
  </AdminLayout>
</template>

<style scoped>
/* =====================================================
   BROADCAST REPORT – DARK WABA POLISH
===================================================== */

.report-dark {
  color: #e5e7eb;
}

/* CARD */
.card {
  background: linear-gradient(180deg, #020617, #0f172a);
  border: 1px solid rgba(255,255,255,.06);
  border-radius: 16px;
}

/* FILTER */
.filters {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

/* TABLE */
:deep(.v-table),
:deep(.v-table__wrapper),
:deep(table) {
  background: transparent !important;
}

:deep(thead th) {
  background: #020617 !important;
  color: #94a3b8 !important;
  font-size: 12px;
  border-bottom: 1px solid rgba(255,255,255,.06);
}

:deep(tbody td) {
  background: transparent !important;
  color: #e5e7eb !important;
  border-bottom: 1px solid rgba(255,255,255,.06);
}

.row-hover:hover {
  background: rgba(59,130,246,.12);
}

/* TEXT */
.muted {
  color: #94a3b8;
}

.danger {
  color: #f87171;
}

/* INPUT */
:deep(.v-field__input input),
:deep(.v-select__selection-text) {
  color: #e5e7eb !important;
}

:deep(.v-label) {
  color: #94a3b8 !important;
}
</style>
