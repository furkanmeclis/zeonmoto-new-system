import { Head, Link } from '@inertiajs/react'
import { CheckCircle, Package } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Separator } from '@/components/ui/separator'
import GuestLayout from '@/components/layouts/GuestLayout'
import { usePriceVisibility } from '@/hooks/usePriceVisibility'

interface OrderItem {
    product_name: string
    quantity: number
    unit_price: number
    total_price: number
}

interface Order {
    id: number
    order_no: string
    status: string
    total: number
    currency: string
    created_at: string
    customer: {
        full_name: string
        phone: string
        address: string
        city: string
        district: string
    }
    items: OrderItem[]
}

interface Props {
    order: Order
    cartCount: number
}

export default function CheckoutSuccess({ order, cartCount }: Props) {
    const { isPriceVisible } = usePriceVisibility()

    const formatPrice = (price: number) => {
        return new Intl.NumberFormat('tr-TR', {
            style: 'currency',
            currency: order.currency,
        }).format(price)
    }

    return (
        <GuestLayout cartCount={cartCount}>
            <Head title="Sipariş Başarılı" />
            
            <div className="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div className="max-w-3xl mx-auto">
                    {/* Success Message */}
                    <Card className="mb-8 border-green-500">
                        <CardContent className="p-8 text-center">
                            <CheckCircle className="h-16 w-16 text-green-500 mx-auto mb-4" />
                            <h1 className="text-3xl font-bold mb-2">Siparişiniz Alındı!</h1>
                            <p className="text-muted-foreground">
                                Sipariş numaranız: <span className="font-bold text-primary">{order.order_no}</span>
                            </p>
                        </CardContent>
                    </Card>

                    {/* Order Details */}
                    <Card className="mb-8">
                        <CardHeader>
                            <CardTitle>Sipariş Detayları</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            {/* Order Info */}
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <p className="text-sm text-muted-foreground">Sipariş No</p>
                                    <p className="font-semibold">{order.order_no}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Tarih</p>
                                    <p className="font-semibold">{order.created_at}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Durum</p>
                                    <p className="font-semibold capitalize">{order.status}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Toplam</p>
                                    <p className="font-semibold text-primary">
                                        {isPriceVisible ? (
                                            formatPrice(order.total)
                                        ) : (
                                            <span className="text-muted-foreground">PIN gerekli</span>
                                        )}
                                    </p>
                                </div>
                            </div>

                            <Separator />

                            {/* Customer Info */}
                            <div>
                                <h3 className="font-semibold mb-3">Müşteri Bilgileri</h3>
                                <div className="space-y-1 text-sm">
                                    <p>
                                        <span className="text-muted-foreground">Ad Soyad:</span>{' '}
                                        <span className="font-medium">{order.customer.full_name}</span>
                                    </p>
                                    <p>
                                        <span className="text-muted-foreground">Telefon:</span>{' '}
                                        <span className="font-medium">{order.customer.phone}</span>
                                    </p>
                                    <p>
                                        <span className="text-muted-foreground">Adres:</span>{' '}
                                        <span className="font-medium">
                                            {order.customer.address}, {order.customer.district}, {order.customer.city}
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <Separator />

                            {/* Order Items */}
                            <div>
                                <h3 className="font-semibold mb-3">Sipariş İçeriği</h3>
                                <div className="space-y-3">
                                    {order.items.map((item, index) => (
                                        <div key={index} className="flex justify-between items-start">
                                            <div className="flex-1">
                                                <p className="font-medium">{item.product_name}</p>
                                                <p className="text-sm text-muted-foreground">
                                                    {item.quantity} adet x{' '}
                                                    {isPriceVisible ? (
                                                        formatPrice(item.unit_price)
                                                    ) : (
                                                        <span className="text-muted-foreground">PIN gerekli</span>
                                                    )}
                                                </p>
                                            </div>
                                            <div className="text-right font-semibold">
                                                {isPriceVisible ? (
                                                    formatPrice(item.total_price)
                                                ) : (
                                                    <span className="text-muted-foreground">PIN gerekli</span>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <Separator />

                            {/* Total */}
                            <div className="flex justify-between text-lg font-bold">
                                <span>Toplam</span>
                                <span className="text-primary">
                                    {isPriceVisible ? (
                                        formatPrice(order.total)
                                    ) : (
                                        <span className="text-muted-foreground">PIN gerekli</span>
                                    )}
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Actions */}
                    <div className="flex gap-4 justify-center">
                        <Button asChild>
                            <Link href="/shop">Yeni Sipariş</Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href="/">Ana Sayfa</Link>
                        </Button>
                    </div>
                </div>
            </div>
        </GuestLayout>
    )
}

