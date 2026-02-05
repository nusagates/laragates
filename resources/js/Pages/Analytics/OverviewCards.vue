<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";

/* ================= LOGIC ASLI ================= */
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
  <v-row dense class="overview-grid">

    <!-- Messages Today -->
    <v-col cols="12" md="3">
      <v-card class="metric-card">
        <div class="metric-label">Messages Today</div>
        <div class="metric-value">
          {{ safe(metrics.messages_today) }}
        </div>
      </v-card>
    </v-col>

    <!-- Active Sessions -->
    <v-col cols="12" md="3">
      <v-card class="metric-card">
        <div class="metric-label">Active Sessions</div>
        <div class="metric-value">
          {{ safe(metrics.active_sessions) }}
        </div>
      </v-card>
    </v-col>

    <!-- Avg Response Time -->
    <v-col cols="12" md="3">
      <v-card class="metric-card">
        <div class="metric-label">Avg Response Time</div>
        <div class="metric-subvalue">
          {{ safe(metrics.avg_response_time, "0s") }}
        </div>
      </v-card>
    </v-col>

    <!-- Best Agent -->
    <v-col cols="12" md="3">
      <v-card class="metric-card">
        <div class="metric-label">Best Agent</div>
        <div class="metric-subvalue">
          {{ safe(metrics.best_agent, "-") }}
        </div>
      </v-card>
    </v-col>

  </v-row>
</template>

<style scoped>
/* =========================================================
   DARK WABA – OVERVIEW METRICS
   Clean • Consistent • PDF Export Safe
========================================================= */

.overview-grid {
  margin-bottom: 12px;
}

/* card */
.metric-card {
  background: linear-gradient(180deg, #020617, #0f172a);
  border: 1px solid rgba(255,255,255,.06);
  border-radius: 16px;
  padding: 18px;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

/* label */
.metric-label {
  font-size: 13px;
  color: #94a3b8;
  margin-bottom: 6px;
  letter-spacing: .3px;
}

/* big number */
.metric-value {
  font-size: 30px;
  font-weight: 700;
  color: #e5e7eb;
  line-height: 1.2;
}

/* text value */
.metric-subvalue {
  font-size: 20px;
  font-weight: 600;
  color: #e5e7eb;
}

/* make cards export-safe */
:deep(.v-card) {
  box-shadow: none !important;
  background-clip: padding-box;
}
</style>
