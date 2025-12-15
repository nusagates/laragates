<template>
  <AdminLayout>
    <template #title>Broadcast Approvals</template>

    <v-row>
      <v-col cols="12">
        <v-card class="pa-4">
          <div class="d-flex justify-space-between align-center mb-4">
            <div>
              <h3 class="text-h6 mb-0">Broadcast Approval Requests</h3>
              <div class="text-caption">Pending approvals for broadcast campaigns</div>
            </div>

            <div>
              <v-select
                v-model="filters.action"
                :items="actions"
                dense
                hide-details
                style="width:200px"
                label="Filter action"
                @change="applyFilter"
                clearable
              />
            </div>
          </div>

          <v-table dense>
            <thead>
              <tr>
                <th>Campaign</th>
                <th>Requested By</th>
                <th>Notes</th>
                <th>Snapshot</th>
                <th>Requested At</th>
                <th style="width:200px">Action</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="a in approvals.data" :key="a.id">
                <td>
                  <div><strong>{{ a.campaign.name }}</strong></div>
                  <div class="text-caption">Template: {{ a.campaign.template?.name || '-' }}</div>
                </td>
                <td>{{ a.requester?.name || '—' }}</td>
                <td>{{ a.request_notes || '-' }}</td>
                <td>
                  <v-btn text small @click="viewSnapshot(a)">View</v-btn>
                </td>
                <td>{{ new Date(a.created_at).toLocaleString() }}</td>
                <td>
                  <v-btn small color="success" class="mr-2" @click="approve(a)">Approve</v-btn>
                  <v-btn small color="error" @click="reject(a)">Reject</v-btn>
                </td>
              </tr>

              <tr v-if="approvals.data.length === 0">
                <td colspan="6" class="text-center">No approval requests.</td>
              </tr>
            </tbody>
          </v-table>

          <div class="mt-4 d-flex justify-end">
            <v-pagination :length="approvals.last_page" v-model="page" @update:modelValue="gotoPage" />
          </div>
        </v-card>
      </v-col>
    </v-row>

    <v-dialog v-model="showSnapshot" max-width="800px">
      <v-card>
        <v-card-title>Snapshot</v-card-title>
        <v-card-text>
          <pre style="white-space:pre-wrap;">{{ snapshotText }}</pre>
        </v-card-text>
        <v-card-actions>
          <v-btn text @click="showSnapshot=false">Close</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { usePage, router } from '@inertiajs/vue3'
import { ref, reactive } from 'vue'
import axios from 'axios'

const props = usePage().props.value
const approvals = ref(props.approvals || { data: [], last_page: 1 })
const page = ref(props.approvals?.current_page || 1)
const filters = reactive({
  action: props.filters?.action || null
})
const actions = [
  { title: 'Requested', value: 'requested' },
  { title: 'Approved', value: 'approved' },
  { title: 'Rejected', value: 'rejected' }
]

const showSnapshot = ref(false)
const snapshotText = ref('')

function applyFilter() {
  router.get(route('broadcast.approvals.index'), { action: filters.action }, { preserveState: true, preserveScroll: true })
}

function gotoPage(p) {
  router.get(route('broadcast.approvals.index'), { page: p, action: filters.action }, { preserveState: true, preserveScroll: true })
}

function viewSnapshot(a) {
  snapshotText.value = JSON.stringify(a.snapshot || a.campaign, null, 2)
  showSnapshot.value = true
}

async function approve(a) {
  if (!confirm('Approve campaign "' + a.campaign.name + '" ?')) return
  try {
    await axios.post(route('broadcast.approvals.approve', { approval: a.id }))
    alert('Approved')
    // reload
    gotoPage(page.value)
  } catch (e) {
    console.error(e)
    alert('Error approving')
  }
}

async function reject(a) {
  const reason = prompt('Reason for rejection:')
  if (reason === null) return
  try {
    await axios.post(route('broadcast.approvals.reject', { approval: a.id }), { note: reason })
    alert('Rejected')
    gotoPage(page.value)
  } catch (e) {
    console.error(e)
    alert('Error rejecting')
  }
}
</script>

<style scoped>
pre { background:#f7f7f7;padding:12px;border-radius:8px; }
</style>
