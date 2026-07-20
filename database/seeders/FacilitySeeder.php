<?php

namespace Database\Seeders;

use Botble\Base\Supports\BaseSeeder;
use Botble\RealEstate\Models\Facility;

class FacilitySeeder extends BaseSeeder
{
    public function run(): void
    {
        Facility::query()->truncate();

        $now = $this->now();

        $facilities = [
            [
                'name' => 'Hospital',
                'icon' => 'ti ti-hospital',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Super Market',
                'icon' => 'ti ti-shopping-cart',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'School',
                'icon' => 'ti ti-school',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Entertainment',
                'icon' => 'ti ti-movie',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Pharmacy',
                'icon' => 'ti ti-pill',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Airport',
                'icon' => 'ti ti-plane-departure',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Railways',
                'icon' => 'ti ti-train',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Bus Stop',
                'icon' => 'ti ti-bus',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Beach',
                'icon' => 'ti ti-beach',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Mall',
                'icon' => 'ti ti-shopping-cart',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Bank',
                'icon' => 'ti ti-building-bank',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        Facility::query()->insert($facilities);
    }
}
