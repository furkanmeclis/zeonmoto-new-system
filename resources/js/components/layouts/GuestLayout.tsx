import { PropsWithChildren, useEffect, useState } from 'react'
import { Link, usePage } from '@inertiajs/react'
import { ShoppingCart, Menu, Package, Heart, Lock } from 'lucide-react'
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
                            <img src="/logo.png" alt="Moto GPT" className="h-14" />
                            <span className="text-xl font-bold">{import.meta.env.VITE_APP_NAME}</span>
                        </Link>

                        {/* Desktop Navigation */}
                        <nav className="hidden md:flex items-center space-x-6">
                            <Link
                                href="/shop"
                                className={`text-sm font-medium transition-colors hover:text-primary ${url.startsWith('/shop') ? 'text-primary' : 'text-muted-foreground'
                                    }`}
                            >
                                Ürünler
                            </Link>
                            <Link
                                href="/favorites"
                                className={`text-sm font-medium transition-colors hover:text-primary ${url.startsWith('/favorites') ? 'text-primary' : 'text-muted-foreground'
                                    }`}
                            >
                                Favoriler
                            </Link>
                        </nav>

                        {/* Cart & Favorites Section - Modern & Cleaner Design */}
                        <div className="flex items-center gap-2 md:gap-5">
                            {/* Price PIN Button */}
                            <button
                                onClick={() => setPinDialogOpen(true)}
                                className="relative group flex items-center"
                                title={isPriceVisible ? 'Fiyatlar görünür' : 'Fiyatları görüntülemek için PIN girin'}
                            >
                                <Lock
                                    className={`h-6 w-6 transition-colors ${isPriceVisible
                                        ? 'text-primary'
                                        : 'text-muted-foreground group-hover:text-primary'
                                        }`}
                                />
                            </button>

                            {/* Favorites */}
                            <Link href="/favorites" className="relative group flex items-center">
                                <Heart className="h-6 w-6 text-muted-foreground group-hover:text-primary transition-colors" />
                                {favoritesCount > 0 && (
                                    <span className="absolute -top-2 -right-2 rounded-full bg-destructive text-white text-[10px] px-1.5 py-0.5 font-semibold border border-background shadow group-hover:bg-primary transition-all">
                                        {favoritesCount > 99 ? '99+' : favoritesCount}
                                    </span>
                                )}
                            </Link>

                            {/* Cart */}
                            <Link href="/cart" className="relative group flex items-center ml-2">
                                <ShoppingCart className="h-6 w-6 text-muted-foreground group-hover:text-primary transition-colors" />
                                {cartCount > 0 && (
                                    <span className="absolute -top-2 -right-2 rounded-full bg-destructive text-white text-[10px] px-1.5 py-0.5 font-semibold border border-background shadow group-hover:bg-primary transition-all">
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
                                                    ? 'bg-primary/10 text-primary'
                                                    : 'hover:bg-muted'
                                                    }`}
                                            >
                                                Ürünler
                                            </Link>
                                            <Link
                                                href="/favorites"
                                                className={`px-3 py-2 rounded-md text-base font-semibold transition-colors ${url.startsWith('/favorites')
                                                    ? 'bg-primary/10 text-primary'
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

            {/* Price PIN Dialog */}
            <PricePinDialog open={pinDialogOpen} onOpenChange={setPinDialogOpen} />

            {/* Footer */}
            <footer className="border-t bg-muted/50">
                <div className="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div>
                            <img src="/logo.png" alt="Moto GPT" className="h-48 mx-auto" />

                        </div>
                        <div>
                            <h3 className="font-semibold mb-4">Hızlı Linkler</h3>
                            <ul className="space-y-2 text-sm">
                                <li>
                                    <Link href="/shop" className="text-muted-foreground hover:text-primary">
                                        Ürünler
                                    </Link>
                                </li>
                                <li>
                                    <Link href="/cart" className="text-muted-foreground hover:text-primary">
                                        Sepetim
                                    </Link>
                                </li>
                                <li>
                                    <Link href="/favorites" className="text-muted-foreground hover:text-primary">
                                        Favorilerim
                                    </Link>
                                </li>
                            </ul>
                        </div>
                        <div>
                            <h3 className="font-semibold mb-4">İletişim</h3>
                            <p className="text-sm text-muted-foreground mb-1">
                                Destek için bizimle iletişime geçin.
                            </p>
                            <div className="mt-3">
                                <div className="text-md text-muted-foreground">
                                    <span className="block font-bold">İsa SÜSLÜ</span>
                                    <a href="tel:05077378097" className="block hover:text-primary">
                                        0507 737 80 97
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div className="mt-8 pt-8 border-t text-center text-sm text-muted-foreground">
                        © {new Date().getFullYear()} Moto GPT. Tüm hakları saklıdır.
                    </div>
                </div>
            </footer>
        </div>
    )
}

