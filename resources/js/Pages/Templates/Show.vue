<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import axios from 'axios'

const props = defineProps({
  template: Object
})

const t = ref(props.template)

// Colors
const statusColor = (s) => {
  return {
    draft: 'grey',
    submitted: 'blue',
    approved: 'green',
    rejected: 'red'
  }[s] || 'orange'
}

// API Actions
const submitForApproval = async () => {
  await axios.post(`/templates/${t.value.id}/submit`)
  router.reload()
}

const approveTemplate = async () => {
  await axios.post(`/templates/${t.value.id}/approve`)
  router.reload()
}

const rejectTemplate = async () => {
  const reason = prompt('Alasan ditolak?')
  if (!reason) return
  await axios.post(`/templates/${t.value.id}/reject`, { reason })
  router.reload()
}

</script>

<template>
  <Head :title="`Template: ${t.name}`" />

  <AdminLayout>
    <template #title>
      Template: {{ t.name }}
    </template>

    <v-card class="pa-4" elevation="2">

      <h3 class="text-h6 font-weight-bold mb-4">Template Detail</h3>

      <v-row>
        <v-col cols="6">
          <p><strong>Name:</strong> {{ t.name }}</p>
          <p><strong>Category:</strong> {{ t.category }}</p>
          <p><strong>Language:</strong> {{ t.language }}</p>

          <p>
            <strong>Status:</strong>
            <v-chip :color="statusColor(t.status)" small dark>
              {{ t.status }}
            </v-chip>
          </p>

          <p><strong>Created:</strong> {{ t.created_at }}</p>
          <p><strong>Updated:</strong> {{ t.updated_at }}</p>
        </v-col>

        <v-col cols="12" class="mt-4">
          <h4 class="text-subtitle-1 mb-2">Header</h4>
          <v-sheet v-if="t.header" class="pa-3 mb-4" elevation="1">
            {{ t.header }}
          </v-sheet>

          <h4 class="text-subtitle-1 mb-2">Body</h4>
          <v-sheet class="pa-3 mb-4" elevation="1">
            {{ t.body }}
          </v-sheet>

          <h4 class="text-subtitle-1 mb-2">Footer</h4>
          <v-sheet v-if="t.footer" class="pa-3 mb-4" elevation="1">
            {{ t.footer }}
          </v-sheet>
        </v-col>
      </v-row>

      <!-- WORKFLOW BUTTON -->
      <div class="d-flex justify-end gap-2 mt-5">

        <!-- draft -->
        <v-btn
          v-if="t.status === 'draft'"
          color="blue"
          @click="submitForApproval"
        >
          Submit for Approval
        </v-btn>

        <!-- submitted -->
        <v-btn
          v-if="t.status === 'submitted'"
          color="green"
          @click="approveTemplate"
        >
          Approve
        </v-btn>

        <v-btn
          v-if="t.status === 'submitted'"
          color="red"
          @click="rejectTemplate"
        >
          Reject
        </v-btn>

        <v-btn variant="text" @click="$inertia.visit('/templates')">
          Back
        </v-btn>
      </div>

    </v-card>
  </AdminLayout>
</template>
