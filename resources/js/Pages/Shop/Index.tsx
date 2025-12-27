import { Head, router, usePage } from '@inertiajs/react'
import { motion } from 'framer-motion'
import GuestLayout from '@/components/layouts/GuestLayout'
import { useState } from 'react'
import HeroSection from '@/components/Shop/HeroSection'
import FilterSection from '@/components/Shop/FilterSection'
import ProductCard from '@/components/Shop/ProductCard'
import Pagination from '@/components/Shop/Pagination'
import { appendHead } from '@/lib/utils'

interface Product {
    id: number
    name: string
    sku: string
    price: number
    base_price?: number
    image: string | null
    images?: string[]
    categories: Array<{ id: number; name: string; slug: string }>
    is_new?: number
    is_discount?: number
}

interface Category {
    id: number
    name: string
    slug: string
    products_count?: number
}

interface PaginatedProducts {
    data: Product[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number
    to: number
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

    const socialLinks = {
        whatsapp: 'https://wa.me/905070004777',
        instagram: 'https://www.instagram.com/zeonmotomarket/',
        tiktok: 'https://www.tiktok.com/@zeonmotoryedekparca',
        phone: '0(507) 000 4777'
    }

    const metaData = {
        description: `Zeon Moto'da motosikletiniz için en kaliteli yedek parçaları ve aksesuarları bulun. Geniş ürün yelpazemiz ve uygun fiyatlarımızla hizmetinizdeyiz.`,
        keywords: `motosiklet yedek parça, motosiklet aksesuarları, zeon moto, motosiklet ekipmanları, motor parçaları`,
        ogTitle: `Zeon Moto | Motosiklet Yedek Parça ve Aksesuarları`,
        ogDescription: `Zeon Moto'da motosikletiniz için en kaliteli yedek parçaları ve aksesuarları bulun. Geniş ürün yelpazemiz ve uygun fiyatlarımızla hizmetinizdeyiz.`,
        ogImage: '/images/banner.png',
        ogUrl: typeof window !== 'undefined' ? window.location.href : '',
        canonicalUrl: typeof window !== 'undefined' ? window.location.href : ''
    }

    if (typeof window !== 'undefined') {
        appendHead(metaData)
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

    // Calculate price range from products
    const priceRange = {
        min: 0,
        max: products.data.length > 0
            ? Math.max(...products.data.map(p => p.price))
            : 9999
    }

    return (
        <GuestLayout cartCount={cartCount}>
            <Head title='Anasayfa' />

            {/* Hero Section */}
            <HeroSection socialLinks={socialLinks} />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div className="flex flex-col lg:flex-row gap-8">
                    {/* Sol Sidebar - Filtreler */}
                    <div className="w-full lg:w-1/4">
                        <FilterSection
                            categories={categories}
                            priceRange={priceRange}
                            filters={filters}
                        />
                    </div>

                    {/* Sağ Taraf - Ürünler */}
                    <div className="w-full lg:w-3/4">
                        <motion.div
                            variants={container}
                            initial="hidden"
                            animate="show"
                            className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 gap-6"
                        >
                            {products.data.map((product) => {
                                const cartItem = cartItems?.[product.id]
                                const cartItemQuantity = cartItem?.quantity || 0
                                const cartItemId = cartItem?.id || null

                                return (
                                    <motion.div key={product.id} variants={item}>
                                        <ProductCard
                                            product={{
                                                ...product,
                                                images: product.image ? [product.image] : [],
                                                is_discount: product.base_price && product.base_price > product.price ? 1 : 0
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

                        {/* Sayfalama */}
                        <div className="mt-8">
                            <Pagination
                                meta={{
                                    current_page: products.current_page,
                                    last_page: products.last_page,
                                    total: products.total,
                                    per_page: products.per_page,
                                    from: products.from,
                                    to: products.to
                                }}
                                links={products.links}
                            />
                        </div>
                    </div>
                </div>
            </div>
        </GuestLayout>
    )
}
