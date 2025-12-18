import React from 'react';
import { Link, router } from '@inertiajs/react';
import { motion } from 'framer-motion';

interface PaginationMeta {
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
    from: number;
    to: number;
}

interface PaginationProps {
    meta: PaginationMeta;
    links?: Array<{ url: string | null; label: string; active: boolean }>;
}

const Pagination: React.FC<PaginationProps> = ({ meta, links }) => {
    if (!meta || meta.total <= meta.per_page) return null;

    const currentPage = meta.current_page;
    const lastPage = meta.last_page;

    // Mevcut URL'den query parametrelerini al
    const getPageUrl = (page: number) => {
        if (typeof window === 'undefined') return `?page=${page}`;
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('page', page.toString());
        return currentUrl.pathname + currentUrl.search;
    };

    // Gösterilecek sayfa numaralarını hesapla
    const getPageNumbers = () => {
        const pages: (number | string)[] = [];
        const delta = 2; // Aktif sayfanın her iki yanında kaç sayfa gösterileceği

        for (let i = 1; i <= lastPage; i++) {
            if (
                i === 1 || // İlk sayfa
                i === lastPage || // Son sayfa
                (i >= currentPage - delta && i <= currentPage + delta) // Aktif sayfa etrafındaki sayfalar
            ) {
                pages.push(i);
            }
        }

        // Boşlukları ekle
        const withDots: (number | string)[] = [];
        let prevPage = 0;

        pages.forEach(page => {
            if (prevPage && typeof page === 'number' && typeof prevPage === 'number' && page - prevPage > 1) {
                withDots.push('...');
            }
            withDots.push(page);
            prevPage = typeof page === 'number' ? page : prevPage;
        });

        return withDots;
    };

    const pageNumbers = getPageNumbers();

    // Eğer links varsa (Laravel pagination format), onu kullan
    if (links && links.length > 0) {
        return (
            <nav className="flex flex-col items-center space-y-3 my-8" aria-label="Sayfalama">
                {/* Mobil Görünüm */}
                <div className="flex items-center justify-between w-full px-4 sm:hidden">
                    <motion.div
                        whileTap={{ scale: 0.95 }}
                        className="flex-1"
                    >
                        {currentPage > 1 && links[0]?.url && (
                            <Link
                                href={links[0].url}
                                className="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                            >
                                Önceki
                            </Link>
                        )}
                    </motion.div>
                    <div className="flex-1 text-center">
                        <p className="text-sm text-gray-700">
                            <span className="font-medium">{currentPage}</span>
                            {' / '}
                            <span className="font-medium">{lastPage}</span>
                        </p>
                    </div>
                    <motion.div
                        whileTap={{ scale: 0.95 }}
                        className="flex-1 text-right"
                    >
                        {currentPage < lastPage && links[links.length - 1]?.url && (
                            <Link
                                href={links[links.length - 1].url}
                                className="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                            >
                                Sonraki
                            </Link>
                        )}
                    </motion.div>
                </div>

                {/* Masaüstü Görünüm */}
                <div className="hidden sm:flex sm:flex-1 sm:items-center sm:justify-center">
                    <div className="flex space-x-2">
                        {/* Önceki Sayfa */}
                        {links[0]?.url && (
                            <motion.div
                                whileHover={{ scale: 1.05 }}
                                whileTap={{ scale: 0.95 }}
                            >
                                <Link
                                    href={links[0].url}
                                    className="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-yellow-50 hover:text-yellow-600 hover:border-yellow-500"
                                >
                                    <span className="sr-only">Önceki Sayfa</span>
                                    &laquo;
                                </Link>
                            </motion.div>
                        )}

                        {/* Sayfa Numaraları */}
                        {links.slice(1, -1).map((link, index) => {
                            if (!link.url) {
                                return (
                                    <span
                                        key={`dots-${index}`}
                                        className="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700"
                                    >
                                        ...
                                    </span>
                                );
                            }

                            return (
                                <motion.div
                                    key={link.url}
                                    whileHover={{ scale: 1.05 }}
                                    whileTap={{ scale: 0.95 }}
                                >
                                    <Link
                                        href={link.url}
                                        className={`relative inline-flex items-center px-4 py-2 text-sm font-medium rounded-md border ${
                                            link.active
                                                ? 'z-10 bg-yellow-50 border-yellow-500 text-yellow-600'
                                                : 'bg-white border-gray-300 text-gray-700 hover:bg-yellow-50 hover:text-yellow-600 hover:border-yellow-500'
                                        }`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                </motion.div>
                            );
                        })}

                        {/* Sonraki Sayfa */}
                        {links[links.length - 1]?.url && (
                            <motion.div
                                whileHover={{ scale: 1.05 }}
                                whileTap={{ scale: 0.95 }}
                            >
                                <Link
                                    href={links[links.length - 1].url}
                                    className="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-yellow-50 hover:text-yellow-600 hover:border-yellow-500"
                                >
                                    <span className="sr-only">Sonraki Sayfa</span>
                                    &raquo;
                                </Link>
                            </motion.div>
                        )}
                    </div>
                </div>

                {/* Toplam Sonuç Bilgisi */}
                <div className="text-sm text-gray-700 text-center">
                    <span className="font-medium">{meta.total}</span> sonuçtan{' '}
                    <span className="font-medium">{meta.from}</span>-
                    <span className="font-medium">{meta.to}</span> arası gösteriliyor
                </div>
            </nav>
        );
    }

    // Links yoksa, basit sayfa numaraları ile göster
    return (
        <nav className="flex flex-col items-center space-y-3 my-8" aria-label="Sayfalama">
            {/* Mobil Görünüm */}
            <div className="flex items-center justify-between w-full px-4 sm:hidden">
                <motion.div
                    whileTap={{ scale: 0.95 }}
                    className="flex-1"
                >
                    {currentPage > 1 && (
                        <Link
                            href={getPageUrl(currentPage - 1)}
                            className="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                        >
                            Önceki
                        </Link>
                    )}
                </motion.div>
                <div className="flex-1 text-center">
                    <p className="text-sm text-gray-700">
                        <span className="font-medium">{currentPage}</span>
                        {' / '}
                        <span className="font-medium">{lastPage}</span>
                    </p>
                </div>
                <motion.div
                    whileTap={{ scale: 0.95 }}
                    className="flex-1 text-right"
                >
                    {currentPage < lastPage && (
                        <Link
                            href={getPageUrl(currentPage + 1)}
                            className="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                        >
                            Sonraki
                        </Link>
                    )}
                </motion.div>
            </div>

            {/* Masaüstü Görünüm */}
            <div className="hidden sm:flex sm:flex-1 sm:items-center sm:justify-center">
                <div className="flex space-x-2">
                    {/* Önceki Sayfa */}
                    {currentPage > 1 && (
                        <motion.div
                            whileHover={{ scale: 1.05 }}
                            whileTap={{ scale: 0.95 }}
                        >
                            <Link
                                href={getPageUrl(currentPage - 1)}
                                className="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-yellow-50 hover:text-yellow-600 hover:border-yellow-500"
                            >
                                <span className="sr-only">Önceki Sayfa</span>
                                &laquo;
                            </Link>
                        </motion.div>
                    )}

                    {/* Sayfa Numaraları */}
                    {pageNumbers.map((page, index) => {
                        if (page === '...') {
                            return (
                                <span
                                    key={`dots-${index}`}
                                    className="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700"
                                >
                                    ...
                                </span>
                            );
                        }

                        const pageNum = page as number;
                        return (
                            <motion.div
                                key={pageNum}
                                whileHover={{ scale: 1.05 }}
                                whileTap={{ scale: 0.95 }}
                            >
                                <Link
                                    href={getPageUrl(pageNum)}
                                    className={`relative inline-flex items-center px-4 py-2 text-sm font-medium rounded-md border ${
                                        currentPage === pageNum
                                            ? 'z-10 bg-yellow-50 border-yellow-500 text-yellow-600'
                                            : 'bg-white border-gray-300 text-gray-700 hover:bg-yellow-50 hover:text-yellow-600 hover:border-yellow-500'
                                    }`}
                                >
                                    {pageNum}
                                </Link>
                            </motion.div>
                        );
                    })}

                    {/* Sonraki Sayfa */}
                    {currentPage < lastPage && (
                        <motion.div
                            whileHover={{ scale: 1.05 }}
                            whileTap={{ scale: 0.95 }}
                        >
                            <Link
                                href={getPageUrl(currentPage + 1)}
                                className="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-yellow-50 hover:text-yellow-600 hover:border-yellow-500"
                            >
                                <span className="sr-only">Sonraki Sayfa</span>
                                &raquo;
                            </Link>
                        </motion.div>
                    )}
                </div>
            </div>

            {/* Toplam Sonuç Bilgisi */}
            <div className="text-sm text-gray-700 text-center">
                <span className="font-medium">{meta.total}</span> sonuçtan{' '}
                <span className="font-medium">{meta.from}</span>-
                <span className="font-medium">{meta.to}</span> arası gösteriliyor
            </div>
        </nav>
    );
};

export default Pagination;

