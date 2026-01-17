import { ref, onMounted, onUnmounted } from 'vue'
import { useChatStore } from '@/stores/chatStore'

export function useEcho() {
  const chatStore = useChatStore()
  const isConnected = ref(false)
  const isConnecting = ref(false)

  /**
   * Initialize Echo connection
   */
  function connect() {
    if (!window.Echo) {
      console.error('Laravel Echo is not initialized')
      return
    }

    isConnecting.value = true
    chatStore.setConnectionStatus('connecting')

    // Pusher connection events
    if (window.Echo.connector && window.Echo.connector.pusher) {
      const pusher = window.Echo.connector.pusher

      pusher.connection.bind('connected', () => {
        isConnected.value = true
        isConnecting.value = false
        chatStore.setConnectionStatus('connected')
        console.log('Echo connected')
      })

      pusher.connection.bind('disconnected', () => {
        isConnected.value = false
        isConnecting.value = false
        chatStore.setConnectionStatus('disconnected')
        console.log('Echo disconnected')
      })

      pusher.connection.bind('connecting', () => {
        isConnecting.value = true
        chatStore.setConnectionStatus('connecting')
        console.log('Echo connecting...')
      })

      pusher.connection.bind('unavailable', () => {
        isConnected.value = false
        isConnecting.value = false
        chatStore.setConnectionStatus('disconnected')
        console.log('Echo unavailable')
      })

      pusher.connection.bind('failed', () => {
        isConnected.value = false
        isConnecting.value = false
        chatStore.setConnectionStatus('disconnected')
        console.error('Echo connection failed')
      })

      pusher.connection.bind('error', (err) => {
        console.error('Echo error:', err)
      })
    }
  }

  /**
   * Subscribe to a private chat session channel
   */
  function subscribeToSession(sessionId) {
    if (!sessionId || !window.Echo) return null

    const channelName = `chat-session.${sessionId}`

    return window.Echo.private(channelName)
  }

  /**
   * Leave a channel
   */
  function leaveChannel(channelName) {
    if (!window.Echo) return
    window.Echo.leave(channelName)
  }

  /**
   * Disconnect Echo
   */
  function disconnect() {
    if (window.Echo) {
      window.Echo.disconnect()
      isConnected.value = false
      chatStore.setConnectionStatus('disconnected')
    }
  }

  // Auto-connect on mount
  onMounted(() => {
    connect()
  })

  // Clean up on unmount
  onUnmounted(() => {
    disconnect()
  })

  return {
    isConnected,
    isConnecting,
    connect,
    disconnect,
    subscribeToSession,
    leaveChannel,
  }
}
