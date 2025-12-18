import { PropsWithChildren, useEffect, useState } from 'react'
import { Link, usePage } from '@inertiajs/react'
import { motion } from 'framer-motion'
import { ShoppingCart, Menu, Package, Heart, Lock } from 'lucide-react'
import { FaWhatsapp, FaInstagram, FaTiktok, FaPhone } from 'react-icons/fa'
import { Button } from '@/components/ui/button'
import { Sheet, SheetContent, SheetTrigger } from '@/components/ui/sheet'
import { Badge } from '@/components/ui/badge'
import { getFavorites } from '@/lib/favorites'
import { PricePinDialog } from '@/components/PricePinDialog'
import { usePriceVisibility } from '@/hooks/usePriceVisibility'

interface GuestLayoutProps extends PropsWithChildren {
    cartCount?: number
}

export default function GuestLayout({ children, cartCount = 0 }: GuestLayoutProps) {
    const { url } = usePage()
    const [favoritesCount, setFavoritesCount] = useState(0)
    const [pinDialogOpen, setPinDialogOpen] = useState(false)
    const { isPriceVisible } = usePriceVisibility()

    useEffect(() => {
        // Initial load
        setFavoritesCount(getFavorites().length)

        // Listen for changes
        const handleChange = () => {
            setFavoritesCount(getFavorites().length)
        }

        window.addEventListener('favorites-changed', handleChange)

        return () => {
            window.removeEventListener('favorites-changed', handleChange)
        }
    }, [])

    return (
        <div className="min-h-screen flex flex-col">
            {/* Header */}
            <header className="sticky top-0 z-50 w-full border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
                <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 items-center justify-between">
                        {/* Logo */}
                        <Link href="/" className="flex items-center space-x-2">
                            <img src="/logo.png" alt="ZeonMoto" className="h-14" />
                            <span className="text-xl font-bold">{import.meta.env.VITE_APP_NAME}</span>
                        </Link>

                        {/* Desktop Navigation */}
                        <nav className="hidden md:flex items-center space-x-6">
                            <Link
                                href="/shop"
                                className={`text-sm font-medium transition-colors hover:text-yellow-600 ${url.startsWith('/shop') ? 'text-yellow-600' : 'text-muted-foreground'
                                    }`}
                            >
                                Ürünler
                            </Link>
                            <Link
                                href="/favorites"
                                className={`text-sm font-medium transition-colors hover:text-yellow-600 ${url.startsWith('/favorites') ? 'text-yellow-600' : 'text-muted-foreground'
                                    }`}
                            >
                                Favoriler
                            </Link>
                        </nav>

                        {/* Cart Section */}
                        <div className="flex items-center gap-2 md:gap-5">
                            {/* Cart */}
                            <Link href="/cart" className="relative group flex items-center ml-2">
                                <ShoppingCart className="h-6 w-6 text-muted-foreground group-hover:text-yellow-600 transition-colors" />
                                {cartCount > 0 && (
                                    <span className="absolute -top-2 -right-2 rounded-full bg-yellow-500 text-white text-[10px] px-1.5 py-0.5 font-semibold border border-background shadow group-hover:bg-yellow-600 transition-all">
                                        {cartCount > 99 ? '99+' : cartCount}
                                    </span>
                                )}
                            </Link>

                            {/* Mobile Menu */}
                            <div className="md:hidden ml-1">
                                <Sheet>
                                    <SheetTrigger asChild>
                                        <Button variant="ghost" size="icon">
                                            <Menu className="h-6 w-6 text-muted-foreground" />
                                        </Button>
                                    </SheetTrigger>
                                    <SheetContent side="right" className="p-0">
                                        <nav className="flex flex-col gap-1 mt-10 px-4">
                                            <Link
                                                href="/shop"
                                                className={`px-3 py-2 rounded-md text-base font-semibold transition-colors ${url.startsWith('/shop')
                                                    ? 'bg-yellow-50 text-yellow-600'
                                                    : 'hover:bg-muted'
                                                    }`}
                                            >
                                                Ürünler
                                            </Link>
                                            <Link
                                                href="/favorites"
                                                className={`px-3 py-2 rounded-md text-base font-semibold transition-colors ${url.startsWith('/favorites')
                                                    ? 'bg-yellow-50 text-yellow-600'
                                                    : 'hover:bg-muted'
                                                    }`}
                                            >
                                                Favoriler
                                            </Link>
                                            <Link
                                                href="/cart"
                                                className="px-3 py-2 rounded-md text-base font-semibold hover:bg-muted transition-colors"
                                            >
                                                Sepetim
                                            </Link>
                                        </nav>
                                    </SheetContent>
                                </Sheet>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            {/* Main Content */}
            <main className="flex-1">{children}</main>

            {/* Sticky Floating Buttons */}
            <div className="fixed bottom-6 left-6 right-6 z-50 flex justify-between pointer-events-none">
                {/* Price PIN Button - Sol Alt */}
                <button
                    onClick={() => setPinDialogOpen(true)}
                    className="pointer-events-auto relative group flex items-center justify-center w-14 h-14 rounded-full bg-white shadow-lg border-2 border-yellow-200 hover:border-yellow-500 transition-all hover:scale-110"
                    title={isPriceVisible ? 'Fiyatlar görünür' : 'Fiyatları görüntülemek için PIN girin'}
                >
                    <Lock
                        className={`h-6 w-6 transition-colors ${isPriceVisible
                            ? 'text-yellow-600'
                            : 'text-muted-foreground group-hover:text-yellow-600'
                            }`}
                    />
                    {isPriceVisible && (
                        <span className="absolute -top-1 -right-1 w-3 h-3 bg-green-500 rounded-full border-2 border-white"></span>
                    )}
                </button>

                {/* Favorites Button - Sağ Alt */}
                <Link
                    href="/favorites"
                    className="pointer-events-auto relative group flex items-center justify-center w-14 h-14 rounded-full bg-white shadow-lg border-2 border-yellow-200 hover:border-yellow-500 transition-all hover:scale-110"
                >
                    <Heart className="h-6 w-6 text-muted-foreground group-hover:text-yellow-600 transition-colors" />
                    {favoritesCount > 0 && (
                        <span className="absolute -top-2 -right-2 rounded-full bg-yellow-500 text-white text-xs px-2 py-0.5 font-semibold border-2 border-white shadow">
                            {favoritesCount > 99 ? '99+' : favoritesCount}
                        </span>
                    )}
                </Link>
            </div>

            {/* Price PIN Dialog */}
            <PricePinDialog open={pinDialogOpen} onOpenChange={setPinDialogOpen} />

            {/* Footer Hero Section */}
            <div className="relative bg-gradient-to-r from-yellow-400 via-yellow-500 to-yellow-600 overflow-hidden">
                {/* Arka plan deseni */}
                <div className="absolute inset-0 opacity-10">
                    <div className="absolute inset-0" style={{
                        backgroundImage: "url(\"data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23000000' fill-opacity='0.4'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E\")",
                        backgroundSize: '30px 30px'
                    }} />
                </div>

                <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        {/* Logo ve Hakkımızda */}
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            whileInView={{ opacity: 1, y: 0 }}
                            viewport={{ once: true }}
                            transition={{ duration: 0.6 }}
                            className="text-center lg:text-left"
                        >
                            <img src="/logo.png" alt="ZeonMoto" className="h-32 mx-auto lg:mx-0 mb-4 grayscale-[1] contrast-200" style={{ filter: 'grayscale(100%) brightness(0)' }} />
                            <h3 className="text-2xl font-bold text-white mb-2">Zeon MOTO</h3>
                            <p className="text-white/90 text-sm">
                                Motosiklet parçaları ve aksesuarları için güvenilir adresiniz.
                                Kaliteli ürünler, uygun fiyatlar ve hızlı teslimat.
                            </p>
                        </motion.div>

                        {/* Hızlı Linkler */}
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            whileInView={{ opacity: 1, y: 0 }}
                            viewport={{ once: true }}
                            transition={{ duration: 0.6, delay: 0.2 }}
                            className="text-center lg:text-left"
                        >
                            <h3 className="text-xl font-semibold text-white mb-4">Hızlı Linkler</h3>
                            <ul className="space-y-3">
                                <li>
                                    <Link href="/shop" className="text-white/90 hover:text-white transition-colors flex items-center justify-center lg:justify-start gap-2">
                                        <Package className="h-4 w-4" />
                                        Ürünler
                                    </Link>
                                </li>
                                <li>
                                    <Link href="/cart" className="text-white/90 hover:text-white transition-colors flex items-center justify-center lg:justify-start gap-2">
                                        <ShoppingCart className="h-4 w-4" />
                                        Sepetim
                                    </Link>
                                </li>
                                <li>
                                    <Link href="/favorites" className="text-white/90 hover:text-white transition-colors flex items-center justify-center lg:justify-start gap-2">
                                        <Heart className="h-4 w-4" />
                                        Favorilerim
                                    </Link>
                                </li>
                            </ul>
                        </motion.div>

                        {/* İletişim ve Sosyal Medya */}
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            whileInView={{ opacity: 1, y: 0 }}
                            viewport={{ once: true }}
                            transition={{ duration: 0.6, delay: 0.4 }}
                            className="text-center lg:text-left"
                        >
                            <h3 className="text-xl font-semibold text-white mb-4">İletişim</h3>
                            <div className="space-y-3 mb-6">
                                <div className="text-white/90">
                                    <span className="block font-bold text-white mb-1">Onur GEDİKKAYA</span>
                                    <a href="tel:05077378097" className="flex items-center justify-center lg:justify-start gap-2 hover:text-white transition-colors">
                                        <FaPhone className="h-4 w-4" />
                                        0507 737 80 97
                                    </a>
                                </div>
                            </div>

                            {/* Sosyal Medya Linkleri */}
                            <div className="flex flex-wrap justify-center lg:justify-start gap-3">
                                <motion.a
                                    href="https://wa.me/905070004777"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="inline-flex items-center px-4 py-2 rounded-full text-white bg-green-500 hover:bg-green-600 transition-transform"
                                    whileHover={{ scale: 1.05 }}
                                    whileTap={{ scale: 0.95 }}
                                >
                                    <FaWhatsapp className="w-4 h-4 mr-2" />
                                    <span className="text-sm font-medium">WhatsApp</span>
                                </motion.a>
                                <motion.a
                                    href="https://www.instagram.com/zeonmotomarket/"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="inline-flex items-center px-4 py-2 rounded-full text-white bg-pink-500 hover:bg-pink-600 transition-transform"
                                    whileHover={{ scale: 1.05 }}
                                    whileTap={{ scale: 0.95 }}
                                >
                                    <FaInstagram className="w-4 h-4 mr-2" />
                                    <span className="text-sm font-medium">Instagram</span>
                                </motion.a>
                                <motion.a
                                    href="https://www.tiktok.com/@zeonmotoryedekparca"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="inline-flex items-center px-4 py-2 rounded-full text-white bg-black hover:bg-gray-800 transition-transform"
                                    whileHover={{ scale: 1.05 }}
                                    whileTap={{ scale: 0.95 }}
                                >
                                    <FaTiktok className="w-4 h-4 mr-2" />
                                    <span className="text-sm font-medium">TikTok</span>
                                </motion.a>
                            </div>
                        </motion.div>
                    </div>
                </div>
            </div>

            {/* Footer Copyright */}
            <footer className="border-t bg-gray-900 text-white">
                <div className="container mx-auto px-4 sm:px-6 lg:px-8 py-6">
                    <div className="text-center text-sm">
                        © {new Date().getFullYear()} ZeonMoto. Tüm hakları saklıdır.
                    </div>
                </div>
            </footer>
        </div>
    )
}

