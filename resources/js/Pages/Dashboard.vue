<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head } from '@inertiajs/vue3'
</script>

<template>
  <Head title="Dashboard" />

  <AdminLayout>
    <template #title>Dashboard</template>

    <!-- ===================== -->
    <!--  WELCOME SECTION       -->
    <!-- ===================== -->

    <v-card
      elevation="10"
      class="pa-6 mb-8 rounded-lg"
      style="background-color: #f8fafc;"
    >
      <h2 class="text-h6 mb-2 font-weight-bold">Selamat Datang di CS WhatsApp Dashboard</h2>
      <p class="text-body-2">
        Kelola percakapan pelanggan Anda dengan mudah dan profesional.
      </p>
    </v-card>

    <!-- ===================== -->
    <!--  STATISTIC CARDS      -->
    <!-- ===================== -->

    <v-row dense>
      <!-- Total Message -->
      <v-col cols="12" md="4">
        <v-card elevation="8" class="pa-5 rounded-lg">
          <v-row no-gutters>
            <v-col cols="3" class="d-flex justify-center align-center">
              <v-icon size="40" color="primary">mdi-whatsapp</v-icon>
            </v-col>
            <v-col>
              <p class="text-body-2 text-grey">Total Message Today</p>
              <h3 class="text-h5 font-weight-bold">128</h3>
            </v-col>
          </v-row>
        </v-card>
      </v-col>

      <!-- Active Agents -->
      <v-col cols="12" md="4">
        <v-card elevation="8" class="pa-5 rounded-lg">
          <v-row no-gutters>
            <v-col cols="3" class="d-flex justify-center align-center">
              <v-icon size="40" color="green">mdi-account-group</v-icon>
            </v-col>
            <v-col>
              <p class="text-body-2 text-grey">Active Agents</p>
              <h3 class="text-h5 font-weight-bold">5</h3>
            </v-col>
          </v-row>
        </v-card>
      </v-col>

      <!-- Pending Tickets -->
      <v-col cols="12" md="4">
        <v-card elevation="8" class="pa-5 rounded-lg">
          <v-row no-gutters>
            <v-col cols="3" class="d-flex justify-center align-center">
              <v-icon size="40" color="red">mdi-timer-sand</v-icon>
            </v-col>
            <v-col>
              <p class="text-body-2 text-grey">Pending Tickets</p>
              <h3 class="text-h5 font-weight-bold">14</h3>
            </v-col>
          </v-row>
        </v-card>
      </v-col>
    </v-row>

    <!-- ===================== -->
    <!--  CONTENT ROW          -->
    <!-- ===================== -->

    <v-row class="mt-8">

      <!-- RECENT CHATS -->
      <v-col cols="12" md="7">
        <v-card elevation="8" class="pa-6 rounded-lg">
          <h3 class="text-h6 mb-4 font-weight-bold">Recent Chats</h3>

          <v-list density="compact" lines="two">
            <v-list-item v-for="i in 4" :key="i">
              <v-list-item-avatar>
                <v-avatar color="blue">
                  <span class="white--text">A</span>
                </v-avatar>
              </v-list-item-avatar>

              <v-list-item-title>Customer {{ i }}</v-list-item-title>
              <v-list-item-subtitle>
                Hi, I'd like to ask about …
              </v-list-item-subtitle>

              <v-list-item-action>
                <v-icon class="text-blue">mdi-chevron-right</v-icon>
              </v-list-item-action>
            </v-list-item>
          </v-list>
        </v-card>
      </v-col>

      <!-- SYSTEM STATUS -->
      <v-col cols="12" md="5">
        <v-card elevation="8" class="pa-6 rounded-lg">
          <h3 class="text-h6 mb-4 font-weight-bold">System Status</h3>

          <!-- Webhook -->
          <v-row class="mb-4">
            <v-col cols="3">
              <v-icon size="40" color="green">mdi-check-circle</v-icon>
            </v-col>
            <v-col>
              <p class="text-body-2">Webhook Status</p>
              <h4 class="font-weight-medium">Connected</h4>
            </v-col>
          </v-row>

          <v-divider class="my-4" />

          <!-- Server -->
          <v-row>
            <v-col cols="3">
              <v-icon size="40" color="primary">mdi-server</v-icon>
            </v-col>
            <v-col>
              <p class="text-body-2">Message Server</p>
              <h4 class="font-weight-medium">Online</h4>
            </v-col>
          </v-row>

        </v-card>
      </v-col>

    </v-row>

  </AdminLayout>
</template>
