<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";

const metrics = ref({
  messages_today: 0,
  active_sessions: 0,
  avg_response_time: "0s",
  best_agent: "-",
});

const safe = (v, fallback = 0) =>
  v === null || v === undefined ? fallback : v;

onMounted(async () => {
  try {
    const res = await axios.get("/analytics/metrics");
    metrics.value = res.data;
  } catch (err) {
    console.error("Metrics Load Error:", err);
  }
});
</script>

<template>
  <v-row dense>
    <v-col cols="12" md="3">
      <v-card class="pa-4">
        <div class="text-h6">Messages Today</div>
        <div class="text-h4 font-weight-bold">
          {{ safe(metrics.messages_today) }}
        </div>
      </v-card>
    </v-col>

    <v-col cols="12" md="3">
      <v-card class="pa-4">
        <div class="text-h6">Active Sessions</div>
        <div class="text-h4 font-weight-bold">
          {{ safe(metrics.active_sessions) }}
        </div>
      </v-card>
    </v-col>

    <v-col cols="12" md="3">
      <v-card class="pa-4">
        <div class="text-h6">Avg Response Time</div>
        <div class="text-h5 font-weight-bold">
          {{ safe(metrics.avg_response_time, "0s") }}
        </div>
      </v-card>
    </v-col>

    <v-col cols="12" md="3">
      <v-card class="pa-4">
        <div class="text-h6">Best Agent</div>
        <div class="text-h5 font-weight-bold">
          {{ safe(metrics.best_agent, "-") }}
        </div>
      </v-card>
    </v-col>
  </v-row>
</template>
