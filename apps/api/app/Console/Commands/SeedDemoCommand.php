<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Role;
use App\Enums\TicketPriority;
use App\Models\Category;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SeedDemoCommand extends Command
{
    protected $signature = 'ticko:demo';

    protected $description = 'Create a demo tenant ("acme") with one user per role and print their random passwords';

    public function handle(TenantContext $tenants): int
    {
        if ($this->laravel->isProduction()) {
            $this->error('Refusing to seed demo data in production.');

            return self::FAILURE;
        }

        if (Tenant::where('slug', 'acme')->exists()) {
            $this->warn('The demo tenant "acme" already exists. Nothing to do.');

            return self::SUCCESS;
        }

        $rows = [];

        DB::transaction(function () use ($tenants, &$rows): void {
            $tenant = Tenant::create(['slug' => 'acme', 'name' => 'Acme Inc.']);

            $tenants->run($tenant, function () use (&$rows): void {
                $users = [];

                foreach ([Role::Admin, Role::Agent, Role::Customer] as $role) {
                    $password = Str::password(20);
                    $users[$role->value] = User::create([
                        'name' => Str::title($role->value).' Demo',
                        'email' => $role->value.'@acme.test',
                        'password' => $password,
                        'role' => $role,
                    ]);
                    $rows[] = [$role->value, $role->value.'@acme.test', $password];
                }

                $hardware = Category::create(['name' => 'Hardware', 'slug' => 'hardware', 'description' => 'Laptops, printers and peripherals']);
                Category::create(['name' => 'Access', 'slug' => 'access', 'description' => 'Accounts, passwords and permissions']);

                $ticket = new Ticket([
                    'subject' => 'Printer on the 3rd floor is jammed',
                    'description' => 'It shows error E-42 since this morning.',
                    'priority' => TicketPriority::High,
                    'category_id' => $hardware->id,
                ]);
                $ticket->requester_id = $users['customer']->id;
                $ticket->save();
            });
        });

        $this->info('Demo tenant created. Send "X-Tenant: acme" when logging in.');
        $this->table(['Role', 'E-mail', 'Password'], $rows);

        return self::SUCCESS;
    }
}
