import { Head, router } from '@inertiajs/react'
import { Trash2, Plus, Minus, ShoppingBag } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Separator } from '@/components/ui/separator'
import { Input } from '@/components/ui/input'
import GuestLayout from '@/components/layouts/GuestLayout'
import { usePriceVisibility } from '@/hooks/usePriceVisibility'
import { useState } from 'react'

interface CartItem {
    id: number
    product_id: number
    quantity: number
    product: {
        id: number
        name: string
        sku: string
        price: number
        retail_price?: number
        image: string | null
    }
}

interface Props {
    items: CartItem[]
    subtotal: number
    total: number
    cartCount: number
}

export default function CartIndex({ items, subtotal, total, cartCount }: Props) {
    const { isPriceVisible } = usePriceVisibility()
    const [quantityInputs, setQuantityInputs] = useState<Record<number, string>>({})

    const formatPrice = (price: number) => {
        return new Intl.NumberFormat('tr-TR', {
            style: 'currency',
            currency: 'TRY',
        }).format(price)
    }

    // Calculate subtotal and total based on price visibility
    const displaySubtotal = isPriceVisible 
        ? subtotal 
        : items.reduce((sum, item) => sum + (item.product.retail_price ?? item.product.price) * item.quantity, 0)
    
    const displayTotal = isPriceVisible 
        ? total 
        : displaySubtotal

    const handleUpdateQuantity = (itemId: number, quantity: number) => {
        if (quantity < 1) {
            handleRemove(itemId)
            return
        }

        router.put(
            `/cart/items/${itemId}`,
            { quantity },
            {
                preserveScroll: true,
            }
        )
    }

    const handleRemove = (itemId: number) => {
        router.delete(`/cart/items/${itemId}`, {
            preserveScroll: true,
        })
    }

    const handleQuantityInputChange = (itemId: number, value: string) => {
        setQuantityInputs(prev => ({
            ...prev,
            [itemId]: value
        }))
    }

    const handleQuantityInputSubmit = (itemId: number, currentQuantity: number) => {
        const inputValue = quantityInputs[itemId]
        if (inputValue === undefined) return

        const newQuantity = Math.max(1, Math.min(999, parseInt(inputValue) || 1))
        
        setQuantityInputs(prev => {
            const updated = { ...prev }
            delete updated[itemId]
            return updated
        })

        if (newQuantity === currentQuantity) return

        if (newQuantity < 1) {
            handleRemove(itemId)
            return
        }

        router.put(
            `/cart/items/${itemId}`,
            { quantity: newQuantity },
            {
                preserveScroll: true,
            }
        )
    }

    const handleCheckout = () => {
        router.visit('/checkout')
    }

    return (
        <GuestLayout cartCount={cartCount}>
            <Head title="Sepetim" />

            <div className="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <h1 className="text-3xl font-bold mb-8">Sepetim</h1>

                {items.length === 0 ? (
                    <Card className="text-center py-12">
                        <CardContent>
                            <ShoppingBag className="h-16 w-16 mx-auto text-muted-foreground mb-4" />
                            <p className="text-lg text-muted-foreground mb-4">Sepetiniz boş</p>
                            <Button asChild>
                                <a href="/shop">Alışverişe Başla</a>
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        {/* Cart Items */}
                        <div className="lg:col-span-2 space-y-4">
                            {items.map((item) => (
                                <Card key={item.id}>
                                    <CardContent className="p-6">
                                        <div className="flex gap-4">
                                            {/* Product Image */}
                                            <div className="w-24 h-24 bg-white rounded-lg overflow-hidden shrink-0 relative">
                                                {item.product.image ? (
                                                    <>
                                                        <img
                                                            src={item.product.image}
                                                            alt={item.product.name}
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
                                                        <ShoppingBag className="h-8 w-8" />
                                                    </div>
                                                )}
                                            </div>

                                            {/* Product Info */}
                                            <div className="flex-1">
                                                <h3 className="font-semibold text-lg mb-1">{item.product.name}</h3>
                                                <p className="text-sm text-muted-foreground mb-2">SKU: {item.product.sku}</p>
                                                <p className="text-lg font-bold text-primary mb-4">
                                                    {isPriceVisible 
                                                        ? formatPrice(item.product.price)
                                                        : formatPrice(item.product.retail_price ?? item.product.price)}
                                                </p>

                                                {/* Quantity Controls */}
                                                <div className="flex items-center gap-4">
                                                    <div className="flex items-center gap-2 border rounded-md">
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="h-8 w-8 text-red-500 hover:text-red-600 hover:bg-red-50"
                                                            onClick={() => handleUpdateQuantity(item.id, item.quantity - 1)}
                                                        >
                                                            <Minus className="h-4 w-4" />
                                                        </Button>
                                                        <Input
                                                            type="number"
                                                            min="1"
                                                            max="999"
                                                            value={quantityInputs[item.id] ?? item.quantity}
                                                            onChange={(e) => handleQuantityInputChange(item.id, e.target.value)}
                                                            onKeyDown={(e) => {
                                                                if (e.key === 'Enter') {
                                                                    e.currentTarget.blur()
                                                                    handleQuantityInputSubmit(item.id, item.quantity)
                                                                }
                                                            }}
                                                            onBlur={() => handleQuantityInputSubmit(item.id, item.quantity)}
                                                            className="w-16 h-8 text-center font-medium px-1 border-0 focus-visible:ring-0 focus-visible:ring-offset-0"
                                                        />
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="h-8 w-8 text-green-500 hover:text-green-600 hover:bg-green-50"
                                                            onClick={() => handleUpdateQuantity(item.id, item.quantity + 1)}
                                                        >
                                                            <Plus className="h-4 w-4" />
                                                        </Button>
                                                    </div>

                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() => handleRemove(item.id)}
                                                        className="text-destructive hover:text-destructive"
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </div>

                                            {/* Item Total */}
                                            <div className="text-right">
                                                <p className="text-lg font-bold">
                                                    {isPriceVisible 
                                                        ? formatPrice(item.product.price * item.quantity)
                                                        : formatPrice((item.product.retail_price ?? item.product.price) * item.quantity)}
                                                </p>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>

                        {/* Order Summary */}
                        <div className="lg:col-span-1">
                            <Card className="sticky top-24">
                                <CardHeader>
                                    <CardTitle>Sipariş Özeti</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="flex justify-between text-sm">
                                        <span className="text-muted-foreground">Ara Toplam</span>
                                        <span className="font-medium">
                                            {formatPrice(displaySubtotal)}
                                        </span>
                                    </div>
                                    <Separator />
                                    <div className="flex justify-between text-lg font-bold">
                                        <span>Toplam</span>
                                        <span className="text-primary">
                                            {formatPrice(displayTotal)}
                                        </span>
                                    </div>
                                    <Button onClick={handleCheckout} className="w-full" size="lg">
                                        Siparişi Tamamla
                                    </Button>
                                    <Button variant="outline" asChild className="w-full">
                                        <a href="/shop">Alışverişe Devam Et</a>
                                    </Button>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                )}
            </div>
        </GuestLayout>
    )
}

