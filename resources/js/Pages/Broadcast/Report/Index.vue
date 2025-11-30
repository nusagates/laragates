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

    <v-card class="pa-4">
      <div class="d-flex justify-space-between align-center mb-4">
        <div>
          <h3 class="text-h6 mb-0">Broadcast Campaign Report</h3>
          <div class="text-caption">Summary of campaigns</div>
        </div>

        <div class="d-flex" style="gap:8px;">
          <v-text-field
            v-model="filters.search"
            dense placeholder="Search campaign..."
            @keyup.enter="applyFilter"
          />

          <v-select
            dense
            v-model="filters.status"
            :items="[
              'draft','pending_approval','approved',
              'scheduled','running','done','failed'
            ]"
            clearable
            placeholder="Status"
          />

          <v-btn color="primary" @click="applyFilter">Filter</v-btn>
        </div>
      </div>

      <v-table dense>
        <thead>
          <tr>
            <th>Campaign</th>
            <th>Template</th>
            <th>Targets</th>
            <th>Sent</th>
            <th>Failed</th>
            <th>Date</th>
            <th></th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="c in campaigns.data" :key="c.id">
            <td>
              <strong>{{ c.name }}</strong>
              <div class="text-caption">by {{ c.created_by }}</div>
            </td>
            <td>{{ c.template?.name || '-' }}</td>
            <td>{{ c.targets_count ?? '-' }}</td>
            <td>{{ c.sent_count }}</td>
            <td>{{ c.failed_count }}</td>
            <td>{{ new Date(c.created_at).toLocaleString() }}</td>
            <td>
              <Link :href="route('broadcast.report.show', c.id)">
                <v-btn small>Detail</v-btn>
              </Link>
            </td>
          </tr>

          <tr v-if="campaigns.data.length === 0">
            <td colspan="7" class="text-center">No campaigns found.</td>
          </tr>
        </tbody>
      </v-table>

      <div class="mt-4 d-flex justify-end">
        <v-pagination
          v-model="campaigns.current_page"
          :length="campaigns.last_page"
          @update:modelValue="p => 
            router.get(route('broadcast.report'), { page: p }, { preserveState:true, preserveScroll:true })
          "
        />
      </div>
    </v-card>
  </AdminLayout>
</template>
