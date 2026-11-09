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
            if (!Schema::hasColumn('users', 'tg_notify_messages')) {
                $table->boolean('tg_notify_messages')->default(true)->after('telegram_link_token');
            }
            if (!Schema::hasColumn('users', 'tg_subscribed_categories')) {
                $table->json('tg_subscribed_categories')->nullable()->after('tg_notify_messages');
            }
            if (!Schema::hasColumn('users', 'tg_radar_lat')) {
                $table->decimal('tg_radar_lat', 10, 7)->nullable()->after('tg_subscribed_categories');
            }
            if (!Schema::hasColumn('users', 'tg_radar_lng')) {
                $table->decimal('tg_radar_lng', 10, 7)->nullable()->after('tg_radar_lat');
            }
            if (!Schema::hasColumn('users', 'tg_radar_radius_km')) {
                $table->unsignedInteger('tg_radar_radius_km')->nullable()->after('tg_radar_lng');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'tg_notify_messages',
                'tg_subscribed_categories',
                'tg_radar_lat',
                'tg_radar_lng',
                'tg_radar_radius_km',
            ]);
        });
    }
};
