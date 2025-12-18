const FAVORITES_STORAGE_KEY = 'moto_gpt_favorites'

export interface FavoriteProduct {
    id: number
    addedAt: string
}

/**
 * Get all favorite product IDs from localStorage
 */
export function getFavorites(): number[] {
    if (typeof window === 'undefined') return []
    
    try {
        const stored = localStorage.getItem(FAVORITES_STORAGE_KEY)
        if (!stored) return []
        
        const favorites: FavoriteProduct[] = JSON.parse(stored)
        return favorites.map(f => f.id)
    } catch (error) {
        console.error('Error reading favorites from localStorage:', error)
        return []
    }
}

/**
 * Get all favorite products with metadata
 */
export function getFavoritesWithMetadata(): FavoriteProduct[] {
    if (typeof window === 'undefined') return []
    
    try {
        const stored = localStorage.getItem(FAVORITES_STORAGE_KEY)
        if (!stored) return []
        
        return JSON.parse(stored) as FavoriteProduct[]
    } catch (error) {
        console.error('Error reading favorites from localStorage:', error)
        return []
    }
}

/**
 * Check if a product is in favorites
 */
export function isFavorite(productId: number): boolean {
    const favorites = getFavorites()
    return favorites.includes(productId)
}

/**
 * Add product to favorites
 */
export function addToFavorites(productId: number): void {
    if (typeof window === 'undefined') return
    
    try {
        const favorites = getFavoritesWithMetadata()
        
        // Check if already in favorites
        if (favorites.some(f => f.id === productId)) {
            return
        }
        
        favorites.push({
            id: productId,
            addedAt: new Date().toISOString(),
        })
        
        localStorage.setItem(FAVORITES_STORAGE_KEY, JSON.stringify(favorites))
        
        // Dispatch custom event for reactivity
        window.dispatchEvent(new CustomEvent('favorites-changed'))
    } catch (error) {
        console.error('Error adding to favorites:', error)
    }
}

/**
 * Remove product from favorites
 */
export function removeFromFavorites(productId: number): void {
    if (typeof window === 'undefined') return
    
    try {
        const favorites = getFavoritesWithMetadata()
        const filtered = favorites.filter(f => f.id !== productId)
        
        localStorage.setItem(FAVORITES_STORAGE_KEY, JSON.stringify(filtered))
        
        // Dispatch custom event for reactivity
        window.dispatchEvent(new CustomEvent('favorites-changed'))
    } catch (error) {
        console.error('Error removing from favorites:', error)
    }
}

/**
 * Toggle favorite status
 */
export function toggleFavorite(productId: number): boolean {
    if (isFavorite(productId)) {
        removeFromFavorites(productId)
        return false
    } else {
        addToFavorites(productId)
        return true
    }
}

/**
 * Clear all favorites
 */
export function clearFavorites(): void {
    if (typeof window === 'undefined') return
    
    try {
        localStorage.removeItem(FAVORITES_STORAGE_KEY)
        window.dispatchEvent(new CustomEvent('favorites-changed'))
    } catch (error) {
        console.error('Error clearing favorites:', error)
    }
}

