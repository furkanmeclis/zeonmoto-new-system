import { Head, router, usePage } from '@inertiajs/react'
import { useState, useEffect } from 'react'
import { Heart, ShoppingCart, Plus, Minus, Package } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardFooter } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import GuestLayout from '@/components/layouts/GuestLayout'
import { useFavorites } from '@/hooks/useFavorites'
import { usePriceVisibility } from '@/hooks/usePriceVisibility'
import axios from 'axios'

interface Product {
    id: number
    name: string
    sku: string
    price: number
    base_price: number
    image: string | null
    categories: Array<{ id: number; name: string; slug: string }>
}

interface Props {
    cartCount: number
}

export default function FavoritesIndex({ cartCount }: Props) {
    const { cartItems } = usePage().props as any
    const { favorites, removeFavorite, toggleFavorite, checkFavorite } = useFavorites()
    const { isPriceVisible } = usePriceVisibility()
    const [products, setProducts] = useState<Product[]>([])
    const [loading, setLoading] = useState(true)
    const [error, setError] = useState<string | null>(null)

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

    const formatPrice = (price: number) => {
        return new Intl.NumberFormat('tr-TR', {
            style: 'currency',
            currency: 'TRY',
        }).format(price)
    }

    const handleAddToCart = (productId: number, quantity: number = 1) => {
        router.post(
            '/cart/add',
            { product_id: productId, quantity },
            {
                preserveScroll: true,
            }
        )
    }

    const handleUpdateQuantity = (productId: number, currentQuantity: number, change: number) => {
        const newQuantity = Math.max(1, Math.min(999, currentQuantity + change))
        const cartItem = cartItems?.[productId]
        
        if (!cartItem) return
        
        router.put(
            `/cart/items/${cartItem.id}`,
            { quantity: newQuantity },
            {
                preserveScroll: true,
            }
        )
    }

    const getCartItemQuantity = (productId: number): number => {
        return cartItems?.[productId]?.quantity || 0
    }

    const hasDiscount = (product: Product) => product.base_price > product.price

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
                    <Card className="text-center py-12">
                        <CardContent>
                            <Heart className="h-16 w-16 mx-auto text-muted-foreground mb-4" />
                            <p className="text-lg text-muted-foreground mb-4">
                                Henüz favori ürününüz yok
                            </p>
                            <Button asChild>
                                <a href="/shop">Ürünleri Keşfet</a>
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        {products.map((product) => {
                            const isFavorite = checkFavorite(product.id)
                            const cartQuantity = getCartItemQuantity(product.id)
                            
                            return (
                                <Card key={product.id} className="overflow-hidden hover:shadow-lg transition-shadow">
                                    <div className="relative">
                                        <a href={`/products/${product.id}`} className="block">
                                            <div className="aspect-square bg-white relative overflow-hidden">
                                                {product.image ? (
                                                    <>
                                                        <img
                                                            src={product.image}
                                                            alt={product.name}
                                                            className="w-full h-full object-contain"
                                                        />
                                                        <img
                                                            src="/logo.png"
                                                            alt=""
                                                            className="absolute inset-0 w-full h-full object-contain opacity-20 pointer-events-none"
                                                        />
                                                    </>
                                                ) : (
                                                    <div className="w-full h-full flex items-center justify-center text-muted-foreground">
                                                        <Package className="h-12 w-12" />
                                                    </div>
                                                )}
                                                {hasDiscount(product) && (
                                                    <Badge
                                                        variant="destructive"
                                                        className="absolute top-2 left-2 z-10"
                                                    >
                                                        İndirim
                                                    </Badge>
                                                )}
                                            </div>
                                        </a>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="absolute top-2 right-2 bg-background/80 hover:bg-background"
                                            onClick={(e) => {
                                                e.preventDefault()
                                                toggleFavorite(product.id)
                                            }}
                                        >
                                            <Heart className={`h-5 w-5 ${isFavorite ? 'fill-destructive text-destructive' : ''}`} />
                                        </Button>
                                    </div>
                                    <CardContent className="p-4">
                                        <a href={`/products/${product.id}`}>
                                            <h3 className="font-semibold mb-2 line-clamp-2 hover:text-primary transition-colors">
                                                {product.name}
                                            </h3>
                                        </a>
                                        <p className="text-sm text-muted-foreground mb-2">SKU: {product.sku}</p>
                                        <div className="flex items-center justify-between mb-3">
                                            <div>
                                                {isPriceVisible ? (
                                                    <>
                                                        <span className="text-lg font-bold text-primary">
                                                            {formatPrice(product.price)}
                                                        </span>
                                                        {hasDiscount(product) && (
                                                            <span className="text-sm text-muted-foreground line-through ml-2">
                                                                {formatPrice(product.base_price)}
                                                            </span>
                                                        )}
                                                    </>
                                                ) : (
                                                    <span className="text-sm text-muted-foreground">
                                                        Fiyat için PIN gerekli
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    </CardContent>
                                    <CardFooter className="p-4 pt-0">
                                        {cartQuantity > 0 ? (
                                            <div className="w-full space-y-2">
                                                <div className="flex items-center justify-between border rounded-md px-2 py-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-7 w-7"
                                                        onClick={() => handleUpdateQuantity(product.id, cartQuantity, -1)}
                                                    >
                                                        <Minus className="h-3 w-3" />
                                                    </Button>
                                                    <span className="text-sm font-medium px-2">
                                                        {cartQuantity} adet
                                                    </span>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-7 w-7"
                                                        onClick={() => handleUpdateQuantity(product.id, cartQuantity, 1)}
                                                    >
                                                        <Plus className="h-3 w-3" />
                                                    </Button>
                                                </div>
                                                <Button
                                                    variant="outline"
                                                    className="w-full"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <a href={`/products/${product.id}`}>Detayları Gör</a>
                                                </Button>
                                            </div>
                                        ) : (
                                            <Button
                                                onClick={() => handleAddToCart(product.id)}
                                                className="w-full"
                                                size="sm"
                                            >
                                                <ShoppingCart className="h-4 w-4 mr-2" />
                                                Sepete Ekle
                                            </Button>
                                        )}
                                    </CardFooter>
                                </Card>
                            )
                        })}
                    </div>
                )}
            </div>
        </GuestLayout>
    )
}

