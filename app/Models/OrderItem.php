<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'unit_price', // Deprecated, use unit_price_snapshot
        'total_price', // Deprecated, use line_total
        'line_discount', // Deprecated
        // Snapshot fields (immutable after order creation)
        'product_name_snapshot',
        'sku_snapshot',
        'unit_price_snapshot',
        'line_total',
        'price_rules_snapshot',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'line_discount' => 'decimal:2',
        'unit_price_snapshot' => 'decimal:2',
        'line_total' => 'decimal:2',
        'price_rules_snapshot' => 'array',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Prevent updates to snapshot fields after creation
        static::updating(function ($orderItem) {
            $snapshotFields = [
                'product_name_snapshot',
                'sku_snapshot',
                'unit_price_snapshot',
                'line_total',
                'price_rules_snapshot',
            ];

            foreach ($snapshotFields as $field) {
                if ($orderItem->isDirty($field)) {
                    // Revert to original value
                    $orderItem->{$field} = $orderItem->getOriginal($field);
                }
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get snapshot price rules as array.
     */
    public function getPriceRulesSnapshotAttribute($value): array
    {
        if (is_string($value)) {
            return json_decode($value, true) ?? [];
        }

        return $value ?? [];
    }

    /**
     * Set snapshot price rules.
     */
    public function setPriceRulesSnapshotAttribute($value): void
    {
        $this->attributes['price_rules_snapshot'] = is_array($value)
            ? json_encode($value)
            : $value;
    }
}
