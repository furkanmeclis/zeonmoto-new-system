<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentLink extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'paytr_link_id',
        'order_id',
        'link_url',
        'name',
        'price',
        'currency',
        'link_type',
        'max_installment',
        'expiry_date',
        'status',
        'merchant_oid',
        'customer_email',
        'customer_phone',
        'callback_data',
        'callback_received_at',
        'sms_sent_at',
        'email_sent_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'expiry_date' => 'datetime',
        'callback_data' => 'array',
        'callback_received_at' => 'datetime',
        'sms_sent_at' => 'datetime',
        'email_sent_at' => 'datetime',
    ];

    /**
     * Get the order that owns the payment link.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Check if the link is expired.
     */
    public function isExpired(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }

        return $this->expiry_date->isPast();
    }

    /**
     * Check if SMS can be sent.
     */
    public function canSendSms(): bool
    {
        return !empty($this->customer_phone) && $this->status === 'pending' && !$this->isExpired();
    }

    /**
     * Check if email can be sent.
     */
    public function canSendEmail(): bool
    {
        return !empty($this->customer_email) && $this->status === 'pending' && !$this->isExpired();
    }

    /**
     * Get status badge color for Filament.
     */
    public function getStatusBadgeColor(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'paid' => 'success',
            'expired' => 'gray',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }
}
