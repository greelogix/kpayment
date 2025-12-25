<?php

namespace Greelogix\KPayment\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $table = 'kpayment_payment_methods';

    protected $fillable = [
        'code',
        'name',
        'name_ar',
        'description',
        'is_active',
        'is_ios_enabled',
        'is_android_enabled',
        'is_web_enabled',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_ios_enabled' => 'boolean',
        'is_android_enabled' => 'boolean',
        'is_web_enabled' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Scope for active payment methods
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for platform-specific payment methods
     */
    public function scopeActiveForPlatform($query, string $platform)
    {
        $column = match(strtolower($platform)) {
            'ios' => 'is_ios_enabled',
            'android' => 'is_android_enabled',
            'web' => 'is_web_enabled',
            default => 'is_web_enabled',
        };

        return $query->where('is_active', true)
            ->where($column, true)
            ->orderBy('sort_order');
    }
}


