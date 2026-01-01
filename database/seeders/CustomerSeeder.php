<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Beat;
use App\Models\Customer;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (config('constants.customers') as $beatName => $customers) {

            $beat = Beat::where('name', $beatName)->first();

            if (!$beat) {
                continue; 
            }

            foreach ($customers as $customerName) {
                Customer::updateOrCreate(
                    [
                        'name' => $customerName,
                        'beat_id' => $beat->id,     
                    ]
                );
            }
        }
    }
}
