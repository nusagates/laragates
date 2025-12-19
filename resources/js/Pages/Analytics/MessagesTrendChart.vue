<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";

import {
  Chart as ChartJS,
  LineElement,
  CategoryScale,
  LinearScale,
  PointElement,
  Tooltip,
  Legend,
} from "chart.js";

import { Line } from "vue-chartjs";

/* ===== REGISTER (LOGIC ASLI) ===== */
ChartJS.register(
  LineElement,
  CategoryScale,
  LinearScale,
  PointElement,
  Tooltip,
  Legend
);

/* ===== STATE (LOGIC ASLI) ===== */
const loading = ref(true);
const labels = ref([]);
const inbound = ref([]);
const outbound = ref([]);

/* ===== FETCH DATA (LOGIC ASLI) ===== */
onMounted(async () => {
  try {
    const res = await axios.get("/analytics/trends");

    labels.value = res.data.labels;
    inbound.value = res.data.inbound.map(Number);
    outbound.value = res.data.outbound.map(Number);

  } catch (e) {
    console.error("Trend load error:", e);
  }

  loading.value = false;
});

/* ===== OPTIONS (LOGIC ASLI) ===== */
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: "top" }
  }
};
</script>

<template>
  <div class="chart-wrapper">

    <!-- TITLE -->
    <h4 class="chart-title">Messages Trend (7 days)</h4>

    <!-- LOADING -->
    <div v-if="loading" class="chart-loading">
      <v-progress-circular indeterminate color="primary" />
    </div>

    <!-- CHART -->
    <div v-else class="chart-canvas">
      <Line
        :data="{
          labels: labels,
          datasets: [
            {
              label: 'Inbound',
              data: inbound,
              borderColor: '#2196F3',
              backgroundColor: 'rgba(33,150,243,0.25)',
              tension: 0.3,
              fill: true
            },
            {
              label: 'Outbound',
              data: outbound,
              borderColor: '#4CAF50',
              backgroundColor: 'rgba(76,175,80,0.25)',
              tension: 0.3,
              fill: true
            }
          ]
        }"
        :options="chartOptions"
      />
    </div>

  </div>
</template>

<style scoped>
/* =========================================================
   DARK WABA – MESSAGES TREND CHART
   (PDF EXPORT SAFE)
========================================================= */

.chart-wrapper {
  height: 100%;
  display: flex;
  flex-direction: column;
}

/* title */
.chart-title {
  font-weight: 600;
  margin-bottom: 12px;
  color: #e5e7eb;
}

/* loading */
.chart-loading {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* canvas */
.chart-canvas {
  height: 260px;
}

/* force transparent canvas for dark bg + pdf */
:deep(canvas) {
  background: transparent !important;
}
</style>
