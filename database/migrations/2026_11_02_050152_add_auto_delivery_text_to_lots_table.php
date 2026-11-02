<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lots', function (Blueprint $table): void {
            $table->text('auto_delivery_text')
                ->nullable()
                ->after('stock_quantity')
                ->comment('Секретный текст/ссылка для автоматической выдачи после оплаты');
        });
    }

    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table): void {
            $table->dropColumn('auto_delivery_text');
        });
    }
};