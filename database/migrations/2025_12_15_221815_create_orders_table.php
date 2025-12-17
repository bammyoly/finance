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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('symbol', 10);
            $table->enum('side', ['buy', 'sell']);
            $table->decimal('price', 18, 2);
            $table->decimal('amount', 36, 18);
            // 1=open,2=filled,3=cancelled
            $table->tinyInteger('status')->default(1);
            $table->decimal('locked_usd', 18, 2)->nullable();
            $table->timestamps();
        

            $table->index(['symbol', 'status', 'side', 'price', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
