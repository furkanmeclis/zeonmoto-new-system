import { useEffect, useRef } from 'react'
import axios from 'axios'
import { router } from '@inertiajs/react'

const CART_SESSION_KEY = 'zeonmoto_cart_session_key'

/**
 * Hook to manage cart session persistence using localStorage.
 * Restores cart session when page loads or session is lost.
 */
export function useCartSession() {
    const hasRestored = useRef(false)

    useEffect(() => {
        const restoreCartSession = async () => {
            try {
                // Get stored session key from localStorage
                const storedSessionKey = localStorage.getItem(CART_SESSION_KEY)

                if (storedSessionKey) {
                    // Try to restore cart with stored session key
                    try {
                        const response = await axios.post('/cart/restore', {
                            session_key: storedSessionKey,
                        })

                        if (response.data.success) {
                            // Update stored session key if changed
                            if (response.data.session_key) {
                                localStorage.setItem(CART_SESSION_KEY, response.data.session_key)
                            }
                            console.log('Cart restored successfully:', response.data.message)
                        }
                    } catch (error) {
                        // If restore fails, get new session key
                        console.warn('Cart restore failed, getting new session key')
                        await getAndStoreSessionKey()
                    }
                } else {
                    // No stored session key, get current one
                    await getAndStoreSessionKey()
                }
            } catch (error) {
                console.error('Error restoring cart session:', error)
                // Try to get new session key as fallback
                await getAndStoreSessionKey()
            }
        }

        const getAndStoreSessionKey = async () => {
            try {
                const response = await axios.get('/cart/session-key')
                if (response.data.session_key) {
                    localStorage.setItem(CART_SESSION_KEY, response.data.session_key)
                }
            } catch (error) {
                console.error('Error getting session key:', error)
            }
        }

        // Restore cart session on mount (only once)
        if (!hasRestored.current) {
            restoreCartSession()
            hasRestored.current = true
        }

        // Also restore on page visibility change (when user comes back to tab)
        const handleVisibilityChange = () => {
            if (document.visibilityState === 'visible') {
                // Reset flag to allow restore on next visibility change
                hasRestored.current = false
            }
        }

        document.addEventListener('visibilitychange', handleVisibilityChange)

        return () => {
            document.removeEventListener('visibilitychange', handleVisibilityChange)
        }
    }, [])

    /**
     * Manually refresh session key (useful after cart operations)
     */
    const refreshSessionKey = async () => {
        try {
            const response = await axios.get('/cart/session-key')
            if (response.data.session_key) {
                localStorage.setItem(CART_SESSION_KEY, response.data.session_key)
            }
        } catch (error) {
            console.error('Error refreshing session key:', error)
        }
    }

    return {
        refreshSessionKey,
    }
}
