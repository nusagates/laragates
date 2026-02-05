<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";

/* ================= CHART.JS (LOGIC ASLI) ================= */
import { Bar } from "vue-chartjs";
import {
  Chart as ChartJS,
  BarElement,
  CategoryScale,
  LinearScale,
  Tooltip,
  Legend,
} from "chart.js";

ChartJS.register(BarElement, CategoryScale, LinearScale, Tooltip, Legend);

/* ================= REACTIVE DATA ================= */
const chartData = ref({
  labels: [],
  datasets: [
    {
      label: "Messages",
      data: [],
      backgroundColor: "#42A5F5",
    },
  ],
});

const chartOptions = ref({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: "top",
      labels: {
        color: "#e5e7eb",
        font: { size: 12 },
      },
    },
  },
  scales: {
    x: {
      ticks: { color: "#94a3b8" },
      grid: { color: "rgba(255,255,255,.06)" },
    },
    y: {
      ticks: { color: "#94a3b8" },
      grid: { color: "rgba(255,255,255,.06)" },
    },
  },
});

/* ================= FETCH DATA (LOGIC ASLI) ================= */
onMounted(loadData);

async function loadData() {
  try {
    const { data } = await axios.get("/analytics/peakhours");

    chartData.value = {
      labels: data.map(h => `${String(h.hour).padStart(2, "0")}:00`),
      datasets: [
        {
          label: "Messages",
          data: data.map(h => h.total ?? 0),
          backgroundColor: "#42A5F5",
        },
      ],
    };
  } catch (e) {
    console.error("PeakHour Error:", e);
  }
}
</script>

<template>
  <v-card class="analytics-card" height="100%">
    <div class="card-header">
      <div class="card-title">Peak Hour Traffic</div>
      <div class="card-subtitle">Hourly message distribution</div>
    </div>

    <div class="chart-wrap">
      <Bar :data="chartData" :options="chartOptions" />
    </div>
  </v-card>
</template>

<style scoped>
/* =========================================================
   DARK WABA – PEAK HOUR CHART
   Clean • Export-safe • Consistent
========================================================= */

.analytics-card {
  background: linear-gradient(180deg, #020617, #0f172a);
  border: 1px solid rgba(255,255,255,.06);
  border-radius: 16px;
  padding: 18px;
  color: #e5e7eb;
}

/* header */
.card-header {
  margin-bottom: 12px;
}
.card-title {
  font-size: 16px;
  font-weight: 600;
}
.card-subtitle {
  font-size: 12px;
  color: #94a3b8;
}

/* chart wrapper */
.chart-wrap {
  height: 300px;
}

/* remove shadow for pdf */
:deep(.v-card) {
  box-shadow: none !important;
}
</style>
