<script setup>
import { Head } from '@inertiajs/vue3'
</script>

<template>
  <Head title="WABA – WhatsApp Customer Service Platform" />

  <v-app style="background:#f1f6fd">

    <!-- ================= NAVBAR ================= -->
    <v-app-bar flat color="white" elevation="0" height="72">
      <v-container class="d-flex align-center">
        <v-toolbar-title class="font-weight-bold text-h6">
          WABA
        </v-toolbar-title>

        <v-spacer />

        <v-btn variant="text">Produk</v-btn>
        <v-btn variant="text">Solusi</v-btn>
        <v-btn variant="text">Harga</v-btn>

        <v-btn
          class="ml-4"
          variant="outlined"
          color="primary"
          href="/login"
        >
          Sign In
        </v-btn>

        <v-btn
          class="ml-2"
          color="primary"
          href="/register"
        >
          Coba Gratis
        </v-btn>
      </v-container>
    </v-app-bar>

    <!-- ================= HERO ================= -->
    <v-main>
      <v-container class="py-20 text-center">
        <h1
          class="text-h3 font-weight-bold mb-6"
          style="color:#0f172a; max-width:900px; margin:auto"
        >
          WhatsApp Customer Service<br />
          untuk Tim Modern & Profesional
        </h1>

        <p
          class="text-body-1 mb-10"
          style="color:#475569; max-width:700px; margin:auto"
        >
          Kelola percakapan WhatsApp multi-agent dalam satu dashboard.
          Auto routing, bot & human handover, monitoring real-time,
          serta API siap integrasi untuk kebutuhan bisnis Anda.
        </p>

        <div>
          <v-btn color="primary" size="large" class="mr-4">
            Coba Gratis
          </v-btn>

          <v-btn variant="outlined" size="large">
            Hubungi Kami
          </v-btn>
        </div>
      </v-container>

      <!-- ================= VALUE PROPOSITION ================= -->
      <v-container class="py-12">
        <v-row justify="center" align="stretch">
          <!-- CARD 1 -->
          <v-col cols="12" md="4" class="d-flex">
            <v-card
              class="pa-6 w-100 d-flex flex-column"
              elevation="6"
              rounded="lg"
            >
              <h3 class="text-h6 font-weight-bold mb-2">
                Multi-Agent Real
              </h3>

              <p class="text-body-2 text-grey-darken-1 flex-grow-1">
                Banyak agent dapat melayani pelanggan dari satu nomor WhatsApp
                secara bersamaan tanpa konflik.
              </p>
            </v-card>
          </v-col>

          <!-- CARD 2 -->
          <v-col cols="12" md="4" class="d-flex">
            <v-card
              class="pa-6 w-100 d-flex flex-column"
              elevation="6"
              rounded="lg"
            >
              <h3 class="text-h6 font-weight-bold mb-2">
                Auto Routing Cerdas
              </h3>

              <p class="text-body-2 text-grey-darken-1 flex-grow-1">
                Chat otomatis dialihkan ke agent online dengan beban kerja
                paling seimbang.
              </p>
            </v-card>
          </v-col>

          <!-- CARD 3 -->
          <v-col cols="12" md="4" class="d-flex">
            <v-card
              class="pa-6 w-100 d-flex flex-column"
              elevation="6"
              rounded="lg"
            >
              <h3 class="text-h6 font-weight-bold mb-2">
                API & Webhook Ready
              </h3>

              <p class="text-body-2 text-grey-darken-1 flex-grow-1">
                Terintegrasi dengan sistem internal dan workflow bisnis Anda
                secara fleksibel.
              </p>
            </v-card>
          </v-col>
        </v-row>
      </v-container>

      <!-- ================= FEATURE GRID ================= -->
      <v-container class="py-14">
        <h2 class="text-h4 text-center font-weight-bold mb-10">
          Fitur Unggulan
        </h2>

        <v-row align="stretch">
          <v-col
            v-for="feature in [
              'Bot + Human Handover',
              'Broadcast Template',
              'Chat History',
              'Ticketing',
              'Agent Monitoring',
              'Analytics'
            ]"
            :key="feature"
            cols="12"
            md="4"
            class="d-flex"
          >
            <v-card
              class="pa-6 w-100 d-flex flex-column"
              rounded="lg"
              elevation="6"
            >
              <h3 class="text-h6 font-weight-bold mb-2">
                {{ feature }}
              </h3>

              <p class="text-body-2 text-grey-darken-1 flex-grow-1">
                Dirancang untuk workflow customer service profesional
                dan mudah dikembangkan.
              </p>
            </v-card>
          </v-col>
        </v-row>
      </v-container>

      <!-- ================= FINAL CTA ================= -->
      <v-container class="py-20 text-center">
        <h2 class="text-h4 font-weight-bold mb-6">
          Siap meningkatkan layanan pelanggan Anda?
        </h2>

        <v-btn color="primary" size="large">
          Buat Akun Sekarang
        </v-btn>
      </v-container>
    </v-main>
  </v-app>
</template>
