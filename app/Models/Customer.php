<?php

namespace App\Models;

use App\Services\Phone\PhoneNormalizationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'city',
        'district',
        'address',
        'note',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Set phone attribute with normalization.
     */
    public function setPhoneAttribute(?string $value): void
    {
        $normalizer = app(PhoneNormalizationService::class);
        $this->attributes['phone'] = $normalizer->normalizeForStorage($value);
    }

    /**
     * Find customer by phone number (with normalization).
     * 
     * @param  string|null  $phone  Phone number in any format
     * @return Customer|null
     */
    public static function findByPhone(?string $phone): ?self
    {
        $normalizer = app(PhoneNormalizationService::class);
        $normalizedPhone = $normalizer->normalizeForStorage($phone);

        if (!$normalizedPhone) {
            return null;
        }

        return static::where('phone', $normalizedPhone)->first();
    }
}
