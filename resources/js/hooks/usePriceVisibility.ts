import { useState, useEffect } from 'react'
import axios from 'axios'

export function usePriceVisibility() {
    const [isPriceVisible, setIsPriceVisible] = useState<boolean>(false)
    const [isVerifying, setIsVerifying] = useState<boolean>(false)
    const [isLoading, setIsLoading] = useState<boolean>(true)

    // Sayfa yüklendiğinde session'dan durumu kontrol et
    useEffect(() => {
        const checkPinStatus = async () => {
            try {
                setIsLoading(true)
                const response = await axios.get('/api/price/check-status')
                if (response.data.success) {
                    setIsPriceVisible(response.data.verified)
                }
            } catch (error) {
                // Hata durumunda PIN girilmemiş sayılır
                setIsPriceVisible(false)
            } finally {
                setIsLoading(false)
            }
        }

        checkPinStatus()
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
                // Başarılı doğrulamada session'a kaydedildi (backend'de yapılıyor)
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
    const resetVerification = async () => {
        try {
            await axios.post('/api/price/reset-status')
            setIsPriceVisible(false)
        } catch (error) {
            // Hata olsa bile frontend state'ini güncelle
            setIsPriceVisible(false)
        }
    }

    return {
        isPriceVisible,
        isVerifying,
        isLoading,
        verifyPin,
        resetVerification,
    }
}

