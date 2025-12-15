<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link } from '@inertiajs/vue3'

const menus = [
  {
    title: "Chat",
    desc: "Kelola percakapan pelanggan dengan mudah.",
    icon: "mdi-message-text-outline",
    to: "/chat",
    color: "blue"
  },
  {
    title: "WhatsApp Menu",
    desc: "Kelola menu otomatis & bot WhatsApp.",
    icon: "mdi-format-list-bulleted",
    to: "/menu",
    color: "indigo"
  },
  {
    title: "Agents",
    desc: "Atur dan monitor agen customer service.",
    icon: "mdi-account-group-outline",
    to: "/agents",
    color: "green"
  },
  {
    title: "Tickets",
    desc: "Pantau dan tindak lanjuti laporan pelanggan.",
    icon: "mdi-ticket-confirmation-outline",
    to: "/tickets",
    color: "orange"
  },
  {
    title: "Templates",
    desc: "Kelola pesan template WhatsApp.",
    icon: "mdi-file-document-edit-outline",
    to: "/templates",
    color: "purple"
  },
  {
    title: "Broadcast",
    desc: "Kirim pesan massal ke pelanggan.",
    icon: "mdi-megaphone-outline",
    to: "/broadcast",
    color: "red"
  },
  {
    title: "Settings",
    desc: "Pengaturan sistem & WhatsApp API.",
    icon: "mdi-cog-outline",
    to: "/settings",
    color: "grey"
  }
]
</script>

<template>
  <Head title="Dashboard" />

  <AdminLayout>
    <template #title>Dashboard</template>

    <div class="pa-4">
      <v-card class="pa-6 mb-6">
        <h2 class="text-h6 font-weight-bold mb-2">Selamat Datang!</h2>
        <p class="text-body-2 text-grey-darken-1">
          Pilih menu di bawah untuk mulai mengelola sistem CS WhatsApp Anda.
        </p>
      </v-card>

      <div class="d-flex flex-wrap ga-4">
        <Link
          v-for="item in menus"
          :key="item.title"
          :href="item.to"
          style="text-decoration: none;"
        >
          <v-card
            class="pa-5 d-flex flex-column align-center justify-center hover-elevate"
            width="260"
            height="200"
            elevation="3"
          >
            <v-icon :color="item.color" size="48" class="mb-3">
              {{ item.icon }}
            </v-icon>

            <h3 class="text-subtitle-1 font-weight-bold mb-1">
              {{ item.title }}
            </h3>

            <p class="text-caption text-grey-darken-1 text-center">
              {{ item.desc }}
            </p>
          </v-card>
        </Link>
      </div>
    </div>
  </AdminLayout>
</template>

<style scoped>
.hover-elevate:hover {
  transform: translateY(-5px);
  transition: 0.2s;
  cursor: pointer;
}
</style>
