<template>
  <v-card elevation="2" class="pa-4" height="100%">
    <div class="text-h6 mb-4">Peak Hour Traffic</div>

    <div style="height: 300px;">
      <Bar :data="chartData" :options="chartOptions" />
    </div>
  </v-card>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";

// Chart.js
import { Bar } from "vue-chartjs";
import {
  Chart as ChartJS,
  BarElement,
  CategoryScale,
  LinearScale,
  Tooltip,
  Legend,
} from "chart.js";

// Register chart
ChartJS.register(BarElement, CategoryScale, LinearScale, Tooltip, Legend);

// =============================
// REACTIVE CHART DATA
// =============================
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
});

// =============================
// FETCH DATA
// =============================
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
