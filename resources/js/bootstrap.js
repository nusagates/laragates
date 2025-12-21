import axios from 'axios'
window.axios = axios

// =======================
// 🔐 CSRF SETUP (WAJIB)
// =======================
const token = document
  .querySelector('meta[name="csrf-token"]')
  ?.getAttribute('content')

if (token) {
  window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token
}

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

// ===========================
// 🔔 Real-time via Pusher
// ===========================
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

window.Echo = new Echo({
  broadcaster: 'pusher',
  key: import.meta.env.VITE_PUSHER_APP_KEY,
  cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
  forceTLS: true,
})
