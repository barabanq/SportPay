<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('avatar_path', 255)->nullable()->after('phone');
            $table->timestamp('last_seen_at')->nullable()->index()->after('status');
        });

        Schema::table('lots', function (Blueprint $table): void {
            $table->string('image_path', 255)->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['avatar_path', 'last_seen_at']);
        });

        Schema::table('lots', function (Blueprint $table): void {
            $table->dropColumn('image_path');
        });
    }
};
