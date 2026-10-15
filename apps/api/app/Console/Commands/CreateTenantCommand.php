<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

final class CreateTenantCommand extends Command
{
    protected $signature = 'ticko:tenant:create
        {slug : URL-safe identifier, sent by clients in the X-Tenant header}
        {name : Display name}
        {--admin-email= : E-mail of the first admin}
        {--admin-name=Administrator : Name of the first admin}';

    protected $description = 'Create a tenant and its first admin (prints a one-time random password)';

    public function handle(TenantContext $tenants): int
    {
        $text = fn (mixed $value): string => is_string($value) ? $value : '';
        $data = [
            'slug' => $text($this->argument('slug')),
            'name' => $text($this->argument('name')),
            'email' => $text($this->option('admin-email')),
        ];

        $validator = Validator::make($data, [
            'slug' => ['required', 'string', 'max:63', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:tenants,slug'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:254'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $password = Str::password(20);

        DB::transaction(function () use ($tenants, $data, $password, $text): void {
            $tenant = Tenant::create(['slug' => $data['slug'], 'name' => $data['name']]);

            $tenants->run($tenant, fn () => User::create([
                'name' => $text($this->option('admin-name')),
                'email' => mb_strtolower($data['email']),
                'password' => $password,
                'role' => Role::Admin,
            ]));
        });

        $this->info("Tenant [{$data['slug']}] created.");
        $this->line("Admin: {$data['email']}");
        $this->line("Password (shown once, change it after the first login): {$password}");

        return self::SUCCESS;
    }
}
