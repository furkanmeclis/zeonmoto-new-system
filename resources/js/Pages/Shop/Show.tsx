import { Head, Link, router } from '@inertiajs/react'
import { useState, useEffect } from 'react'
import { motion } from 'framer-motion'
import { ShoppingCart, Plus, Minus, ArrowLeft, Heart, Trash2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { Input } from '@/components/ui/input'
import GuestLayout from '@/components/layouts/GuestLayout'
import { useFavorites } from '@/hooks/useFavorites'
import { usePriceVisibility } from '@/hooks/usePriceVisibility'
import { formatCurrency } from '@/lib/utils'
import { Swiper, SwiperSlide } from 'swiper/react'
import { Navigation, Pagination, Thumbs } from 'swiper/modules'
import type { Swiper as SwiperType } from 'swiper'
import 'swiper/css'
import 'swiper/css/navigation'
import 'swiper/css/pagination'
import 'swiper/css/thumbs'

interface ProductImage {
    id: number
    url: string
    is_primary: boolean
}

interface Category {
    id: number
    name: string
    slug: string
}

interface Product {
    id: number
    name: string
    sku: string
    price: number
    base_price: number
    images: ProductImage[]
    categories: Category[]
}

interface CartItem {
    id: number
    quantity: number
}

interface Props {
    product: Product
    cartItem: CartItem | null
    cartCount: number
}

export default function ShopShow({ product, cartItem, cartCount }: Props) {
    const [quantity, setQuantity] = useState(cartItem?.quantity || 1)
    const [quantityInput, setQuantityInput] = useState<string>('')
    const [isAdding, setIsAdding] = useState(false)
    const [thumbsSwiper, setThumbsSwiper] = useState<SwiperType | null>(null)
    const { checkFavorite, toggleFavorite } = useFavorites()
    const isFavorite = checkFavorite(product.id)
    const { isPriceVisible } = usePriceVisibility()

    const images = product.images.length > 0 
        ? product.images.map((img) => img.url) 
        : ['/logo.png']

    const handleAddToCart = () => {
        setIsAdding(true)
        router.post(
            '/cart/add',
            { product_id: product.id, quantity },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setIsAdding(false)
                },
                onError: () => {
                    setIsAdding(false)
                },
            }
        )
    }

    const handleUpdateQuantity = (newQuantity: number) => {
        if (newQuantity < 1) return
        if (newQuantity > 999) return

        setQuantity(newQuantity)

        if (cartItem) {
            router.put(
                `/cart/items/${cartItem.id}`,
                { quantity: newQuantity },
                {
                    preserveScroll: true,
                }
            )
        }
    }

    const handleQuantityInputSubmit = (isCartItem: boolean) => {
        if (quantityInput === '') {
            setQuantityInput('')
            return
        }

        const newQuantity = Math.max(1, Math.min(999, parseInt(quantityInput) || 1))
        setQuantityInput('')
        setQuantity(newQuantity)

        if (isCartItem && cartItem) {
            router.put(
                `/cart/items/${cartItem.id}`,
                { quantity: newQuantity },
                {
                    preserveScroll: true,
                }
            )
        }
    }

    useEffect(() => {
        if (cartItem) {
            setQuantity(cartItem.quantity)
        }
    }, [cartItem])

    const hasDiscount = product.base_price > product.price

    return (
        <GuestLayout cartCount={cartCount}>
            <Head title={product.name} />
            
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {/* Back Button */}
                <Button variant="ghost" asChild className="mb-6">
                    <Link href="/shop">
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Ürünlere Dön
                    </Link>
                </Button>

                <div className="bg-white rounded-lg shadow-lg p-6 space-y-8">
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        {/* Product Images */}
                        <div className="space-y-4">
                            <Swiper
                                modules={[Navigation, Pagination, Thumbs]}
                                navigation
                                pagination={{ clickable: true }}
                                thumbs={{ swiper: thumbsSwiper }}
                                className="aspect-square rounded-lg overflow-hidden shadow-lg"
                            >
                                {images.map((image, index) => (
                                    <SwiperSlide key={index}>
                                        <img
                                            src={image}
                                            alt={`${product.name} - ${index + 1}`}
                                            className="w-full h-full object-contain"
                                        />
                                    </SwiperSlide>
                                ))}
                            </Swiper>

                            {images.length > 1 && (
                                <Swiper
                                    onSwiper={setThumbsSwiper}
                                    spaceBetween={10}
                                    slidesPerView={4}
                                    modules={[Navigation, Thumbs]}
                                    className="thumbs-swiper"
                                    watchSlidesProgress
                                >
                                    {images.map((image, index) => (
                                        <SwiperSlide key={index}>
                                            <motion.div
                                                whileHover={{ scale: 1.05 }}
                                                className="aspect-square rounded-lg overflow-hidden cursor-pointer"
                                            >
                                                <img
                                                    src={image}
                                                    className="w-full h-full object-contain"
                                                    alt={`${product.name} - Thumbnail ${index + 1}`}
                                                />
                                            </motion.div>
                                        </SwiperSlide>
                                    ))}
                                </Swiper>
                            )}
                        </div>

                        {/* Product Info */}
                        <div className="space-y-6">
                        {/* Categories */}
                        {product.categories.length > 0 && (
                            <div className="flex flex-wrap gap-2">
                                {product.categories.map((category) => (
                                    <Badge key={category.id} variant="secondary" asChild>
                                        <Link href={`/shop?category=${category.slug}`}>
                                            {category.name}
                                        </Link>
                                    </Badge>
                                ))}
                            </div>
                        )}

                        {/* Product Name and Favorite */}
                        <div className="flex items-start justify-between gap-4">
                            <div className="flex-1">
                                <h1 className="text-4xl font-bold">{product.name}</h1>
                                <p className="text-muted-foreground mt-2">SKU: {product.sku}</p>
                            </div>
                            <Button
                                variant="ghost"
                                size="icon"
                                onClick={() => toggleFavorite(product.id)}
                                className={isFavorite ? 'text-destructive' : ''}
                            >
                                <Heart className={`h-6 w-6 ${isFavorite ? 'fill-current' : ''}`} />
                            </Button>
                        </div>

                        <Separator />

                        {/* Price */}
                        <div className="flex items-center space-x-4">
                            <span className="text-4xl font-bold text-yellow-500">
                                {isPriceVisible ? formatCurrency(product.price) : '***'} ₺
                            </span>
                            {hasDiscount && isPriceVisible && (
                                <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                    İndirimli Ürün
                                </span>
                            )}
                            {product.is_new === 1 && (
                                <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                    Yeni Ürün
                                </span>
                            )}
                        </div>

                        <Separator />

                        {/* Cart Section */}
                        <Card>
                            <CardContent className="p-6">
                                {cartItem ? (
                                    <div className="space-y-4">
                                        <div className="flex items-center justify-between p-4 bg-green-50 border border-green-200 rounded-lg">
                                            <div className="flex items-center space-x-2 text-green-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                                </svg>
                                                <span className="font-medium">Bu ürün sepetinizde</span>
                                            </div>
                                            <div className="flex items-center space-x-4">
                                                <div className="flex items-center space-x-2">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-8 w-8 text-gray-600 hover:bg-green-100 rounded-lg transition-colors"
                                                        onClick={() => handleUpdateQuantity(quantity - 1)}
                                                        disabled={quantity <= 1}
                                                    >
                                                        <Minus className="h-4 w-4" />
                                                    </Button>
                                                    <span className="w-8 text-center font-medium">{quantity}</span>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-8 w-8 text-gray-600 hover:bg-green-100 rounded-lg transition-colors"
                                                        onClick={() => handleUpdateQuantity(quantity + 1)}
                                                        disabled={quantity >= 999}
                                                    >
                                                        <Plus className="h-4 w-4" />
                                                    </Button>
                                                </div>
                                                <Button
                                                    variant="ghost"
                                                    className="text-red-600 hover:text-red-700 font-medium px-3 py-1 rounded-lg hover:bg-red-50 transition-colors"
                                                    onClick={() => {
                                                        router.delete(`/cart/items/${cartItem.id}`, {
                                                            preserveScroll: true,
                                                        })
                                                    }}
                                                >
                                                    Sepetten Kaldır
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="space-y-4">
                                        <div className="flex items-center justify-between">
                                            <span className="font-semibold">Miktar</span>
                                            <div className="flex items-center gap-2 border rounded-md">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-red-500 hover:text-red-600 hover:bg-red-50"
                                                    onClick={() => setQuantity(Math.max(1, quantity - 1))}
                                                    disabled={quantity <= 1}
                                                >
                                                    <Minus className="h-4 w-4" />
                                                </Button>
                                                <Input
                                                    type="number"
                                                    min="1"
                                                    max="999"
                                                    value={quantityInput !== '' ? quantityInput : quantity}
                                                    onChange={(e) => setQuantityInput(e.target.value)}
                                                    onKeyDown={(e) => {
                                                        if (e.key === 'Enter') {
                                                            e.currentTarget.blur()
                                                            handleQuantityInputSubmit(false)
                                                        }
                                                    }}
                                                    onBlur={() => handleQuantityInputSubmit(false)}
                                                    onFocus={() => setQuantityInput(quantity.toString())}
                                                    className="w-16 h-8 text-center font-medium px-1 border-0 focus-visible:ring-0 focus-visible:ring-offset-0"
                                                />
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-green-500 hover:text-green-600 hover:bg-green-50"
                                                    onClick={() => setQuantity(Math.min(999, quantity + 1))}
                                                    disabled={quantity >= 999}
                                                >
                                                    <Plus className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </div>
                                        <motion.button
                                            whileTap={{ scale: 0.95 }}
                                            onClick={handleAddToCart}
                                            disabled={isAdding}
                                            className="w-full bg-gradient-to-r from-yellow-500 to-yellow-600 text-white py-4 px-6 rounded-lg text-lg font-medium hover:from-yellow-600 hover:to-yellow-700 transition-all disabled:opacity-50"
                                        >
                                            {isAdding ? (
                                                <div className="flex items-center justify-center space-x-2">
                                                    <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
                                                    <span>Sepete Ekleniyor...</span>
                                                </div>
                                            ) : (
                                                <>
                                                    <ShoppingCart className="h-4 w-4 mr-2 inline" />
                                                    Sepete Ekle
                                                </>
                                            )}
                                        </motion.button>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Product Description */}
                        {(product as any).description && (
                            <div className="prose prose-yellow max-w-none">
                                <h2 className="text-xl font-semibold mb-4">Ürün Açıklaması</h2>
                                <div dangerouslySetInnerHTML={{ __html: (product as any).description }} />
                            </div>
                        )}
                        </div>
                    </div>
                </div>
            </div>
        </GuestLayout>
    )
}

