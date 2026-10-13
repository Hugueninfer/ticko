<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('slug', 80);
            $table->string('description', 500)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
            $table->unique(['tenant_id', 'id']);
        });

        Schema::create('tickets', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->ulid('requester_id');
            $table->ulid('assignee_id')->nullable();
            $table->ulid('category_id')->nullable();
            $table->string('subject', 200);
            $table->text('description');
            $table->string('status', 16);
            $table->string('priority', 16);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            // Composite keys guarantee related rows belong to the same tenant.
            $table->foreign(['tenant_id', 'requester_id'])->references(['tenant_id', 'id'])->on('users');
            $table->foreign(['tenant_id', 'assignee_id'])->references(['tenant_id', 'id'])->on('users');
            $table->foreign(['tenant_id', 'category_id'])->references(['tenant_id', 'id'])->on('categories');

            $table->unique(['tenant_id', 'id']);
            $table->index(['tenant_id', 'status', 'created_at']);
            $table->index(['tenant_id', 'requester_id']);
            $table->index(['tenant_id', 'assignee_id']);
        });

        Schema::create('ticket_messages', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->ulid('ticket_id');
            $table->ulid('author_id');
            $table->text('body');
            $table->boolean('is_internal')->default(false);
            $table->timestamps();

            $table->foreign(['tenant_id', 'ticket_id'])->references(['tenant_id', 'id'])->on('tickets')->cascadeOnDelete();
            $table->foreign(['tenant_id', 'author_id'])->references(['tenant_id', 'id'])->on('users');

            $table->unique(['tenant_id', 'id']);
            $table->index(['tenant_id', 'ticket_id', 'created_at']);
        });

        Schema::create('attachments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->ulid('ticket_id');
            $table->ulid('uploader_id');
            $table->string('disk', 32);
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 64);
            $table->unsignedInteger('size');
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->char('checksum', 64);
            $table->timestamps();

            $table->foreign(['tenant_id', 'ticket_id'])->references(['tenant_id', 'id'])->on('tickets')->cascadeOnDelete();
            $table->foreign(['tenant_id', 'uploader_id'])->references(['tenant_id', 'id'])->on('users');

            $table->index(['tenant_id', 'ticket_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('categories');
    }
};
