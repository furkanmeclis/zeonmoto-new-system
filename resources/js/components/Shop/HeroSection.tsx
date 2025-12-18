import React from 'react';
import { motion } from 'framer-motion';
import { FaWhatsapp, FaInstagram, FaTiktok, FaPhone } from 'react-icons/fa';

interface SocialLinks {
    whatsapp: string;
    instagram: string;
    tiktok: string;
    phone: string;
}

interface HeroSectionProps {
    socialLinks: SocialLinks;
}

const HeroSection: React.FC<HeroSectionProps> = ({ socialLinks }) => {
    const socialButtons = [
        {
            icon: FaWhatsapp,
            label: 'WhatsApp',
            href: socialLinks.whatsapp,
            color: 'bg-green-500 hover:bg-green-600'
        },
        {
            icon: FaInstagram,
            label: 'Instagram',
            href: socialLinks.instagram,
            color: 'bg-pink-500 hover:bg-pink-600'
        },
        {
            icon: FaTiktok,
            label: 'TikTok',
            href: socialLinks.tiktok,
            color: 'bg-black hover:bg-gray-800'
        },
        {
            icon: FaPhone,
            label: socialLinks.phone,
            href: `tel:${socialLinks.phone.replace(/\s+/g, '')}`,
            color: 'bg-blue-500 hover:bg-blue-600'
        }
    ];

    return (
        <div className="relative bg-gradient-to-r from-yellow-400 via-yellow-500 to-yellow-600 overflow-hidden">
            {/* Arka plan deseni */}
            <div className="absolute inset-0 opacity-10">
                <div className="absolute inset-0" style={{
                    backgroundImage: "url(\"data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23000000' fill-opacity='0.4'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E\")",
                    backgroundSize: '30px 30px'
                }} />
            </div>

            <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                    {/* Sol taraf - Metin içeriği */}
                    <motion.div
                        initial={{ opacity: 0, x: -20 }}
                        animate={{ opacity: 1, x: 0 }}
                        transition={{ duration: 0.6 }}
                        className="text-center lg:text-left"
                    >
                        <h1 className="text-4xl sm:text-5xl font-bold text-white mb-4">
                            Zeon MOTO
                        </h1>
                        <p className="text-lg sm:text-xl text-white/90 mb-8">
                            Motosiklet parçaları ve aksesuarları için güvenilir adresiniz.
                            Kaliteli ürünler, uygun fiyatlar ve hızlı teslimat.
                        </p>
                        <div className="flex flex-wrap justify-center lg:justify-start gap-4">
                            {socialButtons.map((button, index) => (
                                <motion.a
                                    key={button.label}
                                    href={button.href}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className={`inline-flex items-center px-6 py-3 rounded-full text-white ${button.color} transition-transform`}
                                    whileHover={{ scale: 1.05 }}
                                    whileTap={{ scale: 0.95 }}
                                    initial={{ opacity: 0, y: 20 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    transition={{ delay: index * 0.1 }}
                                >
                                    <button.icon className="w-5 h-5 mr-2" />
                                    <span className="font-medium">{button.label}</span>
                                </motion.a>
                            ))}
                        </div>
                    </motion.div>

                    {/* Sağ taraf - Görsel */}
                    <motion.div
                        initial={{ opacity: 0, scale: 0.8 }}
                        animate={{ opacity: 1, scale: 1 }}
                        transition={{ duration: 0.6 }}
                        className="relative hidden lg:block"
                    >
                        <div className="aspect-w-4 aspect-h-3 rounded-lg overflow-hidden shadow-2xl">
                            <img
                                src="/images/banner.png"
                                alt="Zeon MOTO"
                                className="w-full h-full max-h-96 object-contain"
                            />
                        </div>
                    </motion.div>
                </div>
            </div>
        </div>
    );
};

export default HeroSection;

