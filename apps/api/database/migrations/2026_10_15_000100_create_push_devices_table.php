<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Push notifications: the devices (Expo push tokens) of each user and the
 * user's notification preferences.
 *
 * A device belongs to the access token that registered it, so signing out
 * (revoking the token) or the token expiring and being pruned also stops
 * the pushes to that phone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('push_replies')->default(true);
            $table->boolean('push_status_changes')->default(true);
        });

        Schema::create('push_devices', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->ulid('user_id');
            $table->foreignId('personal_access_token_id')->constrained()->cascadeOnDelete();
            $table->string('token', 255);
            $table->string('platform', 16);
            $table->string('locale', 8);
            $table->timestamps();

            $table->foreign(['tenant_id', 'user_id'])->references(['tenant_id', 'id'])->on('users')->cascadeOnDelete();

            $table->unique(['tenant_id', 'token']);
            $table->index(['tenant_id', 'user_id']);
        });

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Same Row-Level Security policy as the other tenant-owned tables.
        DB::statement('ALTER TABLE push_devices ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE push_devices FORCE ROW LEVEL SECURITY');
        DB::statement(<<<'SQL'
            CREATE POLICY tenant_isolation ON push_devices
                USING (tenant_id = NULLIF(current_setting('app.tenant_id', true), ''))
                WITH CHECK (tenant_id = NULLIF(current_setting('app.tenant_id', true), ''))
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('push_devices');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['push_replies', 'push_status_changes']);
        });
    }
};
