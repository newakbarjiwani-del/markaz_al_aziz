<?php

namespace Database\Factories;

use App\Models\QrisPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QrisPayment>
 */
class QrisPaymentFactory extends Factory
{
    protected $model = QrisPayment::class;

    public function definition(): array
    {
        return [
            'vano' => '111111'.fake()->numerify('########'),
            'amount' => 500000,
            'qris_id' => fake()->unique()->numerify('########'),
            'transaction_id' => fake()->numerify('########'),
            'account_no' => '5080010295',
            'mitra_customer_id' => 'ISLAMIC CENTER SMG451061',
            'raw_qr_data' => '000201010212TEST',
            'status' => QrisPayment::STATUS_PENDING,
            'paid_flag' => false,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => QrisPayment::STATUS_PAID,
            'paid_flag' => true,
            'paid_at' => now(),
        ]);
    }
}
