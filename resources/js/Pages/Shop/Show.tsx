import { Head, Link, router } from '@inertiajs/react'
import { useState, useEffect } from 'react'
import { ShoppingCart, Plus, Minus, ArrowLeft, Heart, Trash2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { Input } from '@/components/ui/input'
import GuestLayout from '@/components/layouts/GuestLayout'
import { useFavorites } from '@/hooks/useFavorites'
import { ProductGallery } from '@/components/ProductGallery'
import { usePriceVisibility } from '@/hooks/usePriceVisibility'

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
    const { checkFavorite, toggleFavorite } = useFavorites()
    const isFavorite = checkFavorite(product.id)
    const { isPriceVisible } = usePriceVisibility()

    const formatPrice = (price: number) => {
        return new Intl.NumberFormat('tr-TR', {
            style: 'currency',
            currency: 'TRY',
        }).format(price)
    }

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

    const primaryImage = product.images.find((img) => img.is_primary) || product.images[0]
    const hasDiscount = product.base_price > product.price

    return (
        <GuestLayout cartCount={cartCount}>
            <Head title={product.name} />
            
            <div className="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {/* Back Button */}
                <Button variant="ghost" asChild className="mb-6">
                    <Link href="/shop">
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Ürünlere Dön
                    </Link>
                </Button>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    {/* Product Images */}
                    <div>
                        <ProductGallery
                            images={product.images.map((img) => img.url)}
                            autoPlayInterval={4000}
                        />
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
                        <div className="space-y-2">
                            {isPriceVisible ? (
                                <>
                                    <div className="flex items-baseline gap-3">
                                        <span className="text-4xl font-bold text-primary">
                                            {formatPrice(product.price)}
                                        </span>
                                        {hasDiscount && (
                                            <span className="text-xl text-muted-foreground line-through">
                                                {formatPrice(product.base_price)}
                                            </span>
                                        )}
                                    </div>
                                    {hasDiscount && (
                                        <Badge variant="destructive" className="text-sm">
                                            %{Math.round(((product.base_price - product.price) / product.base_price) * 100)} İndirim
                                        </Badge>
                                    )}
                                </>
                            ) : (
                                <div className="space-y-2">
                                    <p className="text-lg text-muted-foreground">Fiyat için PIN gerekli</p>
                                </div>
                            )}
                        </div>

                        <Separator />

                        {/* Cart Section */}
                        <Card>
                            <CardContent className="p-6">
                                {cartItem ? (
                                    <div className="space-y-4">
                                        <div className="flex items-center justify-between">
                                            <span className="font-semibold">Sepetteki Miktar</span>
                                            <div className="flex items-center gap-2 border rounded-md">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-red-500 hover:text-red-600 hover:bg-red-50"
                                                    onClick={() => handleUpdateQuantity(quantity - 1)}
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
                                                            handleQuantityInputSubmit(true)
                                                        }
                                                    }}
                                                    onBlur={() => handleQuantityInputSubmit(true)}
                                                    onFocus={() => setQuantityInput(quantity.toString())}
                                                    className="w-16 h-8 text-center font-medium px-1 border-0 focus-visible:ring-0 focus-visible:ring-offset-0"
                                                />
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-green-500 hover:text-green-600 hover:bg-green-50"
                                                    onClick={() => handleUpdateQuantity(quantity + 1)}
                                                    disabled={quantity >= 999}
                                                >
                                                    <Plus className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </div>
                                        <div className="flex gap-2">
                                            <Button
                                                variant="destructive"
                                                className="flex-1"
                                                onClick={() => {
                                                    router.delete(`/cart/items/${cartItem.id}`, {
                                                        preserveScroll: true,
                                                    })
                                                }}
                                            >
                                                <Trash2 className="h-4 w-4 mr-2" />
                                                Sepetten Kaldır
                                            </Button>
                                            <Button variant="outline" asChild>
                                                <Link href="/cart">Sepete Git</Link>
                                            </Button>
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
                                        <Button
                                            onClick={handleAddToCart}
                                            className="w-full"
                                            size="lg"
                                            disabled={isAdding}
                                        >
                                            <ShoppingCart className="h-4 w-4 mr-2" />
                                            {isAdding ? 'Sepete Ekleniyor...' : 'Sepete Ekle'}
                                        </Button>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Product Details */}
                        <Card>
                            <CardContent className="p-6">
                                <h2 className="text-xl font-semibold mb-4">Ürün Detayları</h2>
                                <dl className="space-y-2">
                                    <div className="flex justify-between">
                                        <dt className="text-muted-foreground">Ürün Kodu</dt>
                                        <dd className="font-medium">{product.sku}</dd>
                                    </div>
                                    <Separator />
                                    <div className="flex justify-between">
                                        <dt className="text-muted-foreground">Fiyat</dt>
                                        <dd className="font-medium">
                                            {isPriceVisible ? (
                                                formatPrice(product.price)
                                            ) : (
                                                <span className="text-muted-foreground">PIN gerekli</span>
                                            )}
                                        </dd>
                                    </div>
                                </dl>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </GuestLayout>
    )
}

