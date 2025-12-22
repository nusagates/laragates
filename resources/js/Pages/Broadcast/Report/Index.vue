<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, usePage, router, Link } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const page = usePage()

const campaigns = computed(() => page.props.campaigns ?? {
  data: [],
  current_page: 1,
  last_page: 1,
})

const filters = ref({
  search: page.props.filters?.search ?? '',
  status: page.props.filters?.status ?? '',
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

function goPage(pageNumber) {
  router.get(
    route('broadcast.report'),
    {
      page: pageNumber,
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
            <tr v-for="c in campaigns.data" :key="c.id">
              <td>
                <strong>{{ c.name }}</strong>
                <div class="muted text-caption">
                  by {{ c.created_by ?? '-' }}
                </div>
              </td>

              <td>{{ c.template?.name ?? '-' }}</td>
              <td>{{ c.targets_count ?? 0 }}</td>
              <td>{{ c.sent_count ?? 0 }}</td>
              <td class="text-danger">{{ c.failed_count ?? 0 }}</td>
              <td>{{ new Date(c.created_at).toLocaleString() }}</td>

              <td class="text-right">
                <Link :href="route('broadcast.report.show', c.id)">
                  <v-btn size="small" variant="tonal" color="primary">
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

      <!-- PAGINATION (FIXED) -->
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
