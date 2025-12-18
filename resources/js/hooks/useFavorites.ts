import { useState, useEffect } from 'react'
import { getFavorites, isFavorite, addToFavorites, removeFromFavorites, toggleFavorite as toggleFavoriteUtil } from '@/lib/favorites'

/**
 * Hook to manage favorites with reactivity
 */
export function useFavorites() {
    const [favorites, setFavorites] = useState<number[]>([])

    useEffect(() => {
        // Initial load
        setFavorites(getFavorites())

        // Listen for changes
        const handleChange = () => {
            setFavorites(getFavorites())
        }

        window.addEventListener('favorites-changed', handleChange)

        return () => {
            window.removeEventListener('favorites-changed', handleChange)
        }
    }, [])

    const checkFavorite = (productId: number) => {
        return isFavorite(productId)
    }

    const addFavorite = (productId: number) => {
        addToFavorites(productId)
    }

    const removeFavorite = (productId: number) => {
        removeFromFavorites(productId)
    }

    const toggleFavorite = (productId: number) => {
        return toggleFavoriteUtil(productId)
    }

    return {
        favorites,
        checkFavorite,
        addFavorite,
        removeFavorite,
        toggleFavorite,
        count: favorites.length,
    }
}

