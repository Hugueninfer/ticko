<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Role;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SeedDemoCommand extends Command
{
    /** @var array<string, string> */
    private const array CATEGORIES = [
        'Entrega' => 'Atrasos, rastreio parado, endereço errado ou produto avariado.',
        'Pagamento' => 'Cobranças, estornos e parcelamento.',
        'Troca e devolução' => 'Trocas de tamanho, devoluções e reembolsos.',
        'Conta e acesso' => 'Login, senha e dados cadastrais.',
    ];

    /**
     * Subject, description, category, priority, opened (hours ago), assigned to the demo agent, first staff reply.
     *
     * @var list<array{string, string, string, TicketPriority, int, bool, string|null}>
     */
    private const array TICKETS = [
        ['Cobrança em duplicidade no cartão de crédito', 'Fui cobrado duas vezes pelo pedido #88190.', 'Pagamento', TicketPriority::Urgent, 2, true, null],
        ['Pedido #88213 não chegou e o rastreio parou há 6 dias', 'O rastreio está parado em "Em trânsito" desde o dia 06/10.', 'Entrega', TicketPriority::High, 1, true, null],
        ['O código de verificação não chega e não consigo entrar', 'Já tentei três vezes e o SMS não chega.', 'Conta e acesso', TicketPriority::High, 30, false, null],
        ['Estorno da devolução ainda não caiu na fatura', 'Devolvi o produto há 15 dias.', 'Pagamento', TicketPriority::High, 6, true, 'Oi! Já pedimos o estorno à operadora, ele aparece em até 2 faturas.'],
        ['Como altero o endereço de entrega de um pedido já pago?', 'Mudei de apartamento ontem.', 'Entrega', TicketPriority::Normal, 3, false, null],
        ['Quero trocar o tênis por um número maior', 'O 41 ficou apertado, queria o 42.', 'Troca e devolução', TicketPriority::Normal, 20, true, 'Claro! Enviamos o código de postagem por e-mail.'],
        ['Cupom PRIMEIRA10 não é aplicado no carrinho', 'Aparece "cupom inválido".', 'Pagamento', TicketPriority::Low, 4, false, null],
        ['Caixa chegou amassada, mas o produto está perfeito', 'Só para avisar.', 'Entrega', TicketPriority::Low, 50, false, null],
    ];

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
            $tenant = Tenant::create(['slug' => 'acme', 'name' => 'Loja Aurora']);

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

                $categories = [];

                foreach (self::CATEGORIES as $name => $description) {
                    $categories[$name] = Category::create(['name' => $name, 'slug' => Str::slug($name), 'description' => $description]);
                }

                foreach (self::TICKETS as [$subject, $description, $category, $priority, $hoursAgo, $assigned, $reply]) {
                    $opened = now()->subHours($hoursAgo);
                    $ticket = new Ticket([
                        'subject' => $subject,
                        'description' => $description,
                        'priority' => $priority,
                        'category_id' => $categories[$category]->id,
                    ]);
                    $ticket->requester_id = $users['customer']->id;
                    $ticket->assignee_id = $assigned ? $users['agent']->id : null;
                    $ticket->created_at = $opened;
                    $ticket->updated_at = $opened;
                    $ticket->save();

                    if ($reply !== null) {
                        $message = new TicketMessage(['body' => $reply]);
                        $message->ticket_id = $ticket->id;
                        $message->author_id = $users['agent']->id;
                        $message->created_at = $opened->copy()->addMinutes(20);
                        $message->save();

                        $ticket->first_response_at = $message->created_at;
                        $ticket->status = TicketStatus::Pending;
                        $ticket->save();
                    }
                }
            });
        });

        $this->info('Demo tenant created. Send "X-Tenant: acme" when logging in.');
        $this->table(['Role', 'E-mail', 'Password'], $rows);

        return self::SUCCESS;
    }
}
