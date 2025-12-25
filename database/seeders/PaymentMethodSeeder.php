<?php

namespace Greelogix\KPayment\Database\Seeders;

use Illuminate\Database\Seeder;
use Greelogix\KPayment\Models\PaymentMethod;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paymentMethods = [
            [
                'code' => 'KNET',
                'name' => 'KNET Card',
                'name_ar' => 'بطاقة كي نت',
                'description' => 'KNET local payment card',
                'is_active' => true,
                'is_ios_enabled' => true,
                'is_android_enabled' => true,
                'is_web_enabled' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'VISA',
                'name' => 'Visa',
                'name_ar' => 'فيزا',
                'description' => 'Visa credit/debit card',
                'is_active' => true,
                'is_ios_enabled' => true,
                'is_android_enabled' => true,
                'is_web_enabled' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'MASTERCARD',
                'name' => 'Mastercard',
                'name_ar' => 'ماستركارد',
                'description' => 'Mastercard credit/debit card',
                'is_active' => true,
                'is_ios_enabled' => true,
                'is_android_enabled' => true,
                'is_web_enabled' => true,
                'sort_order' => 3,
            ],
            [
                'code' => 'APPLE_PAY',
                'name' => 'Apple Pay',
                'name_ar' => 'آبل باي',
                'description' => 'Apple Pay payment method',
                'is_active' => false, // Enable in admin if Apple Pay is configured
                'is_ios_enabled' => true,
                'is_android_enabled' => false,
                'is_web_enabled' => false,
                'sort_order' => 4,
            ],
            [
                'code' => 'KFAST',
                'name' => 'KFAST',
                'name_ar' => 'كي فاست',
                'description' => 'KNET Fast Payment',
                'is_active' => false, // Enable in admin if KFAST is enabled
                'is_ios_enabled' => true,
                'is_android_enabled' => true,
                'is_web_enabled' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($paymentMethods as $method) {
            PaymentMethod::updateOrCreate(
                ['code' => $method['code']],
                $method
            );
        }

        $this->command->info('Payment methods seeded successfully.');
    }
}


