<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Knowledge base and AI reply suggestions.
 *
 *  - kb_articles: Markdown articles. Agents edit `body`; publishing copies it
 *    to `published_title`/`published_body`, which is what gets indexed, so
 *    the AI keeps citing the published version while a draft is edited.
 *  - kb_chunks: the indexed passages of the published version, with a
 *    768-dimension embedding (pgvector, HNSW, cosine) and a Portuguese
 *    full-text vector, for hybrid search.
 *  - ai_suggestions: reply drafts written by the AI for an agent to approve,
 *    edit or discard. Nothing reaches the customer without an agent.
 *
 * Every table is tenant-owned, with forced Row-Level Security; queries also
 * filter by tenant_id explicitly.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $tables = ['kb_articles', 'kb_chunks', 'ai_suggestions'];

    public function up(): void
    {
        Schema::create('kb_articles', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->ulid('category_id')->nullable();
            $table->ulid('author_id');
            $table->ulid('updated_by_id');
            $table->string('title', 200);
            $table->text('body');
            $table->string('visibility', 16);
            $table->jsonb('tags');
            $table->string('status', 16);
            $table->string('published_title', 200)->nullable();
            $table->text('published_body')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('index_status', 16);
            $table->unsignedSmallInteger('index_progress')->default(0);
            $table->string('index_error', 64)->nullable();
            $table->timestamp('indexed_at')->nullable();
            $table->unsignedInteger('chunks_count')->default(0);
            $table->unsignedInteger('citations_count')->default(0);
            $table->timestamps();

            $table->foreign(['tenant_id', 'author_id'])->references(['tenant_id', 'id'])->on('users');
            $table->foreign(['tenant_id', 'updated_by_id'])->references(['tenant_id', 'id'])->on('users');

            $table->unique(['tenant_id', 'id']);
            $table->index(['tenant_id', 'status', 'updated_at']);
        });

        Schema::create('kb_chunks', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->ulid('article_id');
            $table->unsignedSmallInteger('ordinal');
            $table->string('heading', 200);
            $table->text('content');
            $table->unsignedSmallInteger('tokens');
            $table->string('embedding_model', 100);
            $table->vector('embedding', dimensions: 768);
            $table->unsignedInteger('citations_count')->default(0);
            $table->timestamps();

            $table->foreign(['tenant_id', 'article_id'])->references(['tenant_id', 'id'])->on('kb_articles')->cascadeOnDelete();

            $table->unique(['tenant_id', 'id']);
            $table->unique(['article_id', 'ordinal']);
            $table->index(['tenant_id', 'article_id']);
        });

        Schema::create('ai_suggestions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->ulid('ticket_id');
            $table->ulid('requested_by_id');
            $table->string('status', 16);
            $table->text('body')->nullable();
            $table->decimal('confidence', 4, 3)->nullable();
            $table->jsonb('citations');
            $table->jsonb('candidates');
            $table->string('provider', 32)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('error_code', 32)->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->ulid('reviewed_by_id')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->ulid('message_id')->nullable();
            $table->boolean('edited')->default(false);
            $table->string('discard_reason', 32)->nullable();
            $table->timestamp('gap_reported_at')->nullable();
            $table->timestamps();

            $table->foreign(['tenant_id', 'ticket_id'])->references(['tenant_id', 'id'])->on('tickets')->cascadeOnDelete();
            $table->foreign(['tenant_id', 'requested_by_id'])->references(['tenant_id', 'id'])->on('users');
            $table->foreign(['tenant_id', 'reviewed_by_id'])->references(['tenant_id', 'id'])->on('users');
            $table->foreign(['tenant_id', 'message_id'])->references(['tenant_id', 'id'])->on('ticket_messages');

            $table->index(['tenant_id', 'ticket_id', 'created_at']);
            $table->index(['tenant_id', 'gap_reported_at']);
        });

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Deleting a category keeps its articles (only the category is cleared).
        DB::statement('ALTER TABLE kb_articles ADD CONSTRAINT kb_articles_category_fk
            FOREIGN KEY (tenant_id, category_id) REFERENCES categories (tenant_id, id) ON DELETE SET NULL (category_id)');

        // Full-text vector (Portuguese stemming) kept in sync by Postgres.
        DB::statement("ALTER TABLE kb_chunks ADD COLUMN search_vector tsvector
            GENERATED ALWAYS AS (to_tsvector('portuguese', heading || ' ' || content)) STORED");
        DB::statement('CREATE INDEX kb_chunks_search_vector_index ON kb_chunks USING gin (search_vector)');
        DB::statement('CREATE INDEX kb_chunks_embedding_index ON kb_chunks USING hnsw (embedding vector_cosine_ops)');

        foreach ($this->tables as $table) {
            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY");
            DB::statement(<<<SQL
                CREATE POLICY tenant_isolation ON {$table}
                    USING (tenant_id = NULLIF(current_setting('app.tenant_id', true), ''))
                    WITH CHECK (tenant_id = NULLIF(current_setting('app.tenant_id', true), ''))
                SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_suggestions');
        Schema::dropIfExists('kb_chunks');
        Schema::dropIfExists('kb_articles');
    }
};
