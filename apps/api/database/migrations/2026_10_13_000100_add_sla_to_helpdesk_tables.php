<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SLA: per-category targets (falling back to config/ticko.php) and the
 * deadlines each ticket was given when it was opened.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->jsonb('sla_targets')->nullable();
        });

        Schema::table('tickets', function (Blueprint $table): void {
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('first_response_due_at')->nullable();
            $table->timestamp('resolution_due_at')->nullable();

            $table->index(['tenant_id', 'first_response_due_at']);
            $table->index(['tenant_id', 'resolution_due_at']);
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'first_response_due_at']);
            $table->dropIndex(['tenant_id', 'resolution_due_at']);
            $table->dropColumn(['first_response_at', 'first_response_due_at', 'resolution_due_at']);
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropColumn('sla_targets');
        });
    }
};
