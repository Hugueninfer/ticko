<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Token usage and estimated cost of each reply suggestion (observability:
 * cost per tenant). The prompt and the answer are never stored here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_suggestions', function (Blueprint $table): void {
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->decimal('cost_usd', 12, 6)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ai_suggestions', function (Blueprint $table): void {
            $table->dropColumn(['input_tokens', 'output_tokens', 'cost_usd']);
        });
    }
};
