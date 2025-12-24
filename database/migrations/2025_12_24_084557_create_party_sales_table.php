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
        Schema::create('party_sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('beat_id');
            $table->integer('s_no')->nullable();
            $table->string('customer_name');
            $table->string('bill_no')->nullable()->unique();
            $table->date('bill_date')->nullable();
            $table->string('aging')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('cd')->nullable();
            $table->string('product_return')->nullable();
            $table->string('online_payment')->nullable();
            $table->decimal('amount_received', 12, 2)->nullable();
            $table->decimal('balance', 12, 2)->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();


            $table->foreign('beat_id')->references('id')->on('beats')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('party_sales');
    }
};
