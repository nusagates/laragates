<script setup>
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
  menu: Object
})

const form = useForm({
  key: props.menu?.key ?? '',
  title: props.menu?.title ?? '',
  action_type: props.menu?.action_type ?? 'auto_reply',
  reply_text: props.menu?.reply_text ?? '',
  order: props.menu?.order ?? 0,
})

const submit = () => {
  if (props.menu) {
    form.put(`/menu/${props.menu.id}`)
  } else {
    form.post('/menu')
  }
}
</script>

<template>
  <v-form @submit.prevent="submit" class="form-container">

    <!-- ================= INFORMASI MENU ================= -->
    <section class="form-section">
      <header class="section-header">
        <h3>Informasi Menu</h3>
        <p>Data dasar menu yang akan ditampilkan kepada pelanggan.</p>
      </header>

      <v-row dense>
        <v-col cols="12" md="3">
          <v-text-field
            label="Key Menu"
            placeholder="1"
            v-model="form.key"
          />
        </v-col>

        <v-col cols="12" md="9">
          <v-text-field
            label="Judul Menu"
            placeholder="Cek Status Pesanan"
            v-model="form.title"
          />
        </v-col>
      </v-row>
    </section>

    <!-- ================= KONFIGURASI AKSI ================= -->
    <section class="form-section">
      <header class="section-header">
        <h3>Konfigurasi Aksi</h3>
        <p>Respon sistem ketika pelanggan memilih menu ini.</p>
      </header>

      <v-row dense>
        <v-col cols="12" md="3">
          <v-select
            label="Action Type"
            :items="[
              { title: 'Auto Reply', value: 'auto_reply' },
              { title: 'Ask Input', value: 'ask_input' },
              { title: 'Handover ke Agent', value: 'handover' }
            ]"
            item-title="title"
            item-value="value"
            v-model="form.action_type"
          />
        </v-col>

        <v-col cols="12" md="9">
          <v-textarea
            label="Reply Text"
            placeholder="Tulis pesan otomatis untuk pelanggan..."
            rows="4"
            auto-grow
            v-model="form.reply_text"
          />
        </v-col>
      </v-row>
    </section>

    <!-- ================= ACTION ================= -->
    <div class="form-actions">
      <v-btn
        variant="tonal"
        class="btn-cancel"
        @click="$inertia.visit('/menu')"
      >
        Batal
      </v-btn>

      <v-btn
        type="submit"
        color="primary"
        :loading="form.processing"
      >
        Simpan Menu
      </v-btn>
    </div>

  </v-form>
</template>

<style scoped>
/* ================= CONTAINER ================= */
.form-container {
  max-width: 1100px;
}

/* ================= SECTION ================= */
.form-section {
  background: linear-gradient(
    180deg,
    rgba(255,255,255,0.07),
    rgba(255,255,255,0.04)
  );
  border: 1px solid rgba(255,255,255,0.12);
  border-radius: 18px;
  padding: 28px;
  margin-bottom: 28px;

  box-shadow: 0 18px 40px rgba(0,0,0,.35);
}

/* ================= HEADER ================= */
.section-header h3 {
  font-size: 16px;
  font-weight: 700;
  color: #f8fafc;
}

.section-header p {
  font-size: 13px;
  color: #cbd5f5;
  margin-top: 4px;
  margin-bottom: 18px;
}

/* ================= INPUT THEME ================= */
:deep(.v-field) {
  background: rgba(255,255,255,0.06);
  border-radius: 10px;
}

:deep(.v-label) {
  color: #cbd5f5;
}

:deep(.v-field__input),
:deep(textarea) {
  color: #e5e7eb;
}

/* ================= ACTION ================= */
.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

.btn-cancel {
  background: rgba(255,255,255,0.08);
  color: #e5e7eb;
}

.btn-cancel:hover {
  background: rgba(255,255,255,0.14);
}

.v-btn {
  text-transform: none;
}
</style>
