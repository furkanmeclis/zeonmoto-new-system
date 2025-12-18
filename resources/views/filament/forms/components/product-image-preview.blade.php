@php
    $productId = $productId ?? null;
    $product = $productId ? \App\Models\Product::find($productId) : null;
    $imageUrl = $product?->default_image_url;
    $productName = $product?->name ?? 'Ürün Görseli';
@endphp

<div class="flex items-center justify-center">
    @if($imageUrl)
        <img 
            src="{{ $imageUrl }}" 
            alt="{{ $productName }}"
            class="w-16 h-16 object-cover rounded-lg border border-gray-300"
            onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'64\' height=\'64\'%3E%3Crect width=\'64\' height=\'64\' fill=\'%23f3f4f6\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%239ca3af\' font-size=\'12\'%3EGörsel Yok%3C/text%3E%3C/svg%3E'"
        />
    @else
        <div class="flex items-center justify-center w-16 h-16 bg-gray-100 rounded-lg border border-gray-300">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
        </div>
    @endif
</div>

