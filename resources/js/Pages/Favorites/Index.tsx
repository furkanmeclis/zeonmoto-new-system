import { Head, router, usePage } from '@inertiajs/react'
import { useState, useEffect } from 'react'
import { motion } from 'framer-motion'
import { Heart } from 'lucide-react'
import { Button } from '@/components/ui/button'
import GuestLayout from '@/components/layouts/GuestLayout'
import { useFavorites } from '@/hooks/useFavorites'
import ProductCard from '@/components/Shop/ProductCard'
import axios from 'axios'

interface Product {
    id: number
    name: string
    sku: string
    price: number
    retail_price?: number
    base_price: number
    image: string | null
    categories: Array<{ id: number; name: string; slug: string }>
}

interface Props {
    cartCount: number
}

export default function FavoritesIndex({ cartCount }: Props) {
    const { cartItems } = usePage().props as any
    const { favorites } = useFavorites()
    const [products, setProducts] = useState<Product[]>([])
    const [loading, setLoading] = useState(true)
    const [error, setError] = useState<string | null>(null)
    const [quantityInputs, setQuantityInputs] = useState<Record<number, string>>({})

    useEffect(() => {
        const loadFavorites = async () => {
            if (favorites.length === 0) {
                setProducts([])
                setLoading(false)
                return
            }

            try {
                setLoading(true)
                setError(null)

                const response = await axios.post('/favorites/products', {
                    ids: favorites,
                })

                setProducts(response.data.products || [])
            } catch (err) {
                console.error('Error loading favorites:', err)
                setError('Favoriler yüklenirken bir hata oluştu.')
            } finally {
                setLoading(false)
            }
        }

        loadFavorites()
    }, [favorites])

    const handleAddToCart = (productId: number, quantity: number = 1) => {
        router.post(
            '/cart/add',
            { product_id: productId, quantity },
            {
                preserveScroll: true,
            }
        )
    }

    const handleUpdateQuantity = (cartItemId: number, quantity: number) => {
        router.put(
            `/cart/items/${cartItemId}`,
            { quantity },
            {
                preserveScroll: true,
            }
        )
    }

    const handleRemoveFromCart = (cartItemId: number) => {
        router.delete(`/cart/items/${cartItemId}`, {
            preserveScroll: true,
        })
    }

    const handleQuantityInputChange = (productId: number, value: string) => {
        setQuantityInputs(prev => ({
            ...prev,
            [productId]: value
        }))
    }

    const handleQuantityInputSubmit = (productId: number) => {
        const inputValue = quantityInputs[productId]
        if (inputValue === undefined) return

        const newQuantity = Math.max(1, Math.min(999, parseInt(inputValue) || 1))
        const cartItem = cartItems?.[productId]

        if (!cartItem) return

        setQuantityInputs(prev => {
            const updated = { ...prev }
            delete updated[productId]
            return updated
        })

        router.put(
            `/cart/items/${cartItem.id}`,
            { quantity: newQuantity },
            {
                preserveScroll: true,
            }
        )
    }

    const container = {
        hidden: { opacity: 0 },
        show: {
            opacity: 1,
            transition: {
                staggerChildren: 0.1
            }
        }
    }

    const item = {
        hidden: { opacity: 0, y: 20 },
        show: { opacity: 1, y: 0 }
    }

    return (
        <GuestLayout cartCount={cartCount}>
            <Head title="Favoriler" />

            <div className="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div className="flex items-center justify-between mb-8">
                    <div>
                        <h1 className="text-3xl font-bold mb-2">Favorilerim</h1>
                        <p className="text-muted-foreground">
                            {favorites.length > 0
                                ? `${favorites.length} ürün favorilerinizde`
                                : 'Henüz favori ürününüz yok'}
                        </p>
                    </div>
                </div>

                {loading ? (
                    <div className="text-center py-12">
                        <p className="text-muted-foreground">Yükleniyor...</p>
                    </div>
                ) : error ? (
                    <div className="text-center py-12">
                        <p className="text-destructive">{error}</p>
                    </div>
                ) : products.length === 0 ? (
                    <div className="bg-white rounded-lg shadow-lg p-12 text-center">
                        <Heart className="h-16 w-16 mx-auto text-muted-foreground mb-4" />
                        <p className="text-lg text-muted-foreground mb-4">
                            Henüz favori ürününüz yok
                        </p>
                        <Button asChild className="bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700">
                            <a href="/shop">Ürünleri Keşfet</a>
                        </Button>
                    </div>
                ) : (
                    <motion.div
                        variants={container}
                        initial="hidden"
                        animate="show"
                        className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 gap-6"
                    >
                        {products.map((product) => {
                            const cartItem = cartItems?.[product.id]
                            const cartItemQuantity = cartItem?.quantity || 0
                            const cartItemId = cartItem?.id || null

                            return (
                                <motion.div key={product.id} variants={item}>
                                    <ProductCard
                                        product={{
                                            ...product,
                                            images: product.image ? [product.image] : [],
                                            is_discount: product.base_price > product.price ? 1 : 0
                                        }}
                                        cartItemQuantity={cartItemQuantity}
                                        cartItemId={cartItemId}
                                        onAddToCart={handleAddToCart}
                                        onUpdateQuantity={handleUpdateQuantity}
                                        onRemoveFromCart={handleRemoveFromCart}
                                        onQuantityInputChange={handleQuantityInputChange}
                                        onQuantityInputSubmit={handleQuantityInputSubmit}
                                        quantityInput={quantityInputs[product.id]}
                                    />
                                </motion.div>
                            )
                        })}
                    </motion.div>
                )}
            </div>
        </GuestLayout>
    )
}

