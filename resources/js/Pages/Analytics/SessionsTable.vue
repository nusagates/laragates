<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";

const sessions = ref([]);

onMounted(async () => {
  try {
    const res = await axios.get("/analytics/sessions");
    sessions.value = res.data;
  } catch (e) {
    console.error("Sessions Error:", e);
  }
});
</script>

<template>
  <v-card class="pa-4">
    <div class="text-h6 mb-2">Active Sessions</div>

    <v-table density="compact">
      <thead>
        <tr>
          <th>Session ID</th>
          <th>Customer</th>
          <th>Updated</th>
        </tr>
      </thead>

      <tbody>
        <tr v-if="sessions.length === 0">
          <td colspan="3" class="text-center text-grey">
            No active sessions available
          </td>
        </tr>

        <tr v-for="s in sessions" :key="s.id">
          <td>{{ s.id }}</td>
          <td>{{ s.customer_id }}</td>
          <td>{{ s.updated_at }}</td>
        </tr>
      </tbody>
    </v-table>
  </v-card>
</template>
