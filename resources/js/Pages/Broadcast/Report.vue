<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const props = defineProps({
  campaigns: Object,
  filters: Object,
})

const search = ref(props.filters.search || '')
const status = ref(props.filters.status || '')

function applyFilter() {
  router.get(route('broadcast.report'), {
    search: search.value,
    status: status.value,
  }, {
    preserveScroll: true,
    preserveState: true,
  })
}

function statusColor(s) {
  return {
    draft: 'grey',
    pending_approval: 'orange',
    approved: 'blue',
    rejected: 'red',
    processing: 'info',
    done: 'green',
    failed: 'red-darken-2',
  }[s] || 'grey'
}
</script>

<template>
  <Head title="Broadcast Report" />

  <AdminLayout>
    <template #title>Broadcast Report</template>

    <!-- FILTER CARD -->
    <v-card elevation="2" class="pa-4 mb-4">
      <v-row>
        <v-col cols="12" md="5">
          <v-text-field
            v-model="search"
            label="Cari Campaign"
            prepend-inner-icon="mdi-magnify"
            clearable
          />
        </v-col>
        <v-col cols="12" md="4">
          <v-select
            v-model="status"
            label="Filter Status"
            :items="[
              { title: 'Draft', value: 'draft' },
              { title: 'Pending Approval', value: 'pending_approval' },
              { title: 'Approved', value: 'approved' },
              { title: 'Rejected', value: 'rejected' },
              { title: 'Processing', value: 'processing' },
              { title: 'Done', value: 'done' },
              { title: 'Failed', value: 'failed' },
            ]"
            clearable
          />
        </v-col>
        <v-col cols="12" md="3" class="d-flex align-end">
          <v-btn color="primary" @click="applyFilter" block>Apply Filter</v-btn>
        </v-col>
      </v-row>
    </v-card>

    <!-- TABLE CARD -->
    <v-card elevation="2" class="pa-4">
      <h3 class="text-h6 mb-4 font-weight-bold">Broadcast History</h3>

      <v-table density="compact" fixed-header height="500px">
        <thead>
          <tr>
            <th>Campaign</th>
            <th>Template</th>
            <th>Audience</th>
            <th>Sent</th>
            <th>Status</th>
            <th>Date</th>
          </tr>
        </thead>

        <tbody>
          <tr v-if="props.campaigns.data.length === 0">
            <td colspan="6" class="text-center py-6 text-grey">
              Tidak ada data broadcast.
            </td>
          </tr>

          <tr
            v-for="c in props.campaigns.data"
            :key="c.id"
          >
            <td class="text-body-2">
              <strong>{{ c.name }}</strong>
              <div class="text-caption text-grey-darken-1">
                by {{ c.creator?.name || 'Unknown' }}
              </div>
            </td>

            <td class="text-body-2">
              {{ c.template?.name || '-' }}
            </td>

            <td class="text-body-2">
              {{ c.total_targets }} target
            </td>

            <td class="text-body-2">
              {{ c.sent_count }} sent
              <span v-if="c.failed_count"> / {{ c.failed_count }} failed</span>
            </td>

            <td>
              <v-chip
                :color="statusColor(c.status)"
                size="small"
                label
                class="text-white"
              >
                {{ c.status.replace('_', ' ') }}
              </v-chip>
            </td>

            <td class="text-body-2">
              {{ new Date(c.created_at).toLocaleString() }}
            </td>
          </tr>
        </tbody>
      </v-table>

      <!-- PAGINATION -->
      <v-pagination
        v-model="props.campaigns.current_page"
        :length="props.campaigns.last_page"
        class="mt-4"
        @update:modelValue="(p) => router.get(route('broadcast.report'), { page: p }, { preserveScroll: true })"
      />
    </v-card>
  </AdminLayout>
</template>
