<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'telegram_chat_id')) {
                $table->string('telegram_chat_id', 64)->nullable()->unique()->after('email');
            }
            if (!Schema::hasColumn('users', 'telegram_username')) {
                $table->string('telegram_username', 64)->nullable()->after('telegram_chat_id');
            }
            if (!Schema::hasColumn('users', 'telegram_link_token')) {
                $table->string('telegram_link_token', 64)->nullable()->unique()->after('telegram_username');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['telegram_chat_id', 'telegram_username', 'telegram_link_token']);
        });
    }
};
