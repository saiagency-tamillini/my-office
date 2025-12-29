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
        Schema::create('payment_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('part_sale_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('bill_no')->nullable();
            $table->date('payment_date')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('cd')->nullable();
            $table->string('product_return')->nullable();
            $table->string('online_payment')->nullable();
            $table->decimal('amount_received', 12, 2)->nullable();
            $table->decimal('balance', 12, 2)->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();
            
            $table->foreign('part_sale_id')->references('id')->on('party_sales')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->index(['part_sale_id', 'id'], 'idx_pe_part_sale_id_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_entries');
    }
};
