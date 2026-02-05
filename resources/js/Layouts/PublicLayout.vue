<script setup>
import { Head, Link } from '@inertiajs/vue3'
defineProps({ title: String })
</script>

<template>
  <Head :title="title" />

  <v-app style="background:#f4f7fb">

    <!-- ================= HEADER ================= -->
    <v-app-bar flat color="white" elevation="1" height="72">
      <v-container class="d-flex align-center">
        <v-toolbar-title
          class="font-weight-bold text-h6 text-primary"
          style="cursor:pointer"
        >
          <Link href="/" class="logo-link">WABA</Link>
        </v-toolbar-title>

        <v-spacer />

        <v-btn variant="text" href="/solutions">Solusi</v-btn>
        <v-btn variant="text" href="/pricing">Harga</v-btn>
        <v-btn variant="text" href="/docs">Dokumentasi</v-btn>

        <v-btn variant="outlined" color="primary" class="ml-4" href="/login">
          Sign In
        </v-btn>
        <v-btn color="primary" class="ml-2" href="/register">
          Coba Gratis
        </v-btn>
      </v-container>
    </v-app-bar>

    <!-- ================= CONTENT ================= -->
    <v-main>
      <slot />
    </v-main>

    <!-- ================= FOOTER ================= -->
    <v-footer class="footer-premium">
      <v-container>

        <!-- TOP FOOTER -->
        <v-row class="mb-12">
          <!-- BRAND -->
          <v-col cols="12" md="4">
            <h3 class="footer-brand">WABA</h3>
            <p class="footer-desc">
              Platform WhatsApp Customer Service terpusat
              untuk bisnis modern yang membutuhkan sistem
              rapi, terukur, dan siap scale dari UMKM
              hingga Enterprise.
            </p>
          </v-col>

          <!-- LINKS -->
          <v-col cols="12" md="2">
            <h4 class="footer-title">Produk</h4>
            <ul class="footer-links">
              <li><Link href="/solutions">Solusi</Link></li>
              <li><Link href="/pricing">Harga</Link></li>
              <li><Link href="/docs">Dokumentasi</Link></li>
            </ul>
          </v-col>

          <v-col cols="12" md="2">
            <h4 class="footer-title">Perusahaan</h4>
            <ul class="footer-links">
              <li><a href="#">Tentang Kami</a></li>
              <li><a href="#">Kontak</a></li>
              <li><a href="#">Karir</a></li>
            </ul>
          </v-col>

          <!-- NEWSLETTER -->
          <v-col cols="12" md="4">
            <h4 class="footer-title">Newsletter</h4>
            <p class="footer-desc small">
              Dapatkan update fitur, insight customer service,
              dan pengumuman penting dari WABA langsung
              ke email Anda.
            </p>

            <div class="newsletter">
              <input
                type="email"
                placeholder="Masukkan email Anda"
                class="newsletter-input"
              />
              <button class="newsletter-btn">
                Subscribe
              </button>
            </div>
          </v-col>
        </v-row>

        <!-- DIVIDER -->
        <div class="footer-divider"></div>

        <!-- BOTTOM FOOTER -->
        <v-row class="mt-6 align-center">
          <v-col cols="12" md="6">
            <p class="footer-copy">
              © 2025 <strong>WABA</strong>. All rights reserved.
            </p>
          </v-col>

          <v-col cols="12" md="6" class="text-md-right">
            <p class="footer-legal">
              <a href="#">Privacy Policy</a> ·
              <a href="#">Terms of Service</a>
            </p>
          </v-col>
        </v-row>

      </v-container>
    </v-footer>

  </v-app>
</template>

<style scoped>
/* HEADER */
.logo-link {
  text-decoration: none;
  color: inherit;
}

/* ================= FOOTER PREMIUM ================= */
.footer-premium {
  background: linear-gradient(180deg, #020617, #020617);
  padding: 80px 0 40px;
  margin-top: 120px;
}

.footer-brand {
  color: #ffffff;
  font-size: 20px;
  font-weight: 700;
}

.footer-title {
  color: #ffffff;
  font-size: 14px;
  font-weight: 600;
  margin-bottom: 12px;
}

.footer-desc {
  color: #cbd5f5;
  font-size: 14px;
  margin-top: 12px;
  line-height: 1.7;
}

.footer-desc.small {
  font-size: 13px;
}

.footer-links {
  list-style: none;
  padding: 0;
  margin: 0;
}

.footer-links li {
  margin-bottom: 8px;
}

.footer-links a {
  color: #cbd5f5;
  font-size: 14px;
  text-decoration: none;
  transition: color 0.2s ease;
}

.footer-links a:hover {
  color: #ffffff;
}

/* Newsletter */
.newsletter {
  display: flex;
  gap: 8px;
  margin-top: 16px;
}

.newsletter-input {
  flex: 1;
  padding: 12px 14px;
  border-radius: 8px;
  border: none;
  outline: none;
  font-size: 14px;
}

.newsletter-btn {
  padding: 12px 18px;
  background: #2563eb;
  color: #ffffff;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  border: none;
  transition: background 0.2s ease;
}

.newsletter-btn:hover {
  background: #1e40af;
}

/* Divider */
.footer-divider {
  height: 1px;
  background: rgba(255,255,255,0.08);
}

/* Bottom */
.footer-copy,
.footer-legal {
  color: #94a3b8;
  font-size: 13px;
}

.footer-legal a {
  color: #94a3b8;
  text-decoration: none;
  margin-left: 6px;
}

.footer-legal a:hover {
  color: #ffffff;
}
</style>
