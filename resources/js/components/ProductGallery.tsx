"use client"

import { useState, useEffect, useCallback } from "react"
import { ChevronLeft, ChevronRight } from "lucide-react"
import { Button } from "@/components/ui/button"
import { cn } from "@/lib/utils"

interface ProductGalleryProps {
  images: string[]
  autoPlayInterval?: number
  className?: string
}

export function ProductGallery({ images, autoPlayInterval = 4000, className }: ProductGalleryProps) {
  const [currentIndex, setCurrentIndex] = useState(0)
  const [isAutoPlaying, setIsAutoPlaying] = useState(true)

  const goToSlide = useCallback((index: number) => {
    setCurrentIndex(index)
    setIsAutoPlaying(false)
  }, [])

  const goToPrevious = useCallback(() => {
    setCurrentIndex((prevIndex) => (prevIndex === 0 ? images.length - 1 : prevIndex - 1))
    setIsAutoPlaying(false)
  }, [images.length])

  const goToNext = useCallback(() => {
    setCurrentIndex((prevIndex) => (prevIndex === images.length - 1 ? 0 : prevIndex + 1))
  }, [images.length])

  // Auto-play functionality
  useEffect(() => {
    if (!isAutoPlaying || images.length <= 1) return

    const interval = setInterval(() => {
      goToNext()
    }, autoPlayInterval)

    return () => clearInterval(interval)
  }, [isAutoPlaying, autoPlayInterval, images.length, goToNext])

  // Resume auto-play after 10 seconds of inactivity
  useEffect(() => {
    if (isAutoPlaying) return

    const timeout = setTimeout(() => {
      setIsAutoPlaying(true)
    }, 10000)

    return () => clearTimeout(timeout)
  }, [isAutoPlaying, currentIndex])

  if (images.length === 0) {
    return (
      <div className="flex items-center justify-center bg-white rounded-lg h-96 relative">
        <p className="text-muted-foreground">Görsel bulunmuyor</p>
        <img
          src="/logo.png"
          alt=""
          className="absolute inset-0 w-full h-full object-contain opacity-10 pointer-events-none"
        />
      </div>
    )
  }

  return (
    <div className={cn("flex flex-col gap-4", className)}>
      {/* Main Image Display */}
      <div className="relative aspect-square overflow-hidden rounded-lg bg-white group">
        <img
          src={images[currentIndex] || "/placeholder.svg"}
          alt={`Ürün görseli ${currentIndex + 1}`}
          className="w-full h-full object-contain transition-opacity duration-500"
        />
        {/* Filigran */}
        <img
          src="/logo.png"
          alt=""
          className="absolute inset-0 w-full h-full object-contain opacity-10 pointer-events-none"
        />

        {/* Navigation Arrows */}
        {images.length > 1 && (
          <>
            <Button
              variant="ghost"
              size="icon"
              className="absolute left-2 top-1/2 -translate-y-1/2 bg-background/80 backdrop-blur-sm opacity-0 group-hover:opacity-100 transition-opacity z-10"
              onClick={goToPrevious}
              aria-label="Önceki görsel"
            >
              <ChevronLeft className="h-6 w-6" />
            </Button>

            <Button
              variant="ghost"
              size="icon"
              className="absolute right-2 top-1/2 -translate-y-1/2 bg-background/80 backdrop-blur-sm opacity-0 group-hover:opacity-100 transition-opacity z-10"
              onClick={goToNext}
              aria-label="Sonraki görsel"
            >
              <ChevronRight className="h-6 w-6" />
            </Button>
          </>
        )}

        {/* Image Counter */}
        <div className="absolute bottom-4 right-4 bg-background/80 backdrop-blur-sm px-3 py-1 rounded-full text-sm z-10">
          {currentIndex + 1} / {images.length}
        </div>

        {/* Auto-play Indicator */}
        {isAutoPlaying && images.length > 1 && (
          <div className="absolute top-4 left-4 bg-background/80 backdrop-blur-sm px-3 py-1 rounded-full text-xs flex items-center gap-2 z-10">
            <span className="w-2 h-2 bg-primary rounded-full animate-pulse" />
            Otomatik
          </div>
        )}
      </div>

      {/* Thumbnail Navigation */}
      {images.length > 1 && (
        <div className="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
          {images.map((image, index) => (
            <button
              key={index}
              onClick={() => goToSlide(index)}
              className={cn(
                "flex-shrink-0 w-20 h-20 rounded-md overflow-hidden border-2 transition-all relative",
                currentIndex === index
                  ? "border-primary shadow-md scale-105"
                  : "border-border hover:border-primary/50 opacity-70 hover:opacity-100",
              )}
              aria-label={`${index + 1}. görsele git`}
            >
              <img
                src={image || "/placeholder.svg"}
                alt={`Thumbnail ${index + 1}`}
                className="w-full h-full object-contain"
              />
              {/* Thumbnail filigran */}
              <img
                src="/logo.png"
                alt=""
                className="absolute inset-0 w-full h-full object-contain opacity-10 pointer-events-none"
              />
            </button>
          ))}
        </div>
      )}
    </div>
  )
}

