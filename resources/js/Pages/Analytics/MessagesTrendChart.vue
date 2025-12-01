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

ChartJS.register(LineElement, CategoryScale, LinearScale, PointElement, Tooltip, Legend);

const loading = ref(true);
const labels = ref([]);
const inbound = ref([]);
const outbound = ref([]);

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

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: "top" }
  }
};
</script>

<template>
  <v-card class="pa-4" height="330">
    <div class="text-h6 mb-2">Messages Trend (7 days)</div>

    <div v-if="loading" class="text-center mt-12">
      <v-progress-circular indeterminate color="primary" />
    </div>

    <div v-else style="height: 250px;">
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
              fill: true,
            },
            {
              label: 'Outbound',
              data: outbound,
              borderColor: '#4CAF50',
              backgroundColor: 'rgba(76,175,80,0.25)',
              tension: 0.3,
              fill: true,
            }
          ]
        }"
        :options="chartOptions"
      />
    </div>

  </v-card>
</template>
