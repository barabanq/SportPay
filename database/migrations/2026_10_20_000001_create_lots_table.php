<?php

use App\Enums\LotStatus;
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
        Schema::create('lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 255);
            $table->string('product_type', 100)->index();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->integer('stock_quantity')->default(1);
            $table->string('status', 30)->default(LotStatus::Active->value)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lots');
    }
};
