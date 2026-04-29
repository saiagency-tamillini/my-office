<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  
    public function up(): void
    {
        DB::table('roles')->insert([
            [
                'name' => 'accountant',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'mis_access',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('roles')->whereIn('name', ['accountant', 'mis_access'])->delete();
    }
};
