<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Not tenant-scoped by RLS: the tenant of a bearer token must be readable
        // before the request's tenant is known. Only SHA-256 hashes are stored.
        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->ulidMorphs('tokenable');
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
