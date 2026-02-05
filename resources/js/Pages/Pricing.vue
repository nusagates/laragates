<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue'
</script>

<template>
  <PublicLayout title="Harga – WABA">

    <!-- ================= HERO ================= -->
    <v-container class="py-20 text-center">
      <h1 class="text-h4 font-weight-bold mb-4">
        Paket Harga Fleksibel & Transparan
      </h1>
      <p
        class="text-body-1 text-grey-darken-1 mx-auto"
        style="max-width:760px"
      >
        Mulai gratis, tingkatkan kapan saja, dan scale tanpa batas.
        Cocok untuk UMKM, Startup, hingga Enterprise.
      </p>
    </v-container>

    <!-- ================= PRICING CARDS ================= -->
    <v-container class="pb-24">
      <v-row align="stretch" class="gy-8">

        <!-- STARTER -->
        <v-col cols="12" md="3">
          <v-card class="pricing-card">
            <div class="card-top">
              <h3 class="plan-name">Starter</h3>
              <p class="plan-desc">Untuk bisnis kecil & trial</p>
              <div class="price">Gratis</div>
            </div>

            <ul class="feature-list">
              <li>1 Agent</li>
              <li>Chat 1-on-1</li>
              <li>History chat</li>
              <li>Manual assignment</li>
            </ul>

            <v-btn block variant="outlined" color="primary" href="/register">
              Mulai Gratis
            </v-btn>
          </v-card>
        </v-col>

        <!-- BASIC -->
        <v-col cols="12" md="3">
          <v-card class="pricing-card">
            <div class="card-top">
              <h3 class="plan-name">Basic</h3>
              <p class="plan-desc">Untuk UMKM & tim kecil</p>
              <div class="price">Rp99.000 <span>/bulan</span></div>
            </div>

            <ul class="feature-list">
              <li>2 Agent</li>
              <li>Chat + Media</li>
              <li>Basic analytics</li>
              <li>Chat tagging</li>
            </ul>

            <v-btn block variant="outlined" color="primary" href="/register">
              Pilih Basic
            </v-btn>
          </v-card>
        </v-col>

        <!-- GROWTH -->
        <v-col cols="12" md="3">
          <v-card class="pricing-card">
            <div class="card-top">
              <h3 class="plan-name">Growth</h3>
              <p class="plan-desc">Untuk startup & tim support</p>
              <div class="price">Rp299.000 <span>/bulan</span></div>
            </div>

            <ul class="feature-list">
              <li>Multi-agent</li>
              <li>Auto routing</li>
              <li>Ticketing system</li>
              <li>Analytics & reporting</li>
              <li>Agent performance</li>
            </ul>

            <v-btn block variant="outlined" color="primary" href="/register">
              Mulai Growth
            </v-btn>
          </v-card>
        </v-col>

        <!-- SCALE -->
        <v-col cols="12" md="3">
          <v-card class="pricing-card">
            <div class="card-top">
              <h3 class="plan-name">Scale</h3>
              <p class="plan-desc">Untuk enterprise & korporasi</p>
              <div class="price">Custom</div>
            </div>

            <ul class="feature-list">
              <li>Unlimited agent</li>
              <li>API & Webhook</li>
              <li>SLA & audit log</li>
              <li>Custom workflow</li>
              <li>Dedicated support</li>
            </ul>

            <v-btn block variant="outlined" color="primary">
              Hubungi Sales
            </v-btn>
          </v-card>
        </v-col>

      </v-row>
    </v-container>

    <!-- ================= FEATURE COMPARISON ================= -->
    <v-container class="pb-24">
      <h2 class="text-h5 font-weight-bold text-center mb-4">
        Perbandingan Fitur Detail
      </h2>

      <p
        class="text-body-2 text-grey-darken-1 text-center mb-10 mx-auto"
        style="max-width:720px"
      >
        Bandingkan kapabilitas teknis setiap paket untuk memastikan
        solusi yang paling sesuai dengan skala dan kompleksitas bisnis Anda.
      </p>

      <div class="comparison-table">

        <div class="row header">
          <div class="cell feature">Fitur</div>
          <div class="cell">Starter</div>
          <div class="cell">Basic</div>
          <div class="cell highlight">Growth</div>
          <div class="cell">Scale</div>
        </div>

        <div class="row">
          <div class="cell feature">Jumlah Agent</div>
          <div class="cell">1</div>
          <div class="cell">2</div>
          <div class="cell highlight">Multi</div>
          <div class="cell">Unlimited</div>
        </div>

        <div class="row">
          <div class="cell feature">Chat Multi-Agent</div>
          <div class="cell">–</div>
          <div class="cell">–</div>
          <div class="cell highlight">✔</div>
          <div class="cell">✔</div>
        </div>

        <div class="row">
          <div class="cell feature">Auto Routing</div>
          <div class="cell">–</div>
          <div class="cell">–</div>
          <div class="cell highlight">✔</div>
          <div class="cell">✔</div>
        </div>

        <div class="row">
          <div class="cell feature">Ticketing System</div>
          <div class="cell">–</div>
          <div class="cell">–</div>
          <div class="cell highlight">✔</div>
          <div class="cell">✔</div>
        </div>

        <div class="row">
          <div class="cell feature">Analytics & Reporting</div>
          <div class="cell">–</div>
          <div class="cell">Basic</div>
          <div class="cell highlight">Advanced</div>
          <div class="cell">Enterprise</div>
        </div>

        <div class="row">
          <div class="cell feature">API & Webhook</div>
          <div class="cell">–</div>
          <div class="cell">–</div>
          <div class="cell highlight">–</div>
          <div class="cell">✔</div>
        </div>

        <div class="row">
          <div class="cell feature">SLA & Audit Log</div>
          <div class="cell">–</div>
          <div class="cell">–</div>
          <div class="cell highlight">–</div>
          <div class="cell">✔</div>
        </div>

        <div class="row">
          <div class="cell feature">Custom Workflow</div>
          <div class="cell">–</div>
          <div class="cell">–</div>
          <div class="cell highlight">–</div>
          <div class="cell">✔</div>
        </div>

      </div>
    </v-container>

    <!-- ================= FINAL CTA ================= -->
    <v-container class="pb-28 text-center">
      <h3 class="text-h6 mb-4">
        Butuh solusi khusus atau volume besar?
      </h3>
      <v-btn size="large" color="primary">
        Diskusi dengan Tim Kami
      </v-btn>
    </v-container>

  </PublicLayout>
</template>

<style scoped>
/* ================= PRICING CARD ================= */
.pricing-card {
  height: 100%;
  padding: 32px;
  border-radius: 20px;
  background: #ffffff;
  box-shadow: 0 10px 30px rgba(0,0,0,.06);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  border: 1px solid transparent;
  transition: transform .25s ease, box-shadow .25s ease, border .25s ease;
}

.pricing-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 18px 45px rgba(0,0,0,.12);
  border: 1px solid #2563eb;
}

.card-top {
  margin-bottom: 24px;
}

.plan-name {
  font-weight: 700;
  margin-bottom: 4px;
}
.plan-desc {
  font-size: 13px;
  color: #64748b;
  margin-bottom: 12px;
}
.price {
  font-size: 28px;
  font-weight: 700;
}
.price span {
  font-size: 13px;
  font-weight: 400;
  color: #64748b;
}

.feature-list {
  flex-grow: 1;
  padding-left: 18px;
  margin-bottom: 24px;
}
.feature-list li {
  margin-bottom: 6px;
  color: #334155;
}

.pricing-card:hover .v-btn {
  background-color: #2563eb !important;
  color: white !important;
}

/* ================= COMPARISON TABLE ================= */
.comparison-table {
  background: white;
  border-radius: 18px;
  box-shadow: 0 12px 35px rgba(0,0,0,.08);
  overflow: hidden;
}

.comparison-table .row {
  display: grid;
  grid-template-columns: 2.2fr repeat(4, 1fr);
  border-bottom: 1px solid #e5e7eb;
}
.comparison-table .row:last-child {
  border-bottom: none;
}

.comparison-table .cell {
  padding: 16px 18px;
  font-size: 14px;
  text-align: center;
  color: #334155;
}

.comparison-table .cell.feature {
  text-align: left;
  font-weight: 600;
  background: #f8fafc;
}

.comparison-table .header .cell {
  font-weight: 700;
  background: #f1f5f9;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: .05em;
}

.comparison-table .cell.highlight {
  background: rgba(37, 99, 235, 0.04);
  font-weight: 600;
}

@media (max-width: 960px) {
  .comparison-table {
    overflow-x: auto;
  }
  .comparison-table .row {
    min-width: 720px;
  }
}
</style>
