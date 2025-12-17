import axios from 'axios'
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
axios.defaults.withCredentials = true

declare global {
  interface Window {
    axios: typeof axios
    Echo: any
    Pusher: typeof Pusher
  }
}

window.axios = axios
window.Pusher = Pusher

window.Echo = new Echo({
  broadcaster: 'pusher',
  key: import.meta.env.VITE_PUSHER_APP_KEY,
  cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
  forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
  enabledTransports: ['ws', 'wss'],
})
