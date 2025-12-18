import { Head, router, usePage } from '@inertiajs/react'
import { Search, ShoppingCart, X, Plus, Minus, Heart, Trash2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardFooter } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import GuestLayout from '@/components/layouts/GuestLayout'
import { useForm } from '@inertiajs/react'
import { useFavorites } from '@/hooks/useFavorites'
import { usePriceVisibility } from '@/hooks/usePriceVisibility'
import { useState } from 'react'

interface Product {
    id: number
    name: string
    sku: string
    price: number
    image: string | null
    categories: Array<{ id: number; name: string; slug: string }>
}

interface Category {
    id: number
    name: string
    slug: string
}

interface PaginatedProducts {
    data: Product[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    links: Array<{ url: string | null; label: string; active: boolean }>
}

interface Props {
    products: PaginatedProducts
    categories: Category[]
    filters: {
        category?: string
        search?: string
        sort?: string
        direction?: string
    }
    cartCount: number
}

export default function ShopIndex({ products, categories, filters, cartCount }: Props) {
    const { cartItems } = usePage().props as any
    const [quantityInputs, setQuantityInputs] = useState<Record<number, string>>({})
    const { checkFavorite, toggleFavorite } = useFavorites()
    const { isPriceVisible } = usePriceVisibility()

    const { data, setData, get, processing } = useForm({
        search: filters.search || '',
        category: filters.category || '',
        sort: filters.sort || 'sort_order',
        direction: filters.direction || 'asc',
    })

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault()
        get('/shop', {
            preserveState: true,
            preserveScroll: true,
        })
    }

    const handleAddToCart = (productId: number, quantity: number = 1) => {
        router.post(
            '/cart/add',
            { product_id: productId, quantity },
            {
                preserveScroll: true,
                onSuccess: () => {
                    // Cart count will update via Inertia
                },
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

    const formatPrice = (price: number) => {
        return new Intl.NumberFormat('tr-TR', {
            style: 'currency',
            currency: 'TRY',
        }).format(price)
    }

    return (
        <GuestLayout cartCount={cartCount}>
            <Head title="Ürünler" />

            <div className="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {/* Header */}
                <div className="mb-8">
                    <h1 className="text-3xl font-bold mb-4">Ürünler</h1>

                    {/* Search and Filters */}
                    <div className="flex flex-col md:flex-row gap-4">
                        <form onSubmit={handleSearch} className="flex-1 flex gap-2">
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input
                                    type="text"
                                    placeholder="Ürün ara..."
                                    value={data.search}
                                    onChange={(e) => setData('search', e.target.value)}
                                    className="pl-10"
                                />
                            </div>
                            <Button type="submit" disabled={processing}>
                                Ara
                            </Button>
                        </form>

                        <div className="flex gap-2">
                            {/* Category Filter */}
                            <Select
                                value={data.category || 'all'}
                                onValueChange={(value) => {
                                    const categoryValue = value === 'all' ? '' : value
                                    setData('category', categoryValue)
                                    // Anında arama yap
                                    router.get('/shop', {
                                        category: categoryValue || undefined,
                                        search: data.search || undefined,
                                        sort: data.sort,
                                        direction: data.direction,
                                    }, {
                                        preserveState: true,
                                        preserveScroll: true,
                                    })
                                }}
                            >
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder="Tüm Kategoriler" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Tüm Kategoriler</SelectItem>
                                    {categories.map((category) => (
                                        <SelectItem key={category.id} value={category.slug}>
                                            {category.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            {/* Sort */}
                            <Select
                                value={`${data.sort}-${data.direction}`}
                                onValueChange={(value) => {
                                    const [sort, direction] = value.split('-')
                                    setData({ sort, direction })
                                    get('/shop', {
                                        preserveState: true,
                                        preserveScroll: true,
                                    })
                                }}
                            >
                                <SelectTrigger className="w-[200px]">
                                    <SelectValue placeholder="Sıralama" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="sort_order-asc">Sıralama: Varsayılan</SelectItem>
                                    <SelectItem value="name-asc">İsim: A-Z</SelectItem>
                                    <SelectItem value="name-desc">İsim: Z-A</SelectItem>
                                    <SelectItem value="price-asc">Fiyat: Düşük-Yüksek</SelectItem>
                                    <SelectItem value="price-desc">Fiyat: Yüksek-Düşük</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    {/* Active Filters */}
                    {(filters.category || filters.search) && (
                        <div className="mt-4 flex flex-wrap gap-2">
                            {filters.search && (
                                <Badge variant="secondary" className="gap-2">
                                    Arama: {filters.search}
                                    <button
                                        onClick={() => {
                                            setData('search', '')
                                            get('/shop', {
                                                preserveState: true,
                                                preserveScroll: true,
                                            })
                                        }}
                                        className="ml-1 hover:text-destructive"
                                    >
                                        <X className="h-3 w-3" />
                                    </button>
                                </Badge>
                            )}
                            {filters.category && (
                                <Badge variant="secondary" className="gap-2">
                                    Kategori: {categories.find((c) => c.slug === filters.category)?.name}
                                    <button
                                        onClick={() => {
                                            setData('category', '')
                                            get('/shop', {
                                                preserveState: true,
                                                preserveScroll: true,
                                            })
                                        }}
                                        className="ml-1 hover:text-destructive"
                                    >
                                        <X className="h-3 w-3" />
                                    </button>
                                </Badge>
                            )}
                        </div>
                    )}
                </div>

                {/* Products Grid */}
                {products.data.length === 0 ? (
                    <div className="text-center py-12">
                        <p className="text-muted-foreground text-lg">Ürün bulunamadı.</p>
                    </div>
                ) : (
                    <>
                        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-4 mb-8">
                            {products.data.map((product) => {
                                const isFavorite = checkFavorite(product.id)
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
                                                                className="absolute inset-0 w-full h-full object-contain opacity-10 pointer-events-none"
                                                            />
                                                        </>
                                                    ) : (
                                                        <div className="w-full h-full flex items-center justify-center text-muted-foreground">
                                                            <ShoppingCart className="h-12 w-12" />
                                                        </div>
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
                                            <div className="flex items-center justify-between">
                                                {isPriceVisible ? (
                                                    <span className="text-base font-bold text-primary">
                                                        {formatPrice(product.price)}
                                                    </span>
                                                ) : (
                                                    <span className="text-xs text-muted-foreground">
                                                        Fiyat için PIN gerekli
                                                    </span>
                                                )}
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

                        {/* Pagination */}
                        {products.last_page > 1 && (
                            <div className="flex justify-center gap-1 sm:gap-2 flex-wrap">
                                {products.links
                                    .filter((link, index) => {
                                        // Mobil için daha az link göster
                                        const isFirstOrLast = index === 0 || index === products.links.length - 1
                                        const isAroundCurrent = Math.abs(index - products.links.findIndex(l => l.active)) <= 1
                                        const isEdgePage = index === 1 || index === products.links.length - 2

                                        return isFirstOrLast || isAroundCurrent || isEdgePage
                                    })
                                    .map((link, index, filteredLinks) => {
                                        // Elipsis ekle
                                        const prevIndex = index > 0 ? products.links.indexOf(filteredLinks[index - 1]) : -1
                                        const currentIndex = products.links.indexOf(link)
                                        const showEllipsis = prevIndex !== -1 && currentIndex - prevIndex > 1

                                        return (
                                            <div key={index} className="flex items-center gap-1 sm:gap-2">
                                                {showEllipsis && (
                                                    <span className="px-2 text-muted-foreground hidden sm:inline">...</span>
                                                )}
                                                <Button
                                                    variant={link.active ? 'default' : 'outline'}
                                                    size="sm"
                                                    disabled={!link.url || processing}
                                                    onClick={() => {
                                                        if (link.url) {
                                                            router.visit(link.url, {
                                                                preserveState: true,
                                                                preserveScroll: false,
                                                                onSuccess: () => {
                                                                    window.scrollTo({ top: 0, behavior: 'smooth' })
                                                                },
                                                            })
                                                        }
                                                    }}
                                                    className="min-w-[40px]"
                                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                                />
                                            </div>
                                        )
                                    })}
                            </div>
                        )}
                    </>
                )}
            </div>
        </GuestLayout>
    )
}

