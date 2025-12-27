import { Head, router, useForm } from '@inertiajs/react'
import { ShoppingBag } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Separator } from '@/components/ui/separator'
import GuestLayout from '@/components/layouts/GuestLayout'
import { usePriceVisibility } from '@/hooks/usePriceVisibility'

interface CartItem {
    id: number
    product_id: number
    quantity: number
    product: {
        id: number
        name: string
        sku: string
        price: number
    }
}

interface Props {
    items: CartItem[]
    subtotal: number
    total: number
    cartCount: number
    commission_rate?: number
    commission_amount?: number
    total_with_commission?: number
}

export default function CheckoutIndex({ 
    items, 
    subtotal, 
    total, 
    cartCount,
    commission_rate,
    commission_amount,
    total_with_commission
}: Props) {
    const { isPriceVisible } = usePriceVisibility()
    const { data, setData, post, processing, errors } = useForm({
        first_name: '',
        last_name: '',
        phone: '',
        city: '',
        district: '',
        address: '',
        note: '',
        payment_method: 'transfer', // Default to transfer
    })

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        
        console.log('Checkout form submit başladı', {
            formData: data,
            itemsCount: items.length,
        })
        
        post('/checkout', {
            preserveScroll: true,
            onSuccess: (page) => {
                console.log('Checkout başarılı', page)
            },
            onError: (errors) => {
                console.error('Checkout hatası', errors)
            },
            onFinish: () => {
                console.log('Checkout işlemi tamamlandı')
            },
        })
    }

    const formatPrice = (price: number) => {
        return new Intl.NumberFormat('tr-TR', {
            style: 'currency',
            currency: 'TRY',
        }).format(price)
    }

    return (
        <GuestLayout cartCount={cartCount}>
            <Head title="Ödeme" />
            
            <div className="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <h1 className="text-3xl font-bold mb-8">Ödeme</h1>

                <form onSubmit={handleSubmit}>
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        {/* Customer Information Form */}
                        <div className="lg:col-span-2">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Müşteri Bilgileri</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div className="space-y-2">
                                            <Label htmlFor="first_name">
                                                Ad <span className="text-destructive">*</span>
                                            </Label>
                                            <Input
                                                id="first_name"
                                                value={data.first_name}
                                                onChange={(e) => setData('first_name', e.target.value)}
                                                required
                                                className={errors.first_name ? 'border-destructive' : ''}
                                            />
                                            {errors.first_name && (
                                                <p className="text-sm text-destructive">{errors.first_name}</p>
                                            )}
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="last_name">
                                                Soyad <span className="text-destructive">*</span>
                                            </Label>
                                            <Input
                                                id="last_name"
                                                value={data.last_name}
                                                onChange={(e) => setData('last_name', e.target.value)}
                                                required
                                                className={errors.last_name ? 'border-destructive' : ''}
                                            />
                                            {errors.last_name && (
                                                <p className="text-sm text-destructive">{errors.last_name}</p>
                                            )}
                                        </div>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="phone">
                                            Telefon <span className="text-destructive">*</span>
                                        </Label>
                                        <Input
                                            id="phone"
                                            type="tel"
                                            value={data.phone}
                                            onChange={(e) => setData('phone', e.target.value)}
                                            required
                                            placeholder="05XX XXX XX XX"
                                            className={errors.phone ? 'border-destructive' : ''}
                                        />
                                        {errors.phone && (
                                            <p className="text-sm text-destructive">{errors.phone}</p>
                                        )}
                                        <p className="text-xs text-muted-foreground">
                                            Mevcut müşteri iseniz telefon numaranızla eşleştirileceksiniz.
                                        </p>
                                    </div>

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div className="space-y-2">
                                            <Label htmlFor="city">
                                                Şehir <span className="text-destructive">*</span>
                                            </Label>
                                            <Input
                                                id="city"
                                                value={data.city}
                                                onChange={(e) => setData('city', e.target.value)}
                                                required
                                                className={errors.city ? 'border-destructive' : ''}
                                            />
                                            {errors.city && (
                                                <p className="text-sm text-destructive">{errors.city}</p>
                                            )}
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="district">
                                                İlçe <span className="text-destructive">*</span>
                                            </Label>
                                            <Input
                                                id="district"
                                                value={data.district}
                                                onChange={(e) => setData('district', e.target.value)}
                                                required
                                                className={errors.district ? 'border-destructive' : ''}
                                            />
                                            {errors.district && (
                                                <p className="text-sm text-destructive">{errors.district}</p>
                                            )}
                                        </div>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="address">
                                            Adres <span className="text-destructive">*</span>
                                        </Label>
                                        <Textarea
                                            id="address"
                                            value={data.address}
                                            onChange={(e) => setData('address', e.target.value)}
                                            required
                                            rows={3}
                                            className={errors.address ? 'border-destructive' : ''}
                                        />
                                        {errors.address && (
                                            <p className="text-sm text-destructive">{errors.address}</p>
                                        )}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="note">Not (Opsiyonel)</Label>
                                        <Textarea
                                            id="note"
                                            value={data.note}
                                            onChange={(e) => setData('note', e.target.value)}
                                            rows={3}
                                            placeholder="Siparişinizle ilgili özel notlarınız..."
                                        />
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Payment Method Selection */}
                            <Card className="mt-6">
                                <CardHeader>
                                    <CardTitle>Ödeme Yöntemi</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="space-y-3">
                                        <div className="flex items-center space-x-3 p-4 border rounded-lg hover:bg-accent cursor-pointer transition-colors">
                                            <input
                                                type="radio"
                                                id="payment_transfer"
                                                name="payment_method"
                                                value="transfer"
                                                checked={data.payment_method === 'transfer'}
                                                onChange={(e) => setData('payment_method', e.target.value)}
                                                className="h-4 w-4 text-primary focus:ring-primary"
                                            />
                                            <Label htmlFor="payment_transfer" className="flex-1 cursor-pointer">
                                                <div>
                                                    <div className="font-medium">Havale/EFT</div>
                                                    <div className="text-sm text-muted-foreground">
                                                        Banka hesabımıza havale veya EFT yaparak ödeme yapabilirsiniz
                                                    </div>
                                                </div>
                                            </Label>
                                        </div>

                                        <div className="flex items-center space-x-3 p-4 border rounded-lg hover:bg-accent cursor-pointer transition-colors">
                                            <input
                                                type="radio"
                                                id="payment_paytr_link"
                                                name="payment_method"
                                                value="paytr_link"
                                                checked={data.payment_method === 'paytr_link'}
                                                onChange={(e) => setData('payment_method', e.target.value)}
                                                className="h-4 w-4 text-primary focus:ring-primary"
                                            />
                                            <Label htmlFor="payment_paytr_link" className="flex-1 cursor-pointer">
                                                <div>
                                                    <div className="font-medium">Link ile Ödeme</div>
                                                    <div className="text-sm text-muted-foreground">
                                                        PayTR güvenli ödeme linki ile kredi kartı veya banka kartı ile ödeme yapabilirsiniz
                                                    </div>
                                                </div>
                                            </Label>
                                        </div>
                                    </div>
                                    {errors.payment_method && (
                                        <p className="text-sm text-destructive">{errors.payment_method}</p>
                                    )}
                                    
                                    {/* Commission Information for PayTR Link */}
                                    {data.payment_method === 'paytr_link' && commission_rate && commission_rate > 0 && (
                                        <div className="mt-4 p-4 bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800 rounded-lg">
                                            <div className="flex items-start space-x-3">
                                                <div className="flex-shrink-0">
                                                    <svg className="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </div>
                                                <div className="flex-1">
                                                    <h4 className="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-1">
                                                        Komisyon Bilgisi
                                                    </h4>
                                                    <p className="text-sm text-blue-800 dark:text-blue-200">
                                                        Link ile ödeme seçildiğinde, toplam tutarınıza{' '}
                                                        <span className="font-semibold">%{commission_rate.toFixed(2)}</span> komisyon eklenir.
                                                        {commission_amount && commission_amount > 0 && (
                                                            <>
                                                                {' '}Komisyon tutarı:{' '}
                                                                <span className="font-semibold">
                                                                    {isPriceVisible ? formatPrice(commission_amount) : 'PIN gerekli'}
                                                                </span>
                                                            </>
                                                        )}
                                                    </p>
                                                    {total_with_commission && total_with_commission > 0 && (
                                                        <p className="text-sm text-blue-700 dark:text-blue-300 mt-2 font-medium">
                                                            Ödenecek Toplam Tutar:{' '}
                                                            <span className="font-bold">
                                                                {isPriceVisible ? formatPrice(total_with_commission) : 'PIN gerekli'}
                                                            </span>
                                                        </p>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </div>

                        {/* Order Summary */}
                        <div className="lg:col-span-1">
                            <Card className="sticky top-24">
                                <CardHeader>
                                    <CardTitle>Sipariş Özeti</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="space-y-2 max-h-64 overflow-y-auto">
                                        {items.map((item) => (
                                            <div key={item.id} className="flex justify-between text-sm">
                                                <div className="flex-1">
                                                    <p className="font-medium">{item.product.name}</p>
                                                    <p className="text-muted-foreground">
                                                        {item.quantity} x{' '}
                                                        {isPriceVisible ? (
                                                            formatPrice(item.product.price)
                                                        ) : (
                                                            <span className="text-muted-foreground">PIN gerekli</span>
                                                        )}
                                                    </p>
                                                </div>
                                                <div className="text-right font-medium">
                                                    {isPriceVisible ? (
                                                        formatPrice(item.product.price * item.quantity)
                                                    ) : (
                                                        <span className="text-muted-foreground">PIN gerekli</span>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                    <Separator />
                                    <div className="flex justify-between text-sm">
                                        <span className="text-muted-foreground">Ara Toplam</span>
                                        <span className="font-medium">
                                            {isPriceVisible ? (
                                                formatPrice(subtotal)
                                            ) : (
                                                <span className="text-muted-foreground">PIN gerekli</span>
                                            )}
                                        </span>
                                    </div>
                                    {data.payment_method === 'paytr_link' && commission_rate && commission_rate > 0 && commission_amount && commission_amount > 0 && (
                                        <>
                                            <div className="flex justify-between text-sm">
                                                <span className="text-muted-foreground">
                                                    Komisyon (%{commission_rate.toFixed(2)})
                                                </span>
                                                <span className="font-medium">
                                                    {isPriceVisible ? (
                                                        formatPrice(commission_amount)
                                                    ) : (
                                                        <span className="text-muted-foreground">PIN gerekli</span>
                                                    )}
                                                </span>
                                            </div>
                                            <Separator />
                                        </>
                                    )}
                                    <div className="flex justify-between text-lg font-bold">
                                        <span>Toplam</span>
                                        <span className="text-primary">
                                            {isPriceVisible ? (
                                                formatPrice(
                                                    data.payment_method === 'paytr_link' && total_with_commission 
                                                        ? total_with_commission 
                                                        : total
                                                )
                                            ) : (
                                                <span className="text-muted-foreground">PIN gerekli</span>
                                            )}
                                        </span>
                                    </div>
                                    <Button type="submit" className="w-full" size="lg" disabled={processing}>
                                        {processing ? 'İşleniyor...' : 'Siparişi Onayla'}
                                    </Button>
                                    <Button variant="outline" asChild className="w-full" type="button">
                                        <a href="/cart">Sepete Dön</a>
                                    </Button>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </form>
            </div>
        </GuestLayout>
    )
}

