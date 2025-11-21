<script setup>
import { Head, Link } from '@inertiajs/vue3'
</script>

<template>
  <Head title="WhatsApp Business Solution" />

  <v-app
    style="
      background: linear-gradient(rgba(0,0,0,0.55), rgba(0,0,0,0.55)),
      url('/images/landing-bg.jpg');
      background-size: cover;
      background-position: center;
      min-height: 100vh;
    "
  >

    <!-- NAVBAR -->
    <v-app-bar flat color="rgba(0,0,0,0.3)" density="comfortable">
      <v-toolbar-title class="text-white text-h6 font-weight-bold">
        WABA Customer Service
      </v-toolbar-title>

      <v-spacer />

      <v-btn href="/login" variant="text" class="text-white">Login</v-btn>
      <v-btn href="/register" variant="text" class="text-white">Register</v-btn>
    </v-app-bar>

    <v-main>

      <!-- HERO SECTION -->
      <v-container class="text-center py-16">
        <h1 class="text-h3 text-white font-weight-bold mb-4">
          Solusi WhatsApp Business API Terintegrasi
        </h1>

        <p class="text-body-1 text-white mb-8">
          Platform Customer Service lengkap untuk mengelola percakapan WhatsApp secara profesional.  
          Multi-agent, dashboard real-time, automasi, dan integrasi API siap pakai.
        </p>

        <v-btn href="/register" color="primary" size="large">
          Mulai Sekarang
        </v-btn>
      </v-container>

      <!-- FITUR SECTION -->
      <v-container class="py-10">
        <h2 class="text-h4 text-center font-weight-bold mb-8" style="color:white">
          Fitur Unggulan
        </h2>

        <v-row justify="center" align="stretch" class="text-center">

          <v-col cols="12" md="4">
            <v-card class="pa-6" elevation="10" rounded="lg">
              <h3 class="text-h6 font-weight-bold mb-2">Multi-Agent Chat</h3>
              <p class="text-body-2">Banyak CS bisa membalas dari satu nomor WhatsApp.</p>
            </v-card>
          </v-col>

          <v-col cols="12" md="4">
            <v-card class="pa-6" elevation="10" rounded="lg">
              <h3 class="text-h6 font-weight-bold mb-2">Routing & Automasi</h3>
              <p class="text-body-2">Pesan otomatis dialihkan ke agent yang tersedia.</p>
            </v-card>
          </v-col>

          <v-col cols="12" md="4">
            <v-card class="pa-6" elevation="10" rounded="lg">
              <h3 class="text-h6 font-weight-bold mb-2">Integrasi API / Webhook</h3>
              <p class="text-body-2">Terhubung dengan aplikasi Anda dengan mudah.</p>
            </v-card>
          </v-col>

          <v-col cols="12" md="4">
            <v-card class="pa-6" elevation="10" rounded="lg">
              <h3 class="text-h6 font-weight-bold mb-2">Template Blast</h3>
              <p class="text-body-2">Kirim pesan massal resmi via template WABA.</p>
            </v-card>
          </v-col>

          <v-col cols="12" md="4">
            <v-card class="pa-6" elevation="10" rounded="lg">
              <h3 class="text-h6 font-weight-bold mb-2">Chat History</h3>
              <p class="text-body-2">Riwayat percakapan tersimpan rapih dan aman.</p>
            </v-card>
          </v-col>

          <v-col cols="12" md="4">
            <v-card class="pa-6" elevation="10" rounded="lg">
              <h3 class="text-h6 font-weight-bold mb-2">Bot + Human Handover</h3>
              <p class="text-body-2">Bot menjawab awal, agent melanjutkan bila perlu.</p>
            </v-card>
          </v-col>

        </v-row>
      </v-container>

      <!-- CTA SECTION -->
      <v-container class="text-center py-10">
        <h2 class="text-h5 text-white font-weight-bold mb-4">
          Siap meningkatkan layanan pelanggan Anda?
        </h2>

        <v-btn href="/register" size="large" color="primary">
          Buat Akun Sekarang
        </v-btn>
      </v-container>

    </v-main>
  </v-app>

</template>
