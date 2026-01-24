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
        Schema::create('manual_bill_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_sale_id')->constrained('party_sales')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');

            $table->unsignedInteger('box')->default(0);
            $table->unsignedInteger('pcs')->default(0);
            $table->timestamps();
            $table->unique(['party_sale_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manual_bill_items');
    }
};
