<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Кто написал (покупатель, продавец или админ)
            $table->text('body');
            $table->boolean('is_system')->default(false); // Системное сообщение (например: "Заказ создан", "Деньги заморожены")
            $table->timestamps();

            // Индекс для мгновенной выборки чата по конкретному заказу
            $table->index(['order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};