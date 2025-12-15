<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import axios from 'axios'

const props = usePage().props.value
const campaign = ref(props.campaign)
const targets = ref(props.targets)
const filters = ref(props.filters || { q: '', status: '' })
const page = ref(targets.value.current_page || 1)

function applyFilter() {
  router.get(route('broadcast.report.show', campaign.value.id), { q: filters.value.q, status: filters.value.status }, { preserveState: true })
}

async function retryTarget(targetId) {
  // optional: endpoint to requeue single target (implement backend separately if needed)
  try {
    await axios.post(`/broadcast/targets/${targetId}/retry`)
    alert('Retry requested')
    // reload page
    router.reload()
  } catch (e) {
    console.error(e)
    alert('Retry failed: ' + (e?.response?.data?.message ?? e.message))
  }
}
</script>

<template>
  <Head :title="`Campaign - ${campaign.name}`" />
  <AdminLayout>
    <template #title>Campaign Detail</template>

    <v-card class="pa-4 mb-4">
      <h3 class="text-h6">{{ campaign.name }}</h3>
      <div class="text-caption">Template: {{ campaign.template?.name || '-' }}</div>
      <div class="mt-2">Status: {{ campaign.status }} | Sent: {{ campaign.sent_count }} | Failed: {{ campaign.failed_count }}</div>
    </v-card>

    <v-card class="pa-4">
      <div class="d-flex justify-space-between align-center mb-4">
        <div>
          <h4 class="text-h6">Targets</h4>
          <div class="text-caption">Per-target delivery log</div>
        </div>

        <div class="d-flex" style="gap:8px;">
          <v-text-field v-model="filters.q" dense placeholder="Search phone / name / error" @keyup.enter="applyFilter" />
          <v-select dense v-model="filters.status" :items="['pending','sent','failed']" clearable placeholder="Status" />
          <v-btn @click="applyFilter">Filter</v-btn>
        </div>
      </div>

      <v-table dense>
        <thead>
          <tr>
            <th>#</th>
            <th>Phone</th>
            <th>Name</th>
            <th>Status</th>
            <th>Error</th>
            <th>Sent At</th>
            <th>Attempts</th>
            <th></th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="t in targets.data" :key="t.id">
            <td>{{ t.id }}</td>
            <td>{{ t.phone }}</td>
            <td>{{ t.name || '-' }}</td>
            <td>
              <v-chip :color="t.status === 'sent' ? 'green' : (t.status === 'failed' ? 'red' : 'grey')" size="small">
                {{ t.status }}
              </v-chip>
            </td>
            <td style="max-width:300px;white-space:pre-wrap">{{ t.error_message || '-' }}</td>
            <td>{{ t.sent_at ? new Date(t.sent_at).toLocaleString() : '-' }}</td>
            <td>{{ t.attempts ?? 0 }}</td>
            <td>
              <v-btn small dense @click="retryTarget(t.id)" :disabled="t.status === 'sent'">Retry</v-btn>
            </td>
          </tr>

          <tr v-if="targets.data.length === 0">
            <td colspan="8" class="text-center">No targets found.</td>
          </tr>
        </tbody>
      </v-table>

      <div class="mt-4 d-flex justify-end">
        <v-pagination :length="targets.last_page" v-model="targets.current_page" @update:modelValue="(p) => router.get(route('broadcast.report.show', campaign.id), { page: p }, { preserveState: true })" />
      </div>
    </v-card>
  </AdminLayout>
</template>
