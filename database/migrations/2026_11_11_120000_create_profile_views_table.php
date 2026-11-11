<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('profile_views')) {
            return;
        }

        Schema::create('profile_views', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('profile_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('viewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 40)->default('profile_page')->index();
            $table->string('ip_hash', 64)->index();
            $table->timestamps();

            $table->index(['profile_user_id', 'created_at']);
            $table->index(['profile_user_id', 'viewer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_views');
    }
};