<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Automatic ticket classification.
 *
 *  - tickets: the customer's sentiment, and who set category, priority and
 *    sentiment (requester, ai or staff), so the AI never overwrites a person.
 *  - ticket_classifications: every result from the classifier service.
 *  - classification_corrections: what an agent changed in an AI result.
 *  - audit_events: append-only trail of automatic decisions and corrections.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $tables = ['ticket_classifications', 'classification_corrections', 'audit_events'];

    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->string('sentiment', 16)->nullable();
            $table->string('category_source', 16)->nullable();
            $table->string('priority_source', 16)->nullable();
            $table->string('sentiment_source', 16)->nullable();
        });

        Schema::create('ticket_classifications', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->ulid('ticket_id');
            $table->string('status', 16);
            $table->string('model', 64)->nullable();
            $table->string('category_label', 32)->nullable();
            $table->decimal('category_confidence', 4, 3)->nullable();
            $table->string('priority', 16)->nullable();
            $table->decimal('priority_confidence', 4, 3)->nullable();
            $table->string('sentiment', 16)->nullable();
            $table->decimal('sentiment_confidence', 4, 3)->nullable();
            $table->jsonb('applied');
            $table->jsonb('flags');
            $table->string('error_code', 32)->nullable();
            $table->timestamps();

            $table->foreign(['tenant_id', 'ticket_id'])->references(['tenant_id', 'id'])->on('tickets')->cascadeOnDelete();
            $table->unique(['tenant_id', 'id']);
            $table->index(['tenant_id', 'ticket_id', 'created_at']);
        });

        Schema::create('classification_corrections', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->ulid('ticket_id');
            $table->ulid('classification_id')->nullable();
            $table->ulid('user_id');
            $table->string('field', 16);
            $table->string('ai_value', 64)->nullable();
            $table->string('corrected_value', 64)->nullable();
            $table->timestamps();

            $table->foreign(['tenant_id', 'ticket_id'])->references(['tenant_id', 'id'])->on('tickets')->cascadeOnDelete();
            $table->foreign(['tenant_id', 'classification_id'])->references(['tenant_id', 'id'])->on('ticket_classifications')->nullOnDelete();
            $table->foreign(['tenant_id', 'user_id'])->references(['tenant_id', 'id'])->on('users');
            $table->index(['tenant_id', 'field', 'created_at']);
        });

        Schema::create('audit_events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->ulid('actor_id')->nullable();
            $table->string('action', 64);
            $table->string('subject_type', 32);
            $table->ulid('subject_id');
            $table->jsonb('data');
            $table->timestamp('created_at');

            $table->foreign(['tenant_id', 'actor_id'])->references(['tenant_id', 'id'])->on('users');
            $table->index(['tenant_id', 'subject_type', 'subject_id', 'created_at']);
            $table->index(['tenant_id', 'action', 'created_at']);
        });

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // A nullable column inside a composite key: clearing it keeps the tenant.
        DB::statement('ALTER TABLE classification_corrections DROP CONSTRAINT classification_corrections_tenant_id_classification_id_foreign');
        DB::statement('ALTER TABLE classification_corrections ADD CONSTRAINT classification_corrections_classification_fk
            FOREIGN KEY (tenant_id, classification_id) REFERENCES ticket_classifications (tenant_id, id) ON DELETE SET NULL (classification_id)');

        foreach ($this->tables as $table) {
            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY");
            DB::statement(<<<SQL
                CREATE POLICY tenant_isolation ON {$table}
                    USING (tenant_id = NULLIF(current_setting('app.tenant_id', true), ''))
                    WITH CHECK (tenant_id = NULLIF(current_setting('app.tenant_id', true), ''))
                SQL);
        }

        // Audit events are never rewritten (they can only go away with their tenant).
        DB::statement('CREATE OR REPLACE FUNCTION audit_events_append_only() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN RAISE EXCEPTION \'audit_events cannot be changed\'; END $$');
        DB::statement('CREATE TRIGGER audit_events_no_update BEFORE UPDATE ON audit_events
            FOR EACH ROW EXECUTE FUNCTION audit_events_append_only()');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP TRIGGER IF EXISTS audit_events_no_update ON audit_events');
            DB::statement('DROP FUNCTION IF EXISTS audit_events_append_only()');
        }

        Schema::dropIfExists('audit_events');
        Schema::dropIfExists('classification_corrections');
        Schema::dropIfExists('ticket_classifications');

        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropColumn(['sentiment', 'category_source', 'priority_source', 'sentiment_source']);
        });
    }
};
