<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'

import OverviewCards from './OverviewCards.vue'
import MessagesTrendChart from './MessagesTrendChart.vue'
import AgentPerformanceChart from './AgentPerformanceChart.vue'
import PeakHourChart from './PeakHourChart.vue'
import SessionsTable from './SessionsTable.vue'

import jsPDF from "jspdf"
import html2canvas from "html2canvas"

/* ================= EXPORT PDF ================= */
const exportPDF = async () => {
  const target = document.getElementById("analytics-export-area")
  if (!target) return

  const canvas = await html2canvas(target, {
    scale: 2,
    useCORS: true,
    backgroundColor: "#020617",
  })

  const imgData = canvas.toDataURL("image/png")
  const pdf = new jsPDF("p", "mm", "a4")

  const pdfWidth = pdf.internal.pageSize.getWidth()
  const pdfHeight = (canvas.height * pdfWidth) / canvas.width

  pdf.addImage(imgData, "PNG", 0, 0, pdfWidth, pdfHeight)
  pdf.save(`analytics-report-${Date.now()}.pdf`)
}
</script>

<template>
  <AdminLayout>
    <template #title>Analytics</template>

    <!-- ================= HEADER ================= -->
    <div class="analytics-header">
      <div>
        <h3>Analytics Dashboard</h3>
        <p>Monitoring & performance overview</p>
      </div>

      <v-btn
        color="primary"
        prepend-icon="mdi-file-pdf-box"
        @click="exportPDF"
      >
        Export PDF
      </v-btn>
    </div>

    <!-- ================= EXPORT AREA ================= -->
    <div id="analytics-export-area" class="analytics-dark">

      <!-- OVERVIEW -->
      <OverviewCards />

      <!-- ROW 1 -->
      <v-row dense class="analytics-grid mt-6">
        <v-col cols="12" lg="8">
          <MessagesTrendChart />
        </v-col>

        <v-col cols="12" lg="4">
          <AgentPerformanceChart />
        </v-col>
      </v-row>

      <!-- ROW 2 -->
      <v-row dense class="analytics-grid mt-6">
        <v-col cols="12" lg="8">
          <PeakHourChart />
        </v-col>

        <v-col cols="12" lg="4">
          <SessionsTable />
        </v-col>
      </v-row>

    </div>
  </AdminLayout>
</template>

<style scoped>
/* =========================================================
   ANALYTICS – DARK WABA (SAFE & STABLE)
========================================================= */

.analytics-dark {
  color: #e5e7eb;
}

/* ================= HEADER ================= */
.analytics-header {
  background: linear-gradient(180deg, #020617, #0f172a);
  padding: 20px;
  border-radius: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.analytics-header p {
  color: #94a3b8;
}

/* ================= GRID ================= */
.analytics-grid {
  align-items: stretch;
}

/* ================= CARD FIX ================= */
:deep(.v-card) {
  background: linear-gradient(180deg, #020617, #0f172a);
  border: 1px solid rgba(255,255,255,.06);
  border-radius: 16px;
  color: #e5e7eb;
}

/* ================= CHART WRAPPER ================= */
/* WAJIB agar Chart.js tidak kabur */
:deep(.chart-wrapper) {
  height: 260px;
  position: relative;
}

/* ================= TABLE FIX ================= */
:deep(.sessions-card) {
  max-height: 330px;
  overflow-y: auto;
}

/* ================= TABLE DARK ================= */
:deep(.v-table),
:deep(table) {
  background: transparent !important;
}

:deep(tbody td),
:deep(thead th) {
  color: #e5e7eb !important;
  border-bottom: 1px solid rgba(255,255,255,.06);
}

/* ================= BUTTON ================= */
.v-btn {
  border-radius: 10px;
}

/* ================= PDF SAFETY ================= */
@media print {
  .analytics-header {
    display: none;
  }
}

/* ================= HEADER ================= */
.analytics-header {
  background: linear-gradient(180deg, #020617, #0f172a);
  padding: 20px;
  border-radius: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

/* 🔥 FIX UTAMA */
.analytics-header h3 {
  color: #e5e7eb !important;
  font-size: 18px;
  font-weight: 700;
  margin: 0;
}

.analytics-header p {
  color: #94a3b8;
  margin-top: 4px;
}

</style>
