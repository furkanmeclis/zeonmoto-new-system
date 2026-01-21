<?php

namespace App\Models;

use App\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'order_no',
        'customer_id',
        'status',
        'total_amount', // Deprecated, use total instead
        'total_discount', // Deprecated
        'admin_status', // Deprecated, use status instead
        'subtotal',
        'total',
        'shipping_cost',
        'currency',
        'payment_method',
        'payment_link_id',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'total_amount' => 'decimal:2',
        'total_discount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_no)) {
                $order->order_no = static::generateOrderNumber();
            }
            
            if (empty($order->status)) {
                $order->status = OrderStatus::New;
            }
            
            if (empty($order->currency)) {
                $order->currency = 'TRY';
            }
        });
    }

    /**
     * Generate unique order number.
     */
    protected static function generateOrderNumber(): string
    {
        $prefix = 'ORD-';
        $date = now()->format('Ymd');
        $lastOrder = static::where('order_no', 'like', "{$prefix}{$date}%")
            ->orderBy('order_no', 'desc')
            ->first();

        if ($lastOrder) {
            $lastNumber = (int) substr($lastOrder->order_no, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $date . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function paymentLink(): HasOne
    {
        return $this->hasOne(PaymentLink::class);
    }

    /**
     * Get total items count.
     */
    public function getTotalItemsAttribute(): int
    {
        return $this->orderItems()->sum('quantity');
    }

    /**
     * Recalculate order totals based on order items.
     * This method ensures totals are always correct after save operations.
     */
    public function recalculateTotals(): void
    {
        // Refresh order items relationship
        $this->load('orderItems');

        // Calculate subtotal from all order items
        $subtotal = 0;
        foreach ($this->orderItems as $item) {
            // Use line_total if available, otherwise calculate from unit_price_snapshot and quantity
            if ($item->line_total !== null) {
                $subtotal += round($item->line_total, 2);
            } elseif ($item->unit_price_snapshot !== null) {
                $lineTotal = round($item->unit_price_snapshot * $item->quantity, 2);
                // Update line_total if it's missing
                $item->update(['line_total' => $lineTotal]);
                $subtotal += $lineTotal;
            } else {
                // Fallback to deprecated fields
                $lineTotal = round(($item->unit_price ?? 0) * $item->quantity, 2);
                $subtotal += $lineTotal;
            }
        }

        // Get total discount
        $totalDiscount = round($this->total_discount ?? 0, 2);

        // Get shipping cost
        $shippingCost = round($this->shipping_cost ?? 0, 2);

        // Calculate final total (subtotal - discount + shipping)
        $total = round(max(0, $subtotal - $totalDiscount + $shippingCost), 2);

        // Update order totals
        $this->update([
            'subtotal' => round($subtotal, 2),
            'total' => $total,
            'total_amount' => $total, // Backward compatibility
        ]);
    }
}
