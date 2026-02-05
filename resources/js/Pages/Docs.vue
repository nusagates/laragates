<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue'

const sections = [
  { id: 'intro', title: 'Pendahuluan' },
  { id: 'concept', title: 'Konsep Sistem' },
  { id: 'roles', title: 'Role & Hak Akses' },
  { id: 'flow', title: 'Alur Kerja Chat' },
  { id: 'api', title: 'API Overview' },
  { id: 'endpoints', title: 'API Endpoint' },
  { id: 'webhook', title: 'Webhook Lifecycle' },
]
</script>

<template>
  <PublicLayout title="Dokumentasi – WABA">

    <v-container fluid class="docs-wrapper">
      <v-row>

        <!-- ================= SIDEBAR ================= -->
        <v-col cols="12" md="3" class="docs-sidebar">
          <div class="sidebar-inner">
            <h3 class="sidebar-title">Dokumentasi</h3>

            <ul class="sidebar-menu">
              <li v-for="s in sections" :key="s.id">
                <a :href="'#' + s.id">{{ s.title }}</a>
              </li>
            </ul>
          </div>
        </v-col>

        <!-- ================= CONTENT ================= -->
        <v-col cols="12" md="9" class="docs-content">

          <!-- INTRO -->
          <section id="intro" class="docs-section">
            <h1>Dokumentasi Teknis WABA</h1>
            <p class="lead">
              WABA adalah platform WhatsApp Customer Service terpusat
              yang dirancang untuk bisnis modern dengan kebutuhan
              skalabilitas, audit, dan integrasi sistem.
            </p>

            <p>
              Dokumentasi ini mencakup panduan konseptual dan teknis
              untuk administrator, agent, dan developer.
            </p>
          </section>

          <!-- CONCEPT -->
          <section id="concept" class="docs-section">
            <h2>Konsep Sistem</h2>

            <p>
              WABA bertindak sebagai <strong>middleware</strong> antara
              WhatsApp API provider dan tim customer service internal.
            </p>

            <h3>Komponen Utama</h3>
            <ul>
              <li><strong>Chat Session</strong> – Identitas percakapan pelanggan</li>
              <li><strong>Chat Message</strong> – Pesan inbound & outbound</li>
              <li><strong>Agent</strong> – User internal penanganan chat</li>
              <li><strong>Ticket</strong> – Eskalasi percakapan</li>
            </ul>
          </section>

          <!-- ROLES -->
          <section id="roles" class="docs-section">
            <h2>Role & Hak Akses</h2>

            <table class="role-table">
              <thead>
                <tr>
                  <th>Role</th>
                  <th>Hak Akses</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>Super Admin</td>
                  <td>Konfigurasi sistem & manajemen user</td>
                </tr>
                <tr>
                  <td>Admin</td>
                  <td>Monitoring, laporan, approval template</td>
                </tr>
                <tr>
                  <td>Agent</td>
                  <td>Menangani chat & ticket</td>
                </tr>
              </tbody>
            </table>
          </section>

          <!-- FLOW -->
          <section id="flow" class="docs-section">
            <h2>Alur Kerja Chat</h2>

            <ol class="flow-list">
              <li>Pelanggan mengirim pesan WhatsApp</li>
              <li>Webhook provider mengirim event ke WABA</li>
              <li>Sistem membuat / update chat session</li>
              <li>Auto routing ke agent online</li>
              <li>Agent membalas melalui dashboard</li>
              <li>Chat dicatat sebagai history / ticket</li>
            </ol>
          </section>

          <!-- API OVERVIEW -->
          <section id="api" class="docs-section">
            <h2>API Overview</h2>

            <p>
              WABA menyediakan REST API berbasis JSON
              untuk integrasi sistem eksternal.
            </p>

            <ul>
              <li>Authentication via Bearer Token</li>
              <li>Format JSON</li>
              <li>Stateless</li>
            </ul>
          </section>

          <!-- ENDPOINT -->
          <section id="endpoints" class="docs-section">
            <h2>API Endpoint</h2>

            <h3>List Chat Sessions</h3>
            <pre class="code-block">GET /api/chat/sessions</pre>

            <h3>Kirim Pesan</h3>
            <pre class="code-block">POST /api/chat/sessions/{id}/messages</pre>

            <pre class="code-block">
{
  "message": "Halo, ada yang bisa kami bantu?",
  "media": null
}
            </pre>

            <h3>Convert ke Ticket</h3>
            <pre class="code-block">POST /api/chat/sessions/{id}/convert-ticket</pre>
          </section>

          <!-- WEBHOOK -->
          <section id="webhook" class="docs-section">
            <h2>Webhook Lifecycle</h2>

            <ol class="flow-list">
              <li>WhatsApp provider mengirim event message</li>
              <li>WABA menerima dan memverifikasi signature</li>
              <li>Message disimpan sebagai inbound</li>
              <li>Trigger routing & automation</li>
              <li>Status delivery disimpan</li>
            </ol>

            <p>
              Webhook bersifat idempotent dan aman terhadap retry.
            </p>
          </section>

        </v-col>
      </v-row>
    </v-container>

  </PublicLayout>
</template>

<style scoped>
.docs-wrapper {
  padding-top: 40px;
  padding-bottom: 80px;
}

.docs-sidebar {
  border-right: 1px solid #e5e7eb;
}

.sidebar-inner {
  position: sticky;
  top: 100px;
}

.sidebar-title {
  font-weight: 700;
  margin-bottom: 16px;
}

.sidebar-menu {
  list-style: none;
  padding: 0;
}
.sidebar-menu li {
  margin-bottom: 10px;
}
.sidebar-menu a {
  text-decoration: none;
  color: #334155;
  font-size: 14px;
}
.sidebar-menu a:hover {
  color: #2563eb;
}

.docs-content {
  padding-left: 40px;
}

.docs-section {
  margin-bottom: 60px;
}

.docs-section h1 {
  font-size: 32px;
}
.docs-section h2 {
  font-size: 24px;
}
.docs-section h3 {
  font-size: 18px;
  margin-top: 20px;
}

.docs-section p,
.docs-section li {
  font-size: 15px;
  line-height: 1.7;
  color: #334155;
}

.lead {
  font-size: 17px;
  color: #475569;
}

.role-table {
  width: 100%;
  border-collapse: collapse;
}
.role-table th,
.role-table td {
  border: 1px solid #e5e7eb;
  padding: 12px;
}
.role-table th {
  background: #f8fafc;
}

.flow-list {
  padding-left: 20px;
}

.code-block {
  background: #020617;
  color: #e5e7eb;
  padding: 14px;
  border-radius: 8px;
  font-size: 13px;
  overflow-x: auto;
  margin: 12px 0;
}
</style>
