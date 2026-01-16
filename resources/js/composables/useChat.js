import { ref, watch } from 'vue'
import { useChatStore } from '@/stores/chatStore'
import { useEcho } from './useEcho'
import { useToast } from 'vue-toastification'

export function useChat() {
  const chatStore = useChatStore()
  const { subscribeToSession, leaveChannel } = useEcho()
  const toast = useToast()

  const currentChannel = ref(null)
  const subscribedChannels = ref(new Set())
  const typingTimeout = ref(null)
  const isTyping = ref(false)

  /**
   * Subscribe to all active sessions for incoming messages
   */
  function subscribeToAllSessions() {
    if (!chatStore.sessions || chatStore.sessions.length === 0) return

    chatStore.sessions.forEach(session => {
      const sessionId = session.session_id

      // Skip if already subscribed
      if (subscribedChannels.value.has(sessionId)) return

      const channel = subscribeToSession(sessionId)

      if (!channel) {
        console.error('Failed to subscribe to session:', sessionId)
        return
      }

      // Listen for new messages on this channel
      channel.listen('.MessageSent', (event) => {
        handleMessageSent(event)
      })

      // Listen for message updates
      channel.listen('.message-updated', (event) => {
        handleMessageUpdated(event)
      })

      subscribedChannels.value.add(sessionId)
      console.log('✅ Subscribed to session:', sessionId)
    })
  }

  /**
   * Subscribe to a specific chat session's realtime events (for active room)
   */
  function subscribeToRoom(sessionId) {
    if (!sessionId) return

    // Leave previous active channel
    if (currentChannel.value) {
      leaveChannel(`chat-session.${currentChannel.value}`)
    }

    currentChannel.value = sessionId
    const channel = subscribeToSession(sessionId)

    if (!channel) {
      console.error('Failed to subscribe to session:', sessionId)
      return
    }

    // Listen for new messages
    channel.listen('.MessageSent', (event) => {
      handleMessageSent(event)
    })

    // Listen for message updates (delivery status, reactions, etc.)
    channel.listen('.message-updated', (event) => {
      handleMessageUpdated(event)
    })

    // Listen for typing indicators
    channel.listenForWhisper('typing', (data) => {
      handleTyping(data)
    })

    // Mark this channel as subscribed
    subscribedChannels.value.add(sessionId)

    console.log('✅ Subscribed to active room:', sessionId)
  }

  /**
   * Handle MessageSent event from backend
   */
  function handleMessageSent(event) {
    const message = event.message

    // Safety check
    if (!message || !message.session_id) {
      console.error('Invalid message received:', event)
      return
    }

    // If it's from another user, play sound and show notification
    if (message.sender !== 'agent' || message.user_id !== window.Laravel?.user?.id) {
      playNotificationSound()

      // Show toast if not in active room
      if (chatStore.activeRoomId !== message.session_id) {
        toast.info(`New message from ${event.sender || 'Customer'}`)
      }
    }

    // Add message to store
    chatStore.addMessage(message.session_id, message)
  }

  /**
   * Handle MessageUpdated event (delivery status, reactions, etc.)
   */
  function handleMessageUpdated(event) {
    const message = event.message || event
    chatStore.updateMessage(message.session_id, message)
  }

  /**
   * Handle typing indicator from other users
   */
  function handleTyping(data) {
    const { userId, userName, sessionId, typing } = data

    // Don't show own typing indicator
    if (userId === window.Laravel?.user?.id) return

    chatStore.setTyping(sessionId, userId, userName, typing)

    // Auto-clear after 3 seconds
    if (typing) {
      setTimeout(() => {
        chatStore.clearOldTypingIndicators(sessionId)
      }, 3000)
    }
  }

  /**
   * Emit typing indicator to other users
   */
  function emitTyping(sessionId, typing = true) {
    if (!sessionId || !window.Echo) return

    const channel = window.Echo.private(`chat-session.${sessionId}`)

    if (channel) {
      channel.whisper('typing', {
        userId: window.Laravel?.user?.id,
        userName: window.Laravel?.user?.name,
        sessionId,
        typing,
      })
    }

    // Update local typing state
    isTyping.value = typing

    // Auto-stop typing after 3 seconds of inactivity
    if (typing) {
      clearTimeout(typingTimeout.value)
      typingTimeout.value = setTimeout(() => {
        emitTyping(sessionId, false)
      }, 3000)
    }
  }

  /**
   * Send a message
   */
  async function sendMessage(sessionId, messageData) {
    try {
      // Stop typing indicator
      emitTyping(sessionId, false)

      const message = await chatStore.sendMessage(sessionId, messageData)
      return message
    } catch (error) {
      console.error('Failed to send message:', error)
      toast.error(error.response?.data?.message || 'Failed to send message')
      throw error
    }
  }

  /**
   * Load messages for a session with pagination
   */
  async function loadMessages(sessionId, page = 1) {
    try {
      const messages = await chatStore.loadMessages(sessionId, page)
      return messages
    } catch (error) {
      console.error('Failed to load messages:', error)
      toast.error('Failed to load messages')
      throw error
    }
  }

  /**
   * Add reaction to a message
   */
  async function addReaction(messageId, emoji) {
    try {
      await chatStore.addReaction(messageId, emoji)
    } catch (error) {
      console.error('Failed to add reaction:', error)
      toast.error('Failed to add reaction')
      throw error
    }
  }

  /**
   * Mark session as read
   */
  async function markAsRead(sessionId) {
    try {
      await chatStore.markAsRead(sessionId)
    } catch (error) {
      console.error('Failed to mark as read:', error)
    }
  }

  /**
   * Play notification sound using Web Audio API
   */
  function playNotificationSound() {
    try {
      // Create a simple beep sound using Web Audio API
      const audioContext = new (window.AudioContext || window.webkitAudioContext)()
      const oscillator = audioContext.createOscillator()
      const gainNode = audioContext.createGain()

      oscillator.connect(gainNode)
      gainNode.connect(audioContext.destination)

      oscillator.frequency.value = 800 // Frequency in Hz
      oscillator.type = 'sine'

      gainNode.gain.setValueAtTime(0.1, audioContext.currentTime) // Low volume
      gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.2)

      oscillator.start(audioContext.currentTime)
      oscillator.stop(audioContext.currentTime + 0.2)
    } catch (error) {
      // Ignore sound errors (e.g., if AudioContext is not supported)
      console.error('Failed to play notification sound:', error)
    }
  }

  /**
   * Unsubscribe from current room
   */
  function unsubscribe() {
    if (currentChannel.value) {
      leaveChannel(`chat-session.${currentChannel.value}`)
      currentChannel.value = null
    }
  }

  /**
   * Unsubscribe from all sessions
   */
  function unsubscribeAll() {
    subscribedChannels.value.forEach(sessionId => {
      leaveChannel(`chat-session.${sessionId}`)
    })
    subscribedChannels.value.clear()
    currentChannel.value = null
  }

  // Watch for active room changes
  watch(() => chatStore.activeRoomId, (newRoomId, oldRoomId) => {
    if (newRoomId && newRoomId !== oldRoomId) {
      subscribeToRoom(newRoomId)

      // Load messages if not already loaded
      if (!chatStore.messages[newRoomId] || chatStore.messages[newRoomId].length === 0) {
        loadMessages(newRoomId)
      }

      // Mark as read
      markAsRead(newRoomId)
    }
  })

  // Watch for new sessions (when sessions are loaded or updated)
  watch(() => chatStore.sessions.length, () => {
    subscribeToAllSessions()
  })

  return {
    subscribeToRoom,
    subscribeToAllSessions,
    unsubscribe,
    unsubscribeAll,
    sendMessage,
    loadMessages,
    addReaction,
    markAsRead,
    emitTyping,
    isTyping,
  }
}
