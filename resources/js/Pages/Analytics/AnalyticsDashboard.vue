<template>
  <AdminLayout>
    
    <!-- tombol export -->
    <v-row class="mb-4">
      <v-col cols="12" class="d-flex justify-end">
        <v-btn color="primary" @click="exportPDF">
          <v-icon left>mdi-file-pdf-box</v-icon>
          Export PDF
        </v-btn>
      </v-col>
    </v-row>

    <!-- WRAP AREA UNTUK EXPORT -->
    <v-container fluid class="mt-4" id="analytics-export-area">

      <OverviewCards />

      <v-row class="mt-6" dense>
        <v-col cols="12" lg="8">
          <MessagesTrendChart />
        </v-col>

        <v-col cols="12" lg="4">
          <AgentPerformanceChart />
        </v-col>
      </v-row>

      <v-row class="mt-6" dense>
        <v-col cols="12" lg="8">
          <PeakHourChart />
        </v-col>

        <v-col cols="12" lg="4">
          <SessionsTable />
        </v-col>
      </v-row>

    </v-container>

  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import OverviewCards from './OverviewCards.vue'
import MessagesTrendChart from './MessagesTrendChart.vue'
import AgentPerformanceChart from './AgentPerformanceChart.vue'
import PeakHourChart from './PeakHourChart.vue'
import SessionsTable from './SessionsTable.vue'

import jsPDF from "jspdf";
import html2canvas from "html2canvas";

const exportPDF = async () => {
  const dashboard = document.querySelector("#analytics-export-area");

  if (!dashboard) {
    console.error("Dashboard not found for export");
    return;
  }

  // generate screenshot
  const canvas = await html2canvas(dashboard, {
    scale: 2,
    useCORS: true,
  });

  const imgData = canvas.toDataURL("image/png");
  const pdf = new jsPDF("p", "mm", "a4");

  const pdfWidth = pdf.internal.pageSize.getWidth();
  const imgHeight = (canvas.height * pdfWidth) / canvas.width;

  pdf.addImage(imgData, "PNG", 0, 0, pdfWidth, imgHeight);
  pdf.save(`analytics-report-${Date.now()}.pdf`);
};
</script>
