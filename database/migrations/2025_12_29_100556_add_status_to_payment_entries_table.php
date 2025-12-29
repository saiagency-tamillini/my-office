<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payment_entries', function (Blueprint $table) {
            $table->enum('status', ['pending', 'complete', 'cancel'])->default('pending')->after('balance');
        });
        DB::table('payment_entries')
            ->where('balance', 0)
            ->update(['status' => 'complete']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_entries', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
