<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $names = [
            'surfexcel bar RS 10',
            'surfexcel bar RS 20',
            'surfexcel bar RS 35',
            'surfexcel QW powder RS 10',
        ];

        foreach ($names as $name) {
            Product::firstOrCreate(['name' => $name]);
        }
    }
}
