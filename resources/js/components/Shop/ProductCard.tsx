import React, { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { Plus, Minus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useFavorites } from '@/hooks/useFavorites';
import { usePriceVisibility } from '@/hooks/usePriceVisibility';
import { formatCurrency } from '@/lib/utils';
import { Heart } from 'lucide-react';

interface Product {
    id: number;
    name: string;
    sku: string;
    price: number;
    retail_price?: number;
    base_price?: number;
    image: string | null;
    images?: string[];
    categories?: Array<{ id: number; name: string; slug: string }>;
    is_new?: number;
    is_discount?: number;
}

interface ProductCardProps {
    product: Product;
    cartItemQuantity?: number;
    cartItemId?: number | null;
    onAddToCart?: (productId: number, quantity: number) => void;
    onUpdateQuantity?: (cartItemId: number, quantity: number) => void;
    onRemoveFromCart?: (cartItemId: number) => void;
    onQuantityInputChange?: (productId: number, value: string) => void;
    onQuantityInputSubmit?: (productId: number) => void;
    quantityInput?: string;
}

const ProductCard: React.FC<ProductCardProps> = ({
    product,
    cartItemQuantity = 0,
    cartItemId,
    onAddToCart,
    onUpdateQuantity,
    onRemoveFromCart,
    onQuantityInputChange,
    onQuantityInputSubmit,
    quantityInput
}) => {
    const [loading, setLoading] = useState(false);
    const { checkFavorite, toggleFavorite } = useFavorites();
    const { isPriceVisible } = usePriceVisibility();
    const isFavorite = checkFavorite(product.id);
    const hasCartItem = cartItemQuantity > 0;

    const productImage = product.images?.[0] || product.image || '/logo.png';
    const hasDiscount = product.base_price && product.base_price > product.price;

    const handleAddToCart = async () => {
        if (!onAddToCart) return;
        setLoading(true);
        await onAddToCart(product.id, 1);
        setLoading(false);
    };

    const handleUpdateQuantity = async (change: number) => {
        if (!onUpdateQuantity || !cartItemId) return;
        const newQuantity = Math.max(1, Math.min(999, cartItemQuantity + change));
        setLoading(true);
        await onUpdateQuantity(cartItemId, newQuantity);
        setLoading(false);
    };

    const handleRemoveFromCart = async () => {
        if (!onRemoveFromCart || !cartItemId) return;
        setLoading(true);
        await onRemoveFromCart(cartItemId);
        setLoading(false);
    };

    return (
        <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            whileHover={{ y: -5 }}
            transition={{ duration: 0.2 }}
            className="bg-gradient-to-b from-white to-yellow-50 rounded-xl shadow-lg overflow-hidden border border-yellow-100 hover:shadow-xl hover:border-yellow-200 transition-all"
        >
            <Link href={`/products/${product.id}`}>
                <div className="relative aspect-square">
                    <img
                        src={productImage}
                        alt={product.name}
                        className="w-full h-full object-contain hover:scale-105 transition-transform duration-300"
                    />
                    <div className="absolute inset-0 flex items-center justify-center">
                        <img
                            src="/logo.png"
                            alt="Watermark"
                            className="w-full h-full object-contain opacity-30"
                        />
                    </div>
                    <div className="absolute top-2 right-2 flex flex-col gap-2 z-10">
                        {product.is_new === 1 && (
                            <span className="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full shadow-sm">
                                Yeni
                            </span>
                        )}
                        {product.is_discount === 1 && (
                            <span className="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full shadow-sm">
                                İndirim
                            </span>
                        )}
                    </div>
                    <Button
                        variant="ghost"
                        size="icon"
                        className="absolute top-2 left-2 bg-background/80 hover:bg-background z-10"
                        onClick={(e) => {
                            e.preventDefault();
                            toggleFavorite(product.id);
                        }}
                    >
                        <Heart className={`h-5 w-5 ${isFavorite ? 'fill-red-500 text-red-500' : ''}`} />
                    </Button>
                </div>
            </Link>

            <div className="p-4 space-y-4">
                <Link href={`/products/${product.id}`}>
                    <h3 className="text-lg font-semibold text-gray-900 line-clamp-2 hover:text-yellow-600 transition-colors">
                        {product.name}
                    </h3>
                </Link>

                <div className="flex items-center justify-between">
                    <span className="text-xl font-bold text-yellow-600">
                        {isPriceVisible 
                            ? formatCurrency(product.price) 
                            : formatCurrency(product.retail_price ?? product.price)}
                    </span>
                    {hasDiscount && isPriceVisible && (
                        <span className="text-sm text-gray-500 line-through">
                            {formatCurrency(product.base_price!)}
                        </span>
                    )}
                </div>

                {hasCartItem ? (
                    <div className="flex items-center justify-between bg-gray-50 border border-yellow-200 rounded-lg p-2">
                        <div className="flex items-center space-x-2">
                            <Button
                                variant="ghost"
                                size="icon"
                                className="h-8 w-8 text-yellow-700 hover:bg-yellow-100"
                                onClick={(e) => {
                                    e.preventDefault();
                                    handleUpdateQuantity(-1);
                                }}
                                disabled={loading || cartItemQuantity <= 1}
                            >
                                <Minus className="h-3 w-3" />
                            </Button>
                            <Input
                                type="number"
                                min="1"
                                max="999"
                                value={quantityInput !== undefined ? quantityInput : cartItemQuantity}
                                onChange={(e) => onQuantityInputChange?.(product.id, e.target.value)}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter') {
                                        e.currentTarget.blur();
                                        onQuantityInputSubmit?.(product.id);
                                    }
                                }}
                                onBlur={() => onQuantityInputSubmit?.(product.id)}
                                className="w-16 h-7 text-center text-sm font-medium px-1 border-0 focus-visible:ring-0 focus-visible:ring-offset-0"
                            />
                            <Button
                                variant="ghost"
                                size="icon"
                                className="h-8 w-8 text-yellow-700 hover:bg-yellow-100"
                                onClick={(e) => {
                                    e.preventDefault();
                                    handleUpdateQuantity(1);
                                }}
                                disabled={loading || cartItemQuantity >= 999}
                            >
                                <Plus className="h-3 w-3" />
                            </Button>
                        </div>
                        <Button
                            variant="ghost"
                            size="sm"
                            className="text-red-600 hover:text-red-700 text-sm font-medium"
                            onClick={(e) => {
                                e.preventDefault();
                                handleRemoveFromCart();
                            }}
                            disabled={loading}
                        >
                            <Trash2 className="h-4 w-4" />
                        </Button>
                    </div>
                ) : (
                    <motion.button
                        whileTap={{ scale: 0.95 }}
                        onClick={(e) => {
                            e.preventDefault();
                            handleAddToCart();
                        }}
                        disabled={loading}
                        className="w-full bg-gradient-to-r from-yellow-500 to-yellow-600 text-white py-2 px-4 rounded-lg font-medium hover:from-yellow-600 hover:to-yellow-700 transition-all shadow-md disabled:opacity-50"
                    >
                        {loading ? (
                            <div className="flex items-center justify-center space-x-2">
                                <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                                <span>Ekleniyor...</span>
                            </div>
                        ) : (
                            'Sepete Ekle'
                        )}
                    </motion.button>
                )}
                <div className="text-sm text-gray-500 flex items-center justify-between">
                    <span><span className="font-medium">SKU:</span> {product.sku}</span>
                    {product.categories && product.categories.length > 0 && (
                        <span><span className="font-medium">{product.categories[0].name}</span></span>
                    )}
                </div>
            </div>
        </motion.div>
    );
};

export default ProductCard;

