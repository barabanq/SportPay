<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('lots', function (Blueprint $table): void {
            $table->string('location_name', 255)->nullable()->after('description');
            $table->decimal('latitude', 10, 7)->nullable()->after('location_name');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS postgis;');
            DB::statement('ALTER TABLE lots ADD COLUMN IF NOT EXISTS coordinates geography(Point, 4326);');
            DB::statement('CREATE INDEX IF NOT EXISTS lots_coordinates_gist_idx ON lots USING GIST (coordinates);');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS lots_coordinates_gist_idx;');
            DB::statement('ALTER TABLE lots DROP COLUMN IF EXISTS coordinates;');
        }

        Schema::table('lots', function (Blueprint $table): void {
            $table->dropColumn(['location_name', 'latitude', 'longitude']);
        });
    }
};
