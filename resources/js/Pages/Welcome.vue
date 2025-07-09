<script setup>
import {Head} from '@inertiajs/vue3';
import {
  VApp,
  VAppBar,
  VContainer,
  VRow,
  VCol,
  VCard,
  VBtn,
  VSpacer
} from 'vuetify/components';

defineProps({
  canLogin: {
    type: Boolean,
  },
  canRegister: {
    type: Boolean,
  },
  laravelVersion: {
    type: String,
    required: true,
  },
  phpVersion: {
    type: String,
    required: true,
  },
});
</script>

<template>
  <Head title="Welcome"/>
  <v-app
      style="background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('/path/to/your/image.jpg'); background-size: cover; background-position: center; min-height: 100vh;">
    <v-app-bar title="Welcome - Tutorial Laravel" app>
      <v-spacer/>
      <v-btn v-if="canLogin" href="/login" color="primary" prepend-icon="mdi-login" variant="text">Login</v-btn>
      <v-btn v-if="canRegister" href="/register" color="secondary" prepend-icon="mdi-account" variant="text">Register</v-btn>
    </v-app-bar>
    <v-main>
      <v-container class="d-flex justify-center align-center" style="height: calc(100vh - 64px);">
        <v-card class="elevation-10 rounded" style="background: rgba(255, 255, 255, 0.8); width: 100%; padding: 40px;">
          <v-row align="center" justify="center">
            <v-col cols="12" md="8" class="text-center">
              <h1 class="text-h3 mb-4" style="font-weight: bold; color: #333;">Discover Our Content</h1>
              <p class="text-subtitle-1 mb-4" style="color: #555;">
                Explore exciting videos on our YouTube channel and engaging posts on our TikTok account. Stay connected
                and follow us for more updates!
              </p>
              <p class="text-subtitle-1 mb-4" style="color: #555;">
                YouTube Channel: <strong>Blogger Sejoli</strong><br>
                TikTok Account: <strong>budairi.connect</strong><br>
                Github Repository: <strong>Laragates</strong>
              </p>
              <v-btn href="https://www.youtube.com/c/BloggerSejoli" target="_blank" color="red" variant="contained"
                     class="me-4">
                Visit YouTube Channel
              </v-btn>
              <v-btn href="https://www.tiktok.com/@budairi.connect" target="_blank" color="blue" variant="contained"
                     class="me-4">
                Follow on TikTok
              </v-btn>
              <v-btn href="https://github.com/nusagates/laragates" target="_blank" color="green" variant="contained">
                View Repository
              </v-btn>
            </v-col>
          </v-row>
        </v-card>
      </v-container>
    </v-main>
  </v-app>
</template>