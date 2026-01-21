<script setup>
import { Head } from '@inertiajs/vue3'
</script>

<template>
  <Head title="WABA – WhatsApp Customer Service Platform" />

  <PublicLayout>

    <!-- ================= TOP ACTION (LOGIN / REGISTER ONLY) ================= -->
    <div class="top-action">
      <v-container class="d-flex justify-space-between align-center">
        <div class="logo">WABA</div>

        <div class="actions">
          <v-btn variant="text" href="/login">
            Sign In
          </v-btn>
        </div>
      </v-container>
    </div>

    <!-- ================= HERO ================= -->
    <section class="hero">
      <v-container>
        <v-row align="center">
          <!-- LEFT -->
          <v-col cols="12" md="6">
            <span class="eyebrow primary fade-up">
              WhatsApp Customer Service Platform
            </span>

            <h1 class="hero-title fade-up delay-1">
              Customer Service WhatsApp<br />
              <span>yang Siap Tumbuh Bersama Bisnis Anda</span>
            </h1>

            <p class="hero-desc fade-up delay-2">
              WABA membantu bisnis mengelola percakapan WhatsApp multi-agent
              secara terstruktur, terukur, dan scalable dengan workflow,
              monitoring, analytics, dan AI assistance.
            </p>

            <ul class="hero-points fade-up delay-3">
              <li>✔ Multi-agent tanpa konflik</li>
              <li>✔ Workflow & ticketing profesional</li>
              <li>✔ Audit log & SLA terukur</li>
            </ul>

            <div class="hero-cta fade-up delay-4">
              <v-btn size="large" variant="outlined" href="/login">
                Sign In
              </v-btn>
            </div>

            <small class="hero-note fade-up delay-5">
            </small>
          </v-col>

          <!-- RIGHT -->
          <v-col cols="12" md="6">
            <div class="hero-visual">
              <div class="bubble fade-up delay-2">
                Customer: Halo, mau tanya produk
              </div>
              <div class="bubble right fade-up delay-3">
                Agent CS: Baik, kami bantu ya 🙌
              </div>
              <div class="bubble ai fade-up delay-4">
                AI: Chat diarahkan ke agent tersedia
              </div>
            </div>
          </v-col>
        </v-row>
      </v-container>
    </section>

    <!-- ================= TRUST ================= -->
    <section class="trust">
      <v-container class="text-center">
        <p class="trust-text fade-up">
          Digunakan oleh tim customer service yang ingin bekerja lebih
          <strong>rapi</strong>, <strong>cepat</strong>, dan <strong>terukur</strong>.
        </p>
      </v-container>
    </section>

    <!-- ================= PROBLEM ================= -->
    <section class="section-soft">
      <v-container>
        <div class="section-head center">
          <span class="eyebrow fade-up">Masalah Umum</span>
          <h2 class="section-title fade-up delay-1">
            WhatsApp Tanpa Sistem Akan Menjadi Beban Operasional
          </h2>
          <p class="section-subtitle fade-up delay-2">
            Tanpa manajemen yang tepat, WhatsApp justru memperlambat tim customer service.
          </p>
        </div>

        <v-row class="mt-14">
          <v-col cols="12" md="3">
            <div class="card-hover fade-up delay-1">
              Chat tumpang tindih & tidak terkontrol
            </div>
          </v-col>
          <v-col cols="12" md="3">
            <div class="card-hover fade-up delay-2">
              Tidak jelas agent penanggung jawab
            </div>
          </v-col>
          <v-col cols="12" md="3">
            <div class="card-hover fade-up delay-3">
              Tidak ada SLA & audit trail
            </div>
          </v-col>
          <v-col cols="12" md="3">
            <div class="card-hover fade-up delay-4">
              Sulit evaluasi performa tim
            </div>
          </v-col>
        </v-row>
      </v-container>
    </section>

    <!-- ================= SOLUTION ================= -->
    <section class="section">
      <v-container>
        <div class="section-head center">
          <span class="eyebrow primary fade-up">Solusi</span>
          <h2 class="section-title fade-up delay-1">
            WABA Menjadikan WhatsApp Sebagai Sistem Kerja
          </h2>
        </div>

        <v-row class="mt-14">
          <v-col cols="12" md="4">
            <div class="feature-card fade-up delay-1">
              <h4>Multi-Agent Terpusat</h4>
              <p>Satu nomor WhatsApp dilayani banyak agent secara aman.</p>
            </div>
          </v-col>

          <v-col cols="12" md="4">
            <div class="feature-card fade-up delay-2">
              <h4>Ticketing & SLA</h4>
              <p>Setiap chat tercatat, terukur, dan dapat diaudit.</p>
            </div>
          </v-col>

          <v-col cols="12" md="4">
            <div class="feature-card fade-up delay-3">
              <h4>AI Assistance</h4>
              <p>AI membantu routing, ringkasan, dan efisiensi agent.</p>
            </div>
          </v-col>
        </v-row>
      </v-container>
    </section>

  </PublicLayout>
</template>

<style scoped>
/* ================= ANIMATION ================= */
.fade-up {
  animation: fadeUp .8s ease-out both;
}
.delay-1 { animation-delay: .15s }
.delay-2 { animation-delay: .3s }
.delay-3 { animation-delay: .45s }
.delay-4 { animation-delay: .6s }
.delay-5 { animation-delay: .75s }

@keyframes fadeUp {
  from {
    opacity: 0;
    transform: translateY(16px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* ================= TOP ACTION ================= */
.top-action {
  padding: 24px 0;
}
.logo {
  font-weight: 800;
  font-size: 20px;
  letter-spacing: .02em;
}
.actions {
  display: flex;
  gap: 12px;
}

/* ================= GLOBAL ================= */
.eyebrow {
  font-size: 12px;
  font-weight: 700;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: #94a3b8;
}
.eyebrow.primary {
  color: #2563eb;
}

/* HERO */
.hero {
  padding: 120px 0;
}
.hero-title {
  font-size: 46px;
  font-weight: 800;
  line-height: 1.15;
}
.hero-title span {
  color: #2563eb;
}
.hero-desc {
  margin-top: 18px;
  color: #475569;
}
.hero-points {
  list-style: none;
  padding-left: 0;
  margin-top: 16px;
}
.hero-cta {
  margin-top: 28px;
  display: flex;
  gap: 16px;
}
.hero-note {
  display: block;
  margin-top: 10px;
  color: #64748b;
}

/* VISUAL */
.hero-visual {
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.bubble {
  background: white;
  padding: 14px 18px;
  border-radius: 16px;
  box-shadow: 0 10px 30px rgba(0,0,0,.08);
}
.bubble.right {
  align-self: flex-end;
  border-left: 4px solid #2563eb;
}
.bubble.ai {
  background: #eff6ff;
  border-left: 4px solid #2563eb;
}

/* SECTIONS */
.section {
  padding: 120px 0;
}
.section-soft {
  padding: 120px 0;
  background: #f8fafc;
}
.section-head.center {
  text-align: center;
  max-width: 720px;
  margin: auto;
}
.section-title {
  font-size: 36px;
  font-weight: 800;
}
.section-subtitle {
  margin-top: 14px;
  color: #64748b;
}

/* CARDS */
.card-hover,
.feature-card {
  background: white;
  padding: 32px;
  border-radius: 20px;
  box-shadow: 0 20px 40px rgba(0,0,0,.06);
  transition: all .25s ease;
}
.card-hover:hover,
.feature-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 30px 60px rgba(0,0,0,.12);
}

/* TRUST */
.trust {
  padding: 48px 0;
}
.trust-text {
  color: #64748b;
}
</style>
