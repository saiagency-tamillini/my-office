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
            $table->boolean('party_sale_payment')
                  ->default(false)
                  ->after('remarks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_entries', function (Blueprint $table) {
            $table->dropColumn('party_sale_payment');
        });
    }
};
