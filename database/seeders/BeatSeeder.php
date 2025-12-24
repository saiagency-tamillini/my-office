<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Beat;

class BeatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
            foreach (config('constants.beats') as $beatName => $salesman) {
            Beat::updateOrCreate(
                ['name' => $beatName],
                ['salesman' => $salesman]
            );
        }
    }
}
