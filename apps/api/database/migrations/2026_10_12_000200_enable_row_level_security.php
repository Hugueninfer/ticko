<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Row-Level Security: every tenant-owned table only exposes rows whose
 * tenant_id matches the `app.tenant_id` setting of the current connection.
 *
 * FORCE makes the policy apply to the table owner too, so the application
 * role (which owns the schema and is neither superuser nor BYPASSRLS) can
 * never read or write another tenant's rows, even through raw SQL.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $tables = ['users', 'categories', 'tickets', 'ticket_messages', 'attachments'];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

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
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->tables as $table) {
            DB::statement("DROP POLICY IF EXISTS tenant_isolation ON {$table}");
            DB::statement("ALTER TABLE {$table} NO FORCE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
        }
    }
};
