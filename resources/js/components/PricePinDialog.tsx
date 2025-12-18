import { useState, useRef, useEffect } from 'react'
import { Lock } from 'lucide-react'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { usePriceVisibility } from '@/hooks/usePriceVisibility'

interface PricePinDialogProps {
    open: boolean
    onOpenChange: (open: boolean) => void
}

export function PricePinDialog({ open, onOpenChange }: PricePinDialogProps) {
    const { verifyPin, isVerifying } = usePriceVisibility()
    const [pin, setPin] = useState(['', '', '', ''])
    const [error, setError] = useState<string | null>(null)
    const inputRefs = useRef<(HTMLInputElement | null)[]>([])

    // Dialog açıldığında ilk input'a focus
    useEffect(() => {
        if (open) {
            setPin(['', '', '', ''])
            setError(null)
            setTimeout(() => {
                inputRefs.current[0]?.focus()
            }, 100)
        }
    }, [open])

    const handlePinChange = (index: number, value: string) => {
        // Sadece rakam kabul et
        if (value && !/^\d$/.test(value)) {
            return
        }

        const newPin = [...pin]
        newPin[index] = value
        setPin(newPin)
        setError(null)

        // Otomatik olarak bir sonraki input'a geç
        if (value && index < 3) {
            inputRefs.current[index + 1]?.focus()
        }
    }

    const handleKeyDown = (index: number, e: React.KeyboardEvent<HTMLInputElement>) => {
        // Backspace ile önceki input'a geç
        if (e.key === 'Backspace' && !pin[index] && index > 0) {
            inputRefs.current[index - 1]?.focus()
        }
    }

    const handlePaste = (e: React.ClipboardEvent) => {
        e.preventDefault()
        const pastedData = e.clipboardData.getData('text').slice(0, 4)
        if (/^\d{1,4}$/.test(pastedData)) {
            const newPin = [...pin]
            for (let i = 0; i < 4; i++) {
                newPin[i] = pastedData[i] || ''
            }
            setPin(newPin)
            setError(null)
            // Son dolu input'a focus
            const lastIndex = Math.min(pastedData.length - 1, 3)
            inputRefs.current[lastIndex]?.focus()
        }
    }

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault()
        const pinString = pin.join('')

        if (pinString.length !== 4) {
            setError('Lütfen 4 haneli PIN kodunu girin.')
            return
        }

        const result = await verifyPin(pinString)

        if (result.success) {
            onOpenChange(false)
            setPin(['', '', '', ''])
            setError(null)
        } else {
            setError(result.message)
            setPin(['', '', '', ''])
            setTimeout(() => {
                inputRefs.current[0]?.focus()
            }, 100)
        }
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <div className="flex items-center justify-center mb-2">
                        <div className="rounded-full bg-primary/10 p-3">
                            <Lock className="h-6 w-6 text-primary" />
                        </div>
                    </div>
                    <DialogTitle className="text-center">Fiyat Görünürlüğü</DialogTitle>
                    <DialogDescription className="text-center">
                        Fiyatları görüntülemek için 4 haneli PIN kodunu girin.
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="pin" className="sr-only">
                            PIN Kodu
                        </Label>
                        <div className="flex gap-2 justify-center">
                            {pin.map((digit, index) => (
                                <Input
                                    key={index}
                                    ref={(el) => {
                                        inputRefs.current[index] = el
                                    }}
                                    type="text"
                                    inputMode="numeric"
                                    maxLength={1}
                                    value={digit}
                                    onChange={(e) => handlePinChange(index, e.target.value)}
                                    onKeyDown={(e) => handleKeyDown(index, e)}
                                    onPaste={index === 0 ? handlePaste : undefined}
                                    className="w-14 h-14 text-center text-xl font-semibold"
                                    disabled={isVerifying}
                                    autoComplete="off"
                                />
                            ))}
                        </div>
                        {error && (
                            <p className="text-sm text-destructive text-center">{error}</p>
                        )}
                    </div>

                    <div className="flex gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            className="flex-1"
                            onClick={() => onOpenChange(false)}
                            disabled={isVerifying}
                        >
                            İptal
                        </Button>
                        <Button type="submit" className="flex-1" disabled={isVerifying}>
                            {isVerifying ? 'Doğrulanıyor...' : 'Doğrula'}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    )
}

