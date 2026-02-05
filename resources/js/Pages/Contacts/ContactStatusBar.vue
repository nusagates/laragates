<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
  contactId: {
    type: Number,
    required: true,
  },
})

const loading = ref(true)
const error = ref(false)
const summary = ref(null)

async function loadSummary() {
  if (!props.contactId) return

  loading.value = true
  error.value = false

  try {
    const res = await axios.get(
      `/customers/${props.contactId}/summary`
    )
    summary.value = res.data
  } catch (e) {
    error.value = true
  } finally {
    loading.value = false
  }
}

onMounted(loadSummary)
watch(() => props.contactId, loadSummary)

function statusColor() {
  if (!summary.value) return 'text-gray-400'

  switch (summary.value.status) {
    case 'active':
      return 'text-green-400'
    case 'inactive':
      return 'text-yellow-400'
    case 'blocked':
      return 'text-red-400'
    default:
      return 'text-gray-400'
  }
}
</script>

<template>
  <div class="status-bar">
    <div v-if="loading" class="muted">
      Loading customer status...
    </div>

    <div v-else-if="error" class="muted">
      Unable to load customer status
    </div>

    <div v-else class="content">
      <span
        class="status"
        :class="statusColor()"
      >
        {{ summary.status.toUpperCase() }}
      </span>

      <span class="dot">•</span>

      <span class="activity">
        {{ summary.last_activity?.text ?? 'No recent activity' }}
      </span>

      <span class="dot">•</span>

      <span class="messages">
        {{ summary.counters.messages_today }} msg today
      </span>

      <template v-if="summary.flags.length">
        <span class="dot">•</span>
        <span class="flags">
          {{ summary.flags.join(', ') }}
        </span>
      </template>
    </div>
  </div>
</template>

<style scoped>
.status-bar {
  margin: 10px 0 14px;
  font-size: 13px;
}

.content {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  align-items: center;
}

.status {
  font-weight: 600;
  letter-spacing: .3px;
}

.dot {
  opacity: .5;
}

.activity,
.messages,
.flags {
  color: #cbd5f5;
}

.muted {
  color: #94a3b8;
}
</style>
