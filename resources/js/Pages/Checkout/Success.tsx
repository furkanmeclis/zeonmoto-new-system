import { Head, Link } from '@inertiajs/react'
import { CheckCircle, Package, ExternalLink, Copy } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Separator } from '@/components/ui/separator'
import GuestLayout from '@/components/layouts/GuestLayout'
import { useState } from 'react'

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
    subtotal: number
    shipping_cost: number
    shipping_is_free: boolean
    total: number
    currency: string
    payment_method?: string
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

interface PaymentInfo {
    type: 'transfer' | 'paytr_link'
    bank_account?: {
        name: string
        bank: string
        iban: string
        branch: string
    }
    payment_link?: string | null
    payment_link_id?: string | null
}

interface Props {
    order: Order
    payment_info?: PaymentInfo | null
    cartCount: number
}

export default function CheckoutSuccess({ order, payment_info, cartCount }: Props) {
    const [copied, setCopied] = useState(false)

    const formatPrice = (price: number) => {
        return new Intl.NumberFormat('tr-TR', {
            style: 'currency',
            currency: order.currency,
        }).format(price)
    }

    const copyToClipboard = (text: string) => {
        navigator.clipboard.writeText(text).then(() => {
            setCopied(true)
            setTimeout(() => setCopied(false), 2000)
        })
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
                                        {formatPrice(order.total)}
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
                                                    {item.quantity} adet x {formatPrice(item.unit_price)}
                                                </p>
                                            </div>
                                            <div className="text-right font-semibold">
                                                {formatPrice(item.total_price)}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <Separator />

                            {/* Order Summary */}
                            <div className="space-y-2">
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">Ara Toplam</span>
                                    <span className="font-medium">
                                        {formatPrice(order.subtotal)}
                                    </span>
                                </div>
                                
                                {/* Shipping Cost */}
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">
                                        {order.shipping_is_free ? (
                                            <span className="text-green-600 dark:text-green-400">Kargo</span>
                                        ) : (
                                            'Kargo'
                                        )}
                                    </span>
                                    <span className={`font-medium ${order.shipping_is_free ? 'text-green-600 dark:text-green-400' : ''}`}>
                                        {order.shipping_is_free ? (
                                            <span>Ücretsiz</span>
                                        ) : (
                                            formatPrice(order.shipping_cost)
                                        )}
                                    </span>
                                </div>
                            </div>

                            <Separator />

                            {/* Total */}
                            <div className="flex justify-between text-lg font-bold">
                                <span>Toplam</span>
                                <span className="text-primary">
                                    {formatPrice(order.total)}
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Payment Information */}
                    {payment_info && (
                        <Card className="mb-8">
                            <CardHeader>
                                <CardTitle>
                                    {payment_info.type === 'transfer' ? 'Havale/EFT Bilgileri' : 'Ödeme Linki'}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {payment_info.type === 'transfer' && payment_info.bank_account ? (
                                    <>
                                        <div className="bg-muted p-4 rounded-lg space-y-3">
                                            <div className="flex justify-between items-center">
                                                <span className="text-sm text-muted-foreground">Hesap Sahibi:</span>
                                                <span className="font-semibold">{payment_info.bank_account.name}</span>
                                            </div>
                                            <div className="flex justify-between items-center">
                                                <span className="text-sm text-muted-foreground">Banka:</span>
                                                <span className="font-semibold">{payment_info.bank_account.bank}</span>
                                            </div>
                                            {payment_info.bank_account.branch && (
                                                <div className="flex justify-between items-center">
                                                    <span className="text-sm text-muted-foreground">Şube:</span>
                                                    <span className="font-semibold">{payment_info.bank_account.branch}</span>
                                                </div>
                                            )}
                                            <Separator />
                                            <div className="space-y-2">
                                                <div className="flex justify-between items-center">
                                                    <span className="text-sm text-muted-foreground">IBAN:</span>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => copyToClipboard(payment_info.bank_account!.iban)}
                                                        className="h-auto p-0 font-mono text-sm"
                                                    >
                                                        {payment_info.bank_account.iban}
                                                        <Copy className="ml-2 h-4 w-4" />
                                                    </Button>
                                                </div>
                                                {copied && (
                                                    <p className="text-xs text-green-600 text-right">Kopyalandı!</p>
                                                )}
                                            </div>
                                            <Separator />
                                            <div className="flex justify-between items-center">
                                                <span className="text-sm text-muted-foreground">Ödenecek Tutar:</span>
                                                <span className="text-lg font-bold text-primary">
                                                    {formatPrice(order.total)}
                                                </span>
                                            </div>
                                        </div>
                                        <div className="bg-blue-50 dark:bg-blue-950 p-4 rounded-lg">
                                            <p className="text-sm text-blue-900 dark:text-blue-100">
                                                <strong>Önemli:</strong> Havale/EFT yaparken açıklama kısmına{' '}
                                                <strong>{order.order_no}</strong> numaralı sipariş numaranızı yazmayı unutmayın.
                                            </p>
                                        </div>
                                        <Button
                                            asChild
                                            className="w-full"
                                            onClick={() => {
                                                // Open bank transfer page in new tab
                                                // This would typically open the bank's transfer page
                                                // For now, we'll just show a message
                                            }}
                                        >
                                            <a
                                                href={`https://www.google.com/search?q=${encodeURIComponent(
                                                    payment_info.bank_account.bank + ' havale eft'
                                                )}`}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                            >
                                                Ödeme Yap <ExternalLink className="ml-2 h-4 w-4" />
                                            </a>
                                        </Button>
                                    </>
                                ) : payment_info.type === 'paytr_link' && payment_info.payment_link ? (
                                    <>
                                        <div className="bg-muted p-4 rounded-lg space-y-3">
                                            <p className="text-sm text-muted-foreground">
                                                Ödeme linkiniz hazır. Aşağıdaki butona tıklayarak güvenli ödeme sayfasına
                                                yönlendirileceksiniz.
                                            </p>
                                            <div className="flex items-center gap-2 p-2 bg-background rounded border">
                                                <span className="text-xs font-mono flex-1 truncate">
                                                    {payment_info.payment_link}
                                                </span>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => copyToClipboard(payment_info.payment_link!)}
                                                    className="h-8 w-8 p-0"
                                                >
                                                    <Copy className="h-4 w-4" />
                                                </Button>
                                            </div>
                                            {copied && (
                                                <p className="text-xs text-green-600">Link kopyalandı!</p>
                                            )}
                                        </div>
                                        <Button
                                            asChild
                                            className="w-full"
                                            size="lg"
                                            onClick={() => {
                                                window.open(payment_info.payment_link!, '_blank')
                                            }}
                                        >
                                            <a
                                                href={payment_info.payment_link!}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                            >
                                                Ödeme Yap <ExternalLink className="ml-2 h-4 w-4" />
                                            </a>
                                        </Button>
                                    </>
                                ) : null}
                            </CardContent>
                        </Card>
                    )}

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

