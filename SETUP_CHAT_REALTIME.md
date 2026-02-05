# Setup Chat Realtime - WhatsApp Integration

Dokumentasi lengkap untuk setup fitur chat realtime dengan WhatsApp menggunakan Fonnte dan Pusher.

---

## 📋 Prerequisites

- Laravel 12
- PHP 8.3+
- Node.js & NPM
- Composer
- MySQL/MariaDB

---

## 🔧 1. Fonnte Setup (WhatsApp Gateway)

### A. Buat Akun Fonnte

1. Kunjungi: https://fonnte.com
2. Register akun baru
3. Verifikasi email

### B. Connect WhatsApp Device

1. Login ke dashboard Fonnte
2. Pilih menu **"Device"**
3. Klik **"Add Device"** / **"Connect WhatsApp"**
4. Scan QR Code dengan aplikasi WhatsApp
5. Tunggu sampai status **"Connected"**

### C. Get URL & Device Token

1. Di dashboard, buka menu **"Device"**
2. Pilih salah satu device yang sudah connect, lalu **Edit** copy **Token** *(misal: `k7NDAVboVHqNKAR9yHPN`)*
3. Simpan token ini untuk digunakan di `.env` -  `FONNTE_TOKEN=k7NDAVboVHqNKAR9yHPN`
4. Set URL DENGAN `FONNTE_ENDPOINT=https://api.fonnte.com`

### D. Setup Webhook

1. Masih di Fonnte Dashboard, Set Webhook URL Ke `Device > Edit > Webhook`:
   ```
   https://your-domain.com/api/webhook/fonnte
   ```
   - Untuk development (ngrok): `https://xxxx.ngrok.io/api/webhook/fonnte`
   - Untuk production: `https://laragates.com/api/webhook/fonnte`

3. Enable webhook untuk events:
   - ✅ **Incoming Message**
   - ✅ **Message Status** (optional)

4. Klik **Save**

### E. Environment Variables

Tambahkan ke `.env`:

```env
# Fonnte WhatsApp Gateway
WHATSAPP_PROVIDER=fonnte
FONNTE_ENDPOINT=https://api.fonnte.com
FONNTE_TOKEN=k7NDAVboVHqNKAR9yHPN
```

### F. Create URL tunnel
```
ngrok http https://laragates.test --host-header=rewrite
```
---

## 🔔 2. Pusher Setup (Realtime Broadcasting)

### A. Buat Akun Pusher

1. Kunjungi: https://pusher.com
2. Sign up (gratis untuk development)
3. Verifikasi email

### B. Create App

1. Login ke dashboard: https://dashboard.pusher.com
2. Klik **"Create app"** atau **"Channels apps"** → **"Create app"**
3. Isi form:
   - **Name**: `laragates` atau nama aplikasi Anda
   - **Cluster**: Pilih yang terdekat (misal: `ap1` untuk Asia, `mt1` untuk Mumbai)
   - **Tech stack**: Frontend: `Vue`, Backend: `Laravel`
4. Klik **"Create app"**

### C. Get App Credentials

1. Di dashboard app, pilih tab **"App Keys"**
2. Copy credentials berikut:
   - **app_id**: `302915` (contoh)
   - **key**: `37a4cff9054ccfa432b0`
   - **secret**: `193d0c0ae7d6017b5ba7`
   - **cluster**: `mt1`

### D. Environment Variables

Tambahkan ke `.env`:

```env
# Broadcasting - Pusher
BROADCAST_CONNECTION=pusher
BROADCAST_DRIVER=pusher

PUSHER_APP_ID=302915
PUSHER_APP_KEY=37a4cff9054ccfa432b0
PUSHER_APP_SECRET=193d0c0ae7d6017b5ba7
PUSHER_APP_CLUSTER=mt1

# Frontend Pusher Config (Vite)
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

⚠️ **PENTING**: 
- Jangan commit `.env` ke git!
- Secret key harus tetap rahasia
- Gunakan credentials yang berbeda untuk production

---

## 🗄️ 3. Database Setup

### A. Run Migrations

```bash
php artisan migrate
```

Pastikan tabel berikut ter-create:
- `customers`
- `chat_sessions`
- `chat_messages`
- `agents` / `users`

### B. Seed Data (Optional)

```bash
php artisan db:seed
```

---

## 📦 4. Install Dependencies

### A. PHP Dependencies

```bash
composer install
```

Pastikan package berikut terinstall:
- `pusher/pusher-php-server`
- `guzzlehttp/guzzle`

### B. JavaScript Dependencies

```bash
npm install
```

Pastikan package berikut terinstall:
- `laravel-echo`
- `pusher-js`

---

## ⚙️ 5. Configuration Files

### A. Broadcasting Config

File: `config/broadcasting.php`

Pastikan config Pusher seperti ini:

```php
'pusher' => [
    'driver' => 'pusher',
    'key'    => env('PUSHER_APP_KEY'),
    'secret' => env('PUSHER_APP_SECRET'),
    'app_id' => env('PUSHER_APP_ID'),
    'options' => [
        'cluster' => env('PUSHER_APP_CLUSTER', 'ap1'),
        'useTLS'  => true,
    ],
],
```

⚠️ **Jangan** include `host`, `port`, atau `scheme` untuk Pusher Cloud!

### B. Bootstrap Echo (Frontend)

File: `resources/js/bootstrap.js`

```javascript
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

window.Echo = new Echo({
  broadcaster: 'pusher',
  key: import.meta.env.VITE_PUSHER_APP_KEY,
  cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
  forceTLS: true,
  authEndpoint: '/broadcasting/auth',
  auth: {
    headers: {
      'X-CSRF-TOKEN': token,
      'Accept': 'application/json',
    },
  },
})
```

### C. Broadcast Channels

File: `routes/channels.php`

```php
Broadcast::channel('chat-session.{sessionId}', function ($user, $sessionId) {
    return $user !== null;
});
```

---

## 🚀 6. Build & Run

### A. Build Frontend Assets

Development:
```bash
npm run dev
```

Production:
```bash
npm run build
```

### B. Clear Cache

```bash
php artisan config:clear
php artisan cache:clear
php artisan optimize:clear
```

### C. Start Services

**Laravel Development Server:**
```bash
php artisan serve
```

**Or use Laravel Herd/Valet:**
```bash
# Already running at laragates.test
```

**Queue Worker (if using queues):**
```bash
php artisan queue:work
```

**Jika tanpa Queue Worker**
pastikan di `.env`
```bash
QUEUE_CONNECTION=sync
```

---

## 🧪 7. Testing Setup

### A. Test Pusher Connection

Kunjungi test page:
```
http://laragates.test/test/broadcast
```

1. Check connection status - harus "Listening"
2. Ketik message test dan klik **"Trigger Broadcast"**
3. Message harus muncul di "Received Messages"
4. Buka console (F12) - tidak boleh ada error

✅ **Jika berhasil**, Pusher setup sudah benar!

### B. Test WhatsApp Incoming

1. Buka halaman chat: `http://laragates.test/chat`
2. Pilih atau buat chat session
3. **Kirim pesan dari WhatsApp** ke nomor Fonnte Anda
4. Message harus muncul **realtime** di UI (tanpa refresh)

✅ **Jika berhasil**, Webhook dan realtime sudah bekerja!

### C. Test WhatsApp Outgoing

1. Di halaman chat, ketik pesan
2. Klik **"SEND"**
3. Pesan harus terkirim ke WhatsApp customer
4. Check status di chat bubble

---

## 🐛 8. Troubleshooting

### Issue 1: Pusher Connection Failed

**Symptom:** Console error: "Failed to connect to Pusher"

**Solution:**
1. Check `.env` credentials (app_id, key, secret, cluster)
2. Verify credentials di Pusher Dashboard → App Keys
3. Run `php artisan config:clear`
4. Refresh browser

### Issue 2: Event Tidak Diterima

**Symptom:** Pusher logs: "No callbacks on channel for event"

**Solution:**
- Event name harus pakai **dot prefix**: `.MessageSent` bukan `MessageSent`
- Check listener di `Room.vue`:
  ```javascript
  .listen('.MessageSent', (e) => { ... })
  ```

### Issue 3: Webhook Tidak Jalan

**Symptom:** Pesan dari WhatsApp tidak masuk ke database

**Solution:**
1. Check webhook URL di Fonnte dashboard
2. Pastikan webhook accessible (gunakan ngrok untuk local)
3. Check Laravel logs: `storage/logs/laravel.log`
4. Test webhook manual dengan Postman

### Issue 4: Auth Error untuk Private Channel

**Symptom:** Console error: "Auth failed for private channel"

**Solution:**
1. Pastikan user sudah login
2. Check `routes/channels.php` ada authorization
3. Verify `authEndpoint: '/broadcasting/auth'` di bootstrap.js
4. Check CSRF token ter-pass dengan benar

### Issue 5: Invalid Signature (Pusher)

**Symptom:** Laravel log: "Invalid signature: you should have sent..."

**Solution:**
- **PUSHER_APP_SECRET salah** - double check di dashboard
- Copy ulang credentials dari Pusher Dashboard
- `php artisan config:clear`

---

## 📊 9. Monitoring & Debugging

### A. Pusher Debug Console

1. Login ke Pusher Dashboard
2. Pilih app Anda
3. Tab **"Debug Console"**
4. Akan muncul realtime events yang ter-broadcast

### B. Laravel Logs

```bash
tail -f storage/logs/laravel.log
```

Event yang di-log:
- Webhook incoming
- Broadcast events
- Errors

### C. Browser Console

Enable Pusher logging (untuk debug):
```javascript
window.Pusher.logToConsole = true;
```

---

## 🔐 10. Production Checklist

### Before Deploy:

- [ ] Update Pusher credentials untuk production app
- [ ] Update Fonnte webhook URL ke production domain
- [ ] Set `APP_ENV=production` di `.env`
- [ ] Set `APP_DEBUG=false`
- [ ] Enable HTTPS (SSL certificate)
- [ ] Configure queue workers (Supervisor/PM2)
- [ ] Setup monitoring (Laravel Telescope/Horizon)
- [ ] Test webhook dengan production URL
- [ ] Test realtime dengan production Pusher app
- [ ] Backup database

### Production `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-production-domain.com

BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=your_production_app_id
PUSHER_APP_KEY=your_production_key
PUSHER_APP_SECRET=your_production_secret
PUSHER_APP_CLUSTER=mt1

FONNTE_API_TOKEN=your_production_fonnte_token
```

---

## 📞 11. Support & Resources

### Documentation Links:
- **Fonnte API Docs**: https://fonnte.com/api
- **Pusher Channels Docs**: https://pusher.com/docs/channels
- **Laravel Broadcasting**: https://laravel.com/docs/broadcasting
- **Laravel Echo**: https://laravel.com/docs/broadcasting#client-side-installation

### Common Issues:
- Pusher free tier limit: 200k messages/day, 100 concurrent connections
- Fonnte free tier: Terbatas (check pricing)
- Webhook timeout: Max 30 seconds response time

---

## ✅ Summary

Setup lengkap untuk chat realtime:

1. ✅ Fonnte account + WhatsApp connected
2. ✅ Pusher account + app created  
3. ✅ `.env` configured with credentials
4. ✅ Broadcasting config (no host/port/scheme)
5. ✅ Echo setup with authEndpoint
6. ✅ Channel authorization
7. ✅ Event listener dengan dot prefix
8. ✅ Webhook URL configured
9. ✅ Test page untuk debugging
10. ✅ Production checklist

**Realtime chat siap production! 🚀**

---

**Last Updated**: January 10, 2026  
**Version**: 1.0  
**Author**: Huiralb
