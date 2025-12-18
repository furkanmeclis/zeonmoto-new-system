import { useState, useEffect } from 'react'
import axios from 'axios'

const STORAGE_KEY = 'price_pin_verified'
const STORAGE_VALUE = 'true'

export function usePriceVisibility() {
    const [isPriceVisible, setIsPriceVisible] = useState<boolean>(false)
    const [isVerifying, setIsVerifying] = useState<boolean>(false)

    // Sayfa yüklendiğinde localStorage'dan durumu kontrol et
    useEffect(() => {
        const verified = localStorage.getItem(STORAGE_KEY) === STORAGE_VALUE
        setIsPriceVisible(verified)
    }, [])

    /**
     * PIN doğrulama fonksiyonu
     */
    const verifyPin = async (pin: string): Promise<{ success: boolean; message: string }> => {
        if (pin.length !== 4 || !/^\d{4}$/.test(pin)) {
            return {
                success: false,
                message: 'PIN kodu 4 haneli rakam olmalıdır.',
            }
        }

        try {
            setIsVerifying(true)
            const response = await axios.post('/api/price/verify-pin', { pin })

            if (response.data.success) {
                // Başarılı doğrulamada localStorage'a kaydet
                localStorage.setItem(STORAGE_KEY, STORAGE_VALUE)
                setIsPriceVisible(true)
                
                // Sayfayı yenile
                setTimeout(() => {
                    window.location.reload()
                }, 500) // Kısa bir gecikme ile kullanıcıya başarı mesajını göstermek için
                
                return {
                    success: true,
                    message: response.data.message || 'PIN doğrulandı.',
                }
            } else {
                return {
                    success: false,
                    message: response.data.message || 'PIN doğrulanamadı.',
                }
            }
        } catch (error: any) {
            const message =
                error.response?.data?.message ||
                error.message ||
                'PIN doğrulama sırasında bir hata oluştu.'
            return {
                success: false,
                message,
            }
        } finally {
            setIsVerifying(false)
        }
    }

    /**
     * PIN doğrulama durumunu sıfırla (logout benzeri)
     */
    const resetVerification = () => {
        localStorage.removeItem(STORAGE_KEY)
        setIsPriceVisible(false)
    }

    return {
        isPriceVisible,
        isVerifying,
        verifyPin,
        resetVerification,
    }
}

