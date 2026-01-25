<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('roles')->upsert([
            ['name' => 'super_admin', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'admin',       'created_at' => now(), 'updated_at' => now()],
            ['name' => 'sales_man',       'created_at' => now(), 'updated_at' => now()],
            ['name' => 'employee',       'created_at' => now(), 'updated_at' => now()],
            ['name' => 'guest',       'created_at' => now(), 'updated_at' => now()],
        ], ['name'], ['updated_at']);
    }
}
