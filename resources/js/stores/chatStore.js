import { defineStore } from 'pinia'
import axios from 'axios'
import { formatDateSeparator, formatTime } from '../helpers'

export const useChatStore = defineStore('chat', {
  state: () => ({
    // Sessions
    sessions: [],
    activeRoomId: null,

    // Messages (keyed by session ID)
    messages: {},

    // Typing indicators (keyed by session ID)
    typingUsers: {},

    // Connection status
    connectionStatus: 'disconnected', // disconnected | connecting | connected

    // Optimistic messages (temporary IDs)
    optimisticMessages: {},

    // Loading states
    loadingSessions: false,
    loadingMessages: {},
    sendingMessage: false,
  }),

  getters: {
    activeRoom: (state) => {
      return state.sessions.find(s => s.session_id === state.activeRoomId) || null
    },

    activeMessages: (state) => {
      if (!state.activeRoomId) return []

      return (state.messages[state.activeRoomId] || []).filter(msg => {
        // Filter out internal messages
        if (msg.is_internal) return false

        // Skip delivery receipts or system messages (e.g., "Sent via fonnte.com")
        if (msg.message && msg.message.includes('Sent via fonnte.com')) return false
        if (msg.message && msg.message.includes('Message queued')) return false

        return true
      })
        .map(msg => {
          // Mark outgoing messages
          if (msg.sender === 'agent') {
            return { ...msg, is_outgoing: true }
          }
          return msg
        })

    },

    activeGroupedMessages: (state) => {
      const messages = state.activeMessages
      if(!messages || messages.length === 0) return []
      const groups = []
      let currentDate = null
      messages.forEach((message) => {
        const messageDate = new Date(message.created_at).toDateString()

        if (messageDate !== currentDate) {
          currentDate = messageDate
          groups.push({
            type: 'date',
            date: formatDateSeparator(message.created_at),
          })
        }

        if (message.sender === 'system') {
          groups.push({
            type: 'system',
            data: message,
            date: formatTime(message.created_at),
          })
        }
        else {
          groups.push({
            type: 'message',
            data: message,
          })
        }
      })

      return groups
    },

    activeTypingUsers: (state) => {
      if (!state.activeRoomId) return []
      return state.typingUsers[state.activeRoomId] || []
    },

    isConnected: (state) => state.connectionStatus === 'connected',

    getMessagesBySessionId: (state) => (sessionId) => {
      return state.messages[sessionId] || []
    },
  },

  actions: {
    /* =====================
       CONNECTION STATUS
    ===================== */
    setConnectionStatus(status) {
      this.connectionStatus = status
    },

    /* =====================
       SESSIONS
    ===================== */
    async loadSessions() {
      this.loadingSessions = true
      try {
        const res = await axios.get('/chat/sessions')
        this.sessions = res.data

        // Auto-select first session if none selected
        if (!this.activeRoomId && this.sessions.length > 0) {
          this.activeRoomId = this.sessions[0].session_id
        }
      } catch (error) {
        console.error('Failed to load sessions:', error)
        throw error
      } finally {
        this.loadingSessions = false
      }
    },

    setActiveRoom(sessionId) {
      this.activeRoomId = sessionId
    },

    updateSessionLastMessage(sessionId, message) {
      const session = this.sessions.find(s => s.session_id === sessionId)
      if (session) {
        session.last_message = message.message || ''
        session.updated_at = message.created_at || new Date().toISOString()
      }
    },

    /* =====================
       MESSAGES
    ===================== */
    async loadMessages(sessionId, page = 1) {
      if (!sessionId) return

      this.loadingMessages[sessionId] = true
      try {
        const res = await axios.get(`/chat/sessions/${sessionId}/messages`, {
          params: { page }
        })

        if (page === 1) {
          this.messages[sessionId] = res.data
        } else {
          // Prepend for infinite scroll (older messages)
          this.messages[sessionId] = [...res.data, ...(this.messages[sessionId] || [])]
        }

        return res.data
      } catch (error) {
        console.error('Failed to load messages:', error)
        throw error
      } finally {
        this.loadingMessages[sessionId] = false
      }
    },

    addMessage(sessionId, message) {
      if (!this.messages[sessionId]) {
        this.messages[sessionId] = []
      }

      // Check if message already exists (prevent duplicates)
      // But allow replacement if existing message is optimistic
      const existingIndex = this.messages[sessionId].findIndex(m => m.id === message.id)

      if (existingIndex !== -1) {
        const existing = this.messages[sessionId][existingIndex]

        // Replace if existing is optimistic and new is real
        if (existing.is_optimistic && !message.is_optimistic) {
          this.messages[sessionId][existingIndex] = message
          this.updateSessionLastMessage(sessionId, message)
        }
        // Otherwise skip duplicate
        return
      }

      // Add new message
      this.messages[sessionId].push(message)
      this.updateSessionLastMessage(sessionId, message)

      // Increment unread count if message is from customer and not in active room
      if (message.sender === 'customer' && sessionId !== this.activeRoomId) {
        const session = this.sessions.find(s => s.session_id === sessionId)
        if (session) {
          session.unread_count = (session.unread_count || 0) + 1
        }
      }
    },

    updateMessage(sessionId, updatedMessage) {
      if (!this.messages[sessionId]) return

      const index = this.messages[sessionId].findIndex(m => m.id === updatedMessage.id)
      if (index !== -1) {
        this.messages[sessionId][index] = { ...this.messages[sessionId][index], ...updatedMessage }
      }
    },

    /* =====================
       OPTIMISTIC UPDATES
    ===================== */
    addOptimisticMessage(sessionId, message, tempId) {
      const optimisticMsg = {
        id: tempId,
        session_id: sessionId,
        message: message.message || '',
        sender: 'agent',
        delivery_status: 'pending',
        created_at: new Date().toISOString(),
        media_url: message.media_url || null,
        media_type: message.media_type || null,
        is_optimistic: true,
        is_outgoing: true,
        is_internal: false,
        ...message,
      }

      this.addMessage(sessionId, optimisticMsg)
      this.optimisticMessages[tempId] = optimisticMsg
    },

    replaceOptimisticMessage(tempId, realMessage) {
      const optimistic = this.optimisticMessages[tempId]
      if (!optimistic) return

      const sessionId = optimistic.session_id
      if (!this.messages[sessionId]) return

      // Remove optimistic message
      this.messages[sessionId] = this.messages[sessionId].filter(m => m.id !== tempId)

      // Add real message
      this.addMessage(sessionId, realMessage)

      // Clean up
      delete this.optimisticMessages[tempId]
    },

    removeOptimisticMessage(tempId) {
      const optimistic = this.optimisticMessages[tempId]
      if (!optimistic) {
        console.warn('⚠️ replaceOptimisticMessage: optimistic message not found', tempId)
        return
      }

      const sessionId = optimistic.session_id
      if (!this.messages[sessionId]) {
        console.warn('⚠️ replaceOptimisticMessage: session not found', sessionId)
        return
      }

      console.log('🔄 Replacing optimistic message', {
        tempId,
        realMessageId: realMessage.id,
        sessionId
      })

      // Remove optimistic message
      this.messages[sessionId] = this.messages[sessionId].filter(m => m.id !== tempId)

      // Add real message with all necessary flags
      const messageToAdd = {
        ...realMessage,
        is_outgoing: true,
        is_optimistic: false,
      }

      this.addMessage(sessionId, messageToAdd)

      // Clean up
      delete this.optimisticMessages[tempId]

      console.log('✅ Optimistic message replaced successfully')
    },

    /* =====================
       SEND MESSAGE
    ===================== */
    async sendMessage(sessionId, messageData) {
      const tempId = `temp_${Date.now()}_${Math.random()}`

      // Add optimistic message
      this.addOptimisticMessage(sessionId, messageData, tempId)

      this.sendingMessage = true
      try {
        const formData = new FormData()
        formData.append('message', messageData.message || '')
        if (messageData.media) {
          formData.append('media', messageData.media)
        }

        const res = await axios.post(`/chat/sessions/${sessionId}/messages`, formData)
        const realMessage = res.data.data || res.data

        // Replace optimistic with real message
        this.replaceOptimisticMessage(tempId, realMessage)

        return realMessage
      } catch (error) {
        // Remove optimistic message on error
        this.removeOptimisticMessage(tempId)
        throw error
      } finally {
        this.sendingMessage = false
      }
    },

    /* =====================
       REACTIONS
    ===================== */
    async addReaction(messageId, emoji) {
      const sessionId = this.activeRoomId
      if (!sessionId) return

      // Optimistic update
      const message = this.messages[sessionId]?.find(m => m.id === messageId)
      if (message) {
        if (!message.reactions) {
          message.reactions = {}
        }
        message.reactions[emoji] = (message.reactions[emoji] || 0) + 1
      }

      try {
        await axios.post(`/chat/messages/${messageId}/reaction`, { emoji })
      } catch (error) {
        // Rollback on error
        if (message && message.reactions) {
          message.reactions[emoji] = Math.max(0, (message.reactions[emoji] || 1) - 1)
          if (message.reactions[emoji] === 0) {
            delete message.reactions[emoji]
          }
        }
        throw error
      }
    },

    /* =====================
       TYPING INDICATORS
    ===================== */
    setTyping(sessionId, userId, userName, isTyping) {
      if (!this.typingUsers[sessionId]) {
        this.typingUsers[sessionId] = []
      }

      if (isTyping) {
        // Add user if not already in list
        const exists = this.typingUsers[sessionId].find(u => u.id === userId)
        if (!exists) {
          this.typingUsers[sessionId].push({ id: userId, name: userName, timestamp: Date.now() })
        }
      } else {
        // Remove user from typing list
        this.typingUsers[sessionId] = this.typingUsers[sessionId].filter(u => u.id !== userId)
      }
    },

    clearOldTypingIndicators(sessionId) {
      if (!this.typingUsers[sessionId]) return

      const now = Date.now()
      const timeout = 3000 // 3 seconds

      this.typingUsers[sessionId] = this.typingUsers[sessionId].filter(u => {
        return (now - u.timestamp) < timeout
      })
    },

    /* =====================
       READ RECEIPTS
    ===================== */
    async markAsRead(sessionId) {
      try {
        await axios.post(`/chat/sessions/${sessionId}/mark-read`)

        // Reset unread count locally
        const session = this.sessions.find(s => s.session_id === sessionId)
        if (session) {
          session.unread_count = 0
        }
      } catch (error) {
        console.error('Failed to mark as read:', error)
      }
    },

    /* =====================
       CLOSE SESSION
    ===================== */
    async closeSession(sessionId) {
      try {
        const res = await axios.post(`/chat/sessions/${sessionId}/close`)
        const closedSession = res.data.session || null

        // Update session status to closed instead of removing it
        const session = this.sessions.find(s => s.session_id === sessionId)
        if (session && closedSession) {
          session.status = 'closed'
          session.closed_at = closedSession?.closed_at || new Date().toISOString()
        }

        // Refresh messages to display the system message about session closure
        if (sessionId === this.activeRoomId) {
          await this.loadMessages(sessionId, 1)
        }

        return res.data
      } catch (error) {
        console.error('Failed to close session:', error)
        throw error
      }
    },

    /* =====================
       REASSIGN SESSION
    ===================== */
    async getAvailableAgents() {
      try {
        const res = await axios.get('/chat/agents/available')
        return res.data.agents || []
      } catch (error) {
        console.error('Failed to get available agents:', error)
        throw error
      }
    },

    async reassignSession(sessionId, agentId) {
      try {
        const res = await axios.post(`/chat/sessions/${sessionId}/reassign`, {
          agent_id: agentId,
        })

        // Refresh messages to display the system message about reassignment
        if (sessionId === this.activeRoomId) {
          await this.loadMessages(sessionId, 1)
        }

        // Remove session from current agent's list (karena sudah dialihkan)
        const sessionIndex = this.sessions.findIndex(s => s.session_id === sessionId)
        if (sessionIndex !== -1) {
          this.sessions.splice(sessionIndex, 1)

          // If this was the active session, clear it
          if (this.activeRoomId === sessionId) {
            // Set to first available session or null
            this.activeRoomId = this.sessions.length > 0 ? this.sessions[0].session_id : null
          }
        }

        return res.data
      } catch (error) {
        console.error('Failed to reassign session:', error)
        throw error
      }
    },
  },
})
