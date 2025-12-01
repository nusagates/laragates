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

ChartJS.register(BarElement, CategoryScale, LinearScale, Tooltip, Legend);

const loading = ref(true);
const labels = ref([]);
const counts = ref([]);

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
    <div class="text-h6 mb-2">Agent Performance</div>

    <div v-if="loading" class="text-center mt-12">
      <v-progress-circular indeterminate color="primary" />
    </div>

    <div v-else style="height: 250px;">
      <Bar
        :data="{
          labels: labels,
          datasets: [
            {
              label: 'Messages Sent',
              data: counts,
              backgroundColor: '#42A5F5',
            },
          ]
        }"
        :options="chartOptions"
      />
    </div>

  </v-card>
</template>
