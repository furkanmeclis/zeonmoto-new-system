import { Head, Link, router, usePage } from '@inertiajs/react'
import { ShoppingCart, Package, Truck, ArrowRight, Search, Plus, Minus, Heart, Trash2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardFooter } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import GuestLayout from '@/components/layouts/GuestLayout'
import { useState } from 'react'
import { useFavorites } from '@/hooks/useFavorites'
import { usePriceVisibility } from '@/hooks/usePriceVisibility'

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
    products: Product[]
    cartCount: number
}

export default function Home({ products, cartCount }: Props) {
    const { cartItems } = usePage().props as any
    const [searchQuery, setSearchQuery] = useState('')
    const [quantityInputs, setQuantityInputs] = useState<Record<number, string>>({})
    const { checkFavorite, toggleFavorite } = useFavorites()
    const { isPriceVisible } = usePriceVisibility()

    const formatPrice = (price: number) => {
        return new Intl.NumberFormat('tr-TR', {
            style: 'currency',
            currency: 'TRY',
        }).format(price)
    }

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault()
        if (searchQuery.trim()) {
            router.visit(`/shop?search=${encodeURIComponent(searchQuery.trim())}`)
        } else {
            router.visit('/shop')
        }
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

    const handleQuantityInputChange = (productId: number, value: string) => {
        setQuantityInputs(prev => ({
            ...prev,
            [productId]: value
        }))
    }

    const handleQuantityInputSubmit = (productId: number) => {
        const inputValue = quantityInputs[productId]
        if (!inputValue) return

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

    const getCartItemQuantity = (productId: number): number => {
        return cartItems?.[productId]?.quantity || 0
    }

    const getCartItemId = (productId: number): number | null => {
        return cartItems?.[productId]?.id || null
    }

    const handleRemoveFromCart = (productId: number) => {
        const cartItemId = getCartItemId(productId)
        if (!cartItemId) return

        router.delete(`/cart/items/${cartItemId}`, {
            preserveScroll: true,
        })
    }

    const hasDiscount = (product: Product) => product.base_price > product.price

    return (
        <GuestLayout cartCount={cartCount}>
            <Head title="Ana Sayfa" />

            {/* Hero Section */}
            <div className="bg-gradient from-primary/10 via-background to-background border-b">
                <div className="container mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-24">
                    <div className="max-w-3xl mx-auto text-center">
                        <h1 className="text-4xl md:text-6xl font-bold mb-6">
                            Moto GPT
                        </h1>
                        <p className="text-xl text-muted-foreground mb-8 max-w-2xl mx-auto">
                            Kaliteli ürünler, hızlı teslimat.
                        </p>

                        {/* Search Bar */}
                        <form onSubmit={handleSearch} className="max-w-2xl mx-auto mb-8">
                            <div className="flex gap-2">
                                <div className="relative flex-1">
                                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-muted-foreground" />
                                    <Input
                                        type="text"
                                        placeholder="Ürün ara..."
                                        value={searchQuery}
                                        onChange={(e) => setSearchQuery(e.target.value)}
                                        className="pl-10 h-12 text-lg"
                                    />
                                </div>
                                <Button type="submit" size="lg" className="h-12 px-8">
                                    Ara
                                </Button>
                            </div>
                        </form>

                        <div className="flex gap-4 justify-center flex-wrap">
                            <Button asChild size="lg" variant="default">
                                <Link href="/shop">Tüm Ürünleri Gör</Link>
                            </Button>
                            <Button asChild size="lg" variant="outline">
                                <Link href="/shop">
                                    Ürünleri Keşfet
                                    <ArrowRight className="ml-2 h-4 w-4" />
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
            <div className="max-w-3xl mx-auto text-center flex flex-col items-center mt-16">
                <img
                    src="/images/banner.jpeg"
                    alt="Moto GPT Banner"
                    className="rounded-lg shadow-lg mb-8 max-h-128 mx-4 w-full object-cover"
                    style={{ maxWidth: 800 }}
                />
            </div>

            {/* Features Section */}
            <div className="container mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">

                    {/* 50.000 TL üzeri Kargo Bize Ait */}
                    <Card className="border-2 hover:border-primary/50 transition-colors flex flex-col justify-center">
                        <CardContent className="p-6 text-center flex flex-col items-center">
                            <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary/10 mb-4">
                                <Truck className="h-8 w-8 text-primary" />
                            </div>
                            <h3 className="text-xl font-semibold mb-2">50.000 TL Üzeri Kargo Bize Ait</h3>
                            <p className="text-muted-foreground">
                                50.000 TL ve üzeri alışverişlerde kargo ücreti tamamen bize ait!
                            </p>
                        </CardContent>
                    </Card>

                    {/* Kredi Kartı ile Güvenli Alışveriş + 3 Taksit */}
                    <Card className="border-2 hover:border-primary/50 transition-colors flex flex-col justify-center">
                        <CardContent className="p-6 text-center flex flex-col items-center">
                            <div className="flex items-center justify-center space-x-2 mb-2">
                                <img
                                    src="/images/visa.png"
                                    alt="Visa"
                                    className="h-8 w-auto drop-shadow-md"
                                    style={{ borderRadius: 4 }}
                                />
                                <img
                                    src="/images/mastercard.png"
                                    alt="Mastercard"
                                    className="h-8 w-auto drop-shadow-md"
                                    style={{ borderRadius: 4 }}
                                />
                                <img
                                    src="/images/troy.png"
                                    alt="Troy"
                                    className="h-8 w-auto drop-shadow-md"
                                    style={{ borderRadius: 4 }}
                                />
                            </div>
                            <span className="text-green-700 font-bold text-lg mb-1">
                                Vade Farksız 3 Taksit!
                            </span>
                            <h3 className="text-xl font-semibold mb-2">Kredi Kartına 3 Taksit</h3>
                            <p className="text-muted-foreground">
                                Tüm kartlara güvenli alışveriş ve ödeme kolaylığı.
                            </p>
                        </CardContent>
                    </Card>

                    {/* Geniş Ürün Yelpazesi */}
                    <Card className="border-2 hover:border-primary/50 transition-colors flex flex-col justify-center">
                        <CardContent className="p-6 text-center flex flex-col items-center">
                            <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary/10 mb-4">
                                <Package className="h-8 w-8 text-primary" />
                            </div>
                            <h3 className="text-xl font-semibold mb-2">Geniş Ürün Yelpazesi</h3>
                            <p className="text-muted-foreground">
                                Binlerce ürün çeşidi ile ihtiyacınız olan her şeyi bulun.
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>

            {/* Featured Products Section */}
            {products.length > 0 && (
                <div className="bg-muted/50 py-16">
                    <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex items-center justify-between mb-8">
                            <div>
                                <h2 className="text-3xl font-bold mb-2">Öne Çıkan Ürünler</h2>
                                <p className="text-muted-foreground">
                                    En popüler ve öne çıkan ürünlerimizi keşfedin
                                </p>
                            </div>
                            <Button asChild variant="outline">
                                <Link href="/shop">
                                    Tümünü Gör
                                    <ArrowRight className="ml-2 h-4 w-4" />
                                </Link>
                            </Button>
                        </div>

                        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-4">
                            {products.map((product) => {
                                const isFavorite = checkFavorite(product.id)
                                return (
                                    <Card key={product.id} className="overflow-hidden hover:shadow-lg transition-all group">
                                        <div className="relative">
                                            <a href={`/products/${product.id}`} className="block">
                                                <div className="aspect-square bg-white relative overflow-hidden">
                                                    {product.image ? (
                                                        <>
                                                            <img
                                                                src={product.image}
                                                                alt={product.name}
                                                                className="w-full h-full object-contain group-hover:scale-105 transition-transform duration-300"
                                                            />
                                                            <img
                                                                src="/logo.png"
                                                                alt=""
                                                                className="absolute inset-0 w-full h-full object-contain opacity-10 pointer-events-none"
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
                                        <CardContent className="p-3">
                                            <a href={`/products/${product.id}`}>
                                                <h3 className="font-semibold text-sm mb-1 line-clamp-2 hover:text-primary transition-colors">
                                                    {product.name}
                                                </h3>
                                            </a>
                                            <p className="text-xs text-muted-foreground mb-1">SKU: {product.sku}</p>
                                            <div className="flex items-center justify-between mb-2">
                                                <div>
                                                    {isPriceVisible ? (
                                                        <>
                                                            <span className="text-base font-bold text-primary">
                                                                {formatPrice(product.price)}
                                                            </span>
                                                            {hasDiscount(product) && (
                                                                <span className="text-xs text-muted-foreground line-through ml-2">
                                                                    {formatPrice(product.base_price)}
                                                                </span>
                                                            )}
                                                        </>
                                                    ) : (
                                                        <span className="text-xs text-muted-foreground">
                                                            Fiyat için PIN gerekli
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        </CardContent>
                                        <CardFooter className="p-3 pt-0">
                                            {getCartItemQuantity(product.id) > 0 ? (
                                                <div className="w-full space-y-2">
                                                    <div className="flex items-center justify-between border rounded-md px-2 py-1">
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="h-7 w-7 text-red-500 hover:text-red-600 hover:bg-red-50"
                                                            onClick={() => handleUpdateQuantity(product.id, getCartItemQuantity(product.id), -1)}
                                                        >
                                                            <Minus className="h-3 w-3" />
                                                        </Button>
                                                        <Input
                                                            type="number"
                                                            min="1"
                                                            max="999"
                                                            value={quantityInputs[product.id] ?? getCartItemQuantity(product.id)}
                                                            onChange={(e) => handleQuantityInputChange(product.id, e.target.value)}
                                                            onKeyDown={(e) => {
                                                                if (e.key === 'Enter') {
                                                                    e.currentTarget.blur()
                                                                    handleQuantityInputSubmit(product.id)
                                                                }
                                                            }}
                                                            onBlur={() => handleQuantityInputSubmit(product.id)}
                                                            className="w-16 h-7 text-center text-sm font-medium px-1 border-0 focus-visible:ring-0 focus-visible:ring-offset-0"
                                                        />
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="h-7 w-7 text-green-500 hover:text-green-600 hover:bg-green-50"
                                                            onClick={() => handleUpdateQuantity(product.id, getCartItemQuantity(product.id), 1)}
                                                        >
                                                            <Plus className="h-3 w-3" />
                                                        </Button>
                                                    </div>
                                                    <div className="flex gap-2">
                                                        <Button
                                                            variant="outline"
                                                            className="flex-1"
                                                            size="sm"
                                                            asChild
                                                        >
                                                            <a href={`/products/${product.id}`}>Detayları Gör</a>
                                                        </Button>
                                                        <Button
                                                            variant="destructive"
                                                            size="sm"
                                                            onClick={() => handleRemoveFromCart(product.id)}
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    </div>
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
                    </div>
                </div>
            )}
        </GuestLayout>
    )
}
