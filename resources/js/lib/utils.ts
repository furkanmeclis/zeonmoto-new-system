import { clsx, type ClassValue } from "clsx"
import { twMerge } from "tailwind-merge"

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

export function formatCurrency(price: number): string {
  return new Intl.NumberFormat('tr-TR', {
    style: 'currency',
    currency: 'TRY',
  }).format(price)
}

export function appendHead(metaData: {
  description?: string
  keywords?: string
  ogTitle?: string
  ogDescription?: string
  ogImage?: string
  ogUrl?: string
  canonicalUrl?: string
}) {
  // This function can be used to update meta tags
  // For now, it's a placeholder as Inertia handles head management
  if (typeof window !== 'undefined') {
    // Meta tags are handled by Inertia's Head component
    // This function can be extended if needed
  }
}
