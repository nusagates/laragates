<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";

import {
  Chart as ChartJS,
  BarElement,
  CategoryScale,
  LinearScale,
  Tooltip,
  Legend
} from "chart.js";

import { Bar } from "vue-chartjs";

/* ===== CHART REGISTER (LOGIC ASLI) ===== */
ChartJS.register(BarElement, CategoryScale, LinearScale, Tooltip, Legend);

/* ===== STATE (LOGIC ASLI) ===== */
const loading = ref(true);
const labels = ref([]);
const counts = ref([]);

/* ===== FETCH DATA (LOGIC ASLI) ===== */
onMounted(async () => {
  try {
    const res = await axios.get("/analytics/agents");

    labels.value = res.data.labels;
    counts.value = res.data.counts.map(Number);

  } catch (e) {
    console.error("Agent load error:", e);
  }

  loading.value = false;
});

/* ===== CHART OPTIONS (LOGIC ASLI) ===== */
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
    <h4 class="chart-title">Agent Performance</h4>

    <!-- LOADING -->
    <div v-if="loading" class="chart-loading">
      <v-progress-circular indeterminate color="primary" />
    </div>

    <!-- CHART -->
    <div v-else class="chart-canvas">
      <Bar
        :data="{
          labels: labels,
          datasets: [
            {
              label: 'Messages Sent',
              data: counts,
              backgroundColor: '#42A5F5'
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
   DARK WABA – ANALYTICS CHART
   (SAFE FOR PDF EXPORT)
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

/* ensure canvas transparent for dark bg */
:deep(canvas) {
  background: transparent !important;
}
</style>
