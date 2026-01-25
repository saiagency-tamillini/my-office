<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssignDefaultRoleToUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $guestRoleId = DB::table('roles')->where('name', 'guest')->value('id');

        if (!$guestRoleId) return;

        DB::table('users')
            ->whereNull('role_id')
            ->update(['role_id' => $guestRoleId]);
    }
}
