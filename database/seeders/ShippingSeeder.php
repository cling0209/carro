<?php

namespace Database\Seeders;

use App\Models\ShippingSetting;
use App\Models\ShippingWeightRate;
use Illuminate\Database\Seeder;

class ShippingSeeder extends Seeder
{
    public function run(): void
    {
        ShippingSetting::setValue('rm_flat_rate', 3990);
        ShippingSetting::setValue('default_product_weight_kg', 1.0);

        if (ShippingWeightRate::query()->exists()) {
            return;
        }

        $bands = [
            ['label' => 'Hasta 1 kg', 'min' => 0, 'max' => 1, 'price' => 4990, 'sort' => 1],
            ['label' => '1 a 3 kg', 'min' => 1, 'max' => 3, 'price' => 6990, 'sort' => 2],
            ['label' => '3 a 5 kg', 'min' => 3, 'max' => 5, 'price' => 8990, 'sort' => 3],
            ['label' => 'Más de 5 kg', 'min' => 5, 'max' => null, 'price' => 11990, 'sort' => 4],
        ];

        foreach ($bands as $band) {
            ShippingWeightRate::create([
                'label' => $band['label'],
                'min_weight_kg' => $band['min'],
                'max_weight_kg' => $band['max'],
                'price' => $band['price'],
                'is_active' => true,
                'sort_order' => $band['sort'],
            ]);
        }
    }
}
