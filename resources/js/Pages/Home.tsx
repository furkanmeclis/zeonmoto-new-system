import { Head, Link, router, usePage } from '@inertiajs/react'
import { Package, Truck, ArrowRight } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import GuestLayout from '@/components/layouts/GuestLayout'
import { useState } from 'react'
import HeroSection from '@/components/Shop/HeroSection'
import ProductCard from '@/components/Shop/ProductCard'

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
    products: Product[]
    cartCount: number
}

export default function Home({ products, cartCount }: Props) {
    const { cartItems } = usePage().props as any
    const [quantityInputs, setQuantityInputs] = useState<Record<number, string>>({})

    const socialLinks = {
        whatsapp: 'https://wa.me/905070004777',
        instagram: 'https://www.instagram.com/zeonmotomarket/',
        tiktok: 'https://www.tiktok.com/@zeonmotoryedekparca',
        phone: '0(507) 000 4777'
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

    return (
        <GuestLayout cartCount={cartCount}>
            <Head title="Ana Sayfa" />

            {/* Hero Section */}
            <HeroSection socialLinks={socialLinks} />

            {/* Features Section */}
            <div className="hidden container mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">

                    {/* 50.000 TL üzeri Kargo Bize Ait */}
                    <Card className="border-2 hover:border-yellow-500/50 transition-colors flex flex-col justify-center">
                        <CardContent className="p-6 text-center flex flex-col items-center">
                            <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-yellow-100 mb-4">
                                <Truck className="h-8 w-8 text-yellow-600" />
                            </div>
                            <h3 className="text-xl font-semibold mb-2">50.000 TL Üzeri Kargo Bize Ait</h3>
                            <p className="text-muted-foreground">
                                50.000 TL ve üzeri alışverişlerde kargo ücreti tamamen bize ait!
                            </p>
                        </CardContent>
                    </Card>

                    {/* Kredi Kartı ile Güvenli Alışveriş + 3 Taksit */}
                    <Card className="border-2 hover:border-yellow-500/50 transition-colors flex flex-col justify-center">
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
                    <Card className="border-2 hover:border-yellow-500/50 transition-colors flex flex-col justify-center">
                        <CardContent className="p-6 text-center flex flex-col items-center">
                            <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-yellow-100 mb-4">
                                <Package className="h-8 w-8 text-yellow-600" />
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
                            <Button asChild variant="outline" className="bg-yellow-50 border-yellow-500 text-yellow-700 hover:bg-yellow-100">
                                <Link href="/shop">
                                    Tümünü Gör
                                    <ArrowRight className="ml-2 h-4 w-4" />
                                </Link>
                            </Button>
                        </div>

                        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 gap-6">
                            {products.map((product) => {
                                const cartItem = cartItems?.[product.id]
                                const cartItemQuantity = cartItem?.quantity || 0
                                const cartItemId = cartItem?.id || null

                                return (
                                    <ProductCard
                                        key={product.id}
                                        product={{
                                            ...product,
                                            images: product.image ? [product.image] : [],
                                            is_discount: product.base_price > product.price ? 1 : 0
                                        }}
                                        cartItemQuantity={cartItemQuantity}
                                        cartItemId={cartItemId}
                                        onAddToCart={handleAddToCart}
                                        onUpdateQuantity={(cartItemId, quantity) => {
                                            router.put(`/cart/items/${cartItemId}`, { quantity }, { preserveScroll: true })
                                        }}
                                        onRemoveFromCart={handleRemoveFromCart}
                                        onQuantityInputChange={handleQuantityInputChange}
                                        onQuantityInputSubmit={handleQuantityInputSubmit}
                                        quantityInput={quantityInputs[product.id]}
                                    />
                                )
                            })}
                        </div>
                    </div>
                </div>
            )}
        </GuestLayout>
    )
}
