import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// ===========================
// 🔔 Real-time via Pusher Cloud
// ===========================
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Set global Pusher (optional for debugging)
window.Pusher = Pusher;

// Init Echo
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
});
