<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue'

const industries = [
  {
    title: 'UMKM & Online Seller',
    desc: 'Kelola chat pelanggan tanpa kehilangan pesan, meski dikerjakan oleh tim kecil.',
    points: [
      'Satu nomor WhatsApp, banyak admin',
      'Riwayat chat tersimpan',
      'Respon cepat & rapi',
    ],
  },
  {
    title: 'Startup & SaaS',
    desc: 'Skalakan customer support tanpa chaos dengan workflow terstruktur.',
    points: [
      'Auto routing ke agent online',
      'Ticketing & tagging',
      'Analytics performa agent',
    ],
  },
  {
    title: 'Enterprise & Korporasi',
    desc: 'Kontrol penuh, audit trail, dan integrasi sistem internal.',
    points: [
      'Unlimited agent',
      'SLA & audit log',
      'API & webhook integration',
    ],
  },
]

const roles = [
  {
    role: 'Owner / Management',
    value: 'Visibilitas penuh terhadap performa layanan & kepuasan pelanggan.',
  },
  {
    role: 'Customer Service Manager',
    value: 'Distribusi chat adil, monitoring agent, dan pengelolaan ticket.',
  },
  {
    role: 'Agent',
    value: 'Fokus menjawab pelanggan tanpa konflik chat.',
  },
  {
    role: 'IT / Developer',
    value: 'Integrasi mudah dengan sistem internal via API & webhook.',
  },
]
</script>

<template>
  <PublicLayout title="Solusi – WABA">

    <!-- ================= HERO ================= -->
    <v-container class="py-20 text-center">
      <h1 class="text-h4 font-weight-bold mb-4">
        Solusi WhatsApp Customer Service
      </h1>
      <p
        class="text-body-1 text-grey-darken-1 mx-auto"
        style="max-width:760px"
      >
        WABA dirancang untuk berbagai skala bisnis dengan kebutuhan
        operasional yang berbeda, namun satu tujuan:
        <strong>layanan pelanggan yang rapi, cepat, dan terukur.</strong>
      </p>
    </v-container>

    <!-- ================= INDUSTRY SOLUTIONS ================= -->
    <v-container class="pb-24">
      <h2 class="text-h5 font-weight-bold mb-10 text-center">
        Solusi Berdasarkan Jenis Bisnis
      </h2>

      <v-row align="stretch">
        <v-col
          v-for="item in industries"
          :key="item.title"
          cols="12"
          md="4"
        >
          <v-card class="solution-card">
            <h3 class="mb-2">{{ item.title }}</h3>
            <p class="text-body-2 text-grey-darken-1 mb-4">
              {{ item.desc }}
            </p>

            <ul>
              <li v-for="p in item.points" :key="p">{{ p }}</li>
            </ul>
          </v-card>
        </v-col>
      </v-row>
    </v-container>

    <!-- ================= WORKFLOW ================= -->
    <v-container class="pb-24">
      <h2 class="text-h5 font-weight-bold mb-4 text-center">
        Alur Kerja Sistem WABA
      </h2>

      <p
        class="text-body-2 text-grey-darken-1 text-center mb-12 mx-auto"
        style="max-width:720px"
      >
        Dari pesan masuk hingga penyelesaian ticket,
        semua proses tercatat dan terkontrol.
      </p>

      <div class="workflow">
        <div class="step">
          <div class="circle">1</div>
          <h4>Pesan Masuk</h4>
          <p>Pelanggan mengirim pesan ke WhatsApp bisnis.</p>
        </div>

        <div class="line"></div>

        <div class="step">
          <div class="circle">2</div>
          <h4>Auto Routing</h4>
          <p>Sistem mendistribusikan chat ke agent online.</p>
        </div>

        <div class="line"></div>

        <div class="step">
          <div class="circle">3</div>
          <h4>Agent Menangani</h4>
          <p>Agent membalas chat melalui dashboard.</p>
        </div>

        <div class="line"></div>

        <div class="step">
          <div class="circle">4</div>
          <h4>Ticket & Report</h4>
          <p>Chat ditutup atau dikonversi menjadi ticket.</p>
        </div>
      </div>
    </v-container>

    <!-- ================= ROLE VALUE ================= -->
    <v-container class="pb-24">
      <h2 class="text-h5 font-weight-bold mb-10 text-center">
        Nilai untuk Setiap Peran
      </h2>

      <v-row>
        <v-col
          v-for="r in roles"
          :key="r.role"
          cols="12"
          md="6"
        >
          <v-card class="role-card">
            <h4>{{ r.role }}</h4>
            <p class="text-body-2 text-grey-darken-1">
              {{ r.value }}
            </p>
          </v-card>
        </v-col>
      </v-row>
    </v-container>

    <!-- ================= CTA ================= -->
    <v-container class="pb-28 text-center">
      <h3 class="text-h6 mb-4">
        Siap menerapkan workflow customer service yang lebih profesional?
      </h3>
      <v-btn size="large" color="primary" href="/register">
        Mulai Sekarang
      </v-btn>
    </v-container>

  </PublicLayout>
</template>

<style scoped>
/* ================= SOLUTION CARD ================= */
.solution-card {
  height: 100%;
  padding: 28px;
  border-radius: 20px;
  background: #ffffff;
  box-shadow: 0 10px 30px rgba(0,0,0,.06);
}

.solution-card h3 {
  font-weight: 700;
}

.solution-card ul {
  padding-left: 18px;
}
.solution-card li {
  margin-bottom: 6px;
  color: #334155;
}

/* ================= WORKFLOW ================= */
.workflow {
  display: flex;
  align-items: flex-start;
  justify-content: center;
  gap: 16px;
  flex-wrap: wrap;
}

.step {
  width: 200px;
  text-align: center;
}

.circle {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: #2563eb;
  color: white;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 12px;
}

.line {
  width: 60px;
  height: 2px;
  background: #cbd5f5;
  margin-top: 24px;
}

/* ================= ROLE ================= */
.role-card {
  padding: 24px;
  border-radius: 18px;
  background: #f8fafc;
}

.role-card h4 {
  font-weight: 700;
  margin-bottom: 6px;
}

/* RESPONSIVE */
@media (max-width: 960px) {
  .line {
    display: none;
  }
}
</style>
