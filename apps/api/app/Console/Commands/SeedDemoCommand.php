<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Knowledge\PublishKnowledgeArticle;
use App\Actions\Knowledge\SaveKnowledgeArticle;
use App\Enums\FieldSource;
use App\Enums\Role;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Jobs\ClassifyTicket;
use App\Models\Category;
use App\Models\KbArticle;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Support\Demo\DemoKnowledgeBase;
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
     * A null category leaves category and priority for the classifier (when it runs).
     *
     * @var list<array{string, string, string|null, TicketPriority, int, bool, string|null}>
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
        ['Paguei o boleto e o pedido continua aguardando pagamento', 'Paguei anteontem e preciso do produto para sexta. Estou bem chateada.', null, TicketPriority::Normal, 1, false, null],
        ['Adorei o atendimento, só queria trocar a cor', 'A blusa veio perfeita, mas prefiro a azul. Obrigada pela ajuda!', null, TicketPriority::Normal, 2, false, null],
    ];

    protected $signature = 'ticko:demo';

    protected $description = 'Create a demo tenant ("acme") with one user per role and print their random passwords';

    public function handle(TenantContext $tenants): int
    {
        if ($this->laravel->isProduction()) {
            $this->error('Refusing to seed demo data in production.');

            return self::FAILURE;
        }

        $existing = Tenant::where('slug', 'acme')->first();

        if ($existing !== null) {
            // Older demo tenants get the knowledge base added once.
            $added = $tenants->run($existing, fn (): int => $this->seedKnowledgeBase());
            $this->warn('The demo tenant "acme" already exists.'.($added > 0 ? " Added {$added} knowledge-base articles." : ' Nothing to do.'));

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
                        'category_id' => $category === null ? null : $categories[$category]->id,
                    ]);
                    // What the customer picked; the classifier never overwrites it.
                    $ticket->category_source = $category === null ? null : FieldSource::Requester;
                    $ticket->priority_source = $category === null ? null : FieldSource::Requester;
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

        $tenant = Tenant::where('slug', 'acme')->firstOrFail();
        $tenants->run($tenant, fn (): int => $this->seedKnowledgeBase());

        // With the classifier running (make up-ml), the demo tickets get classified by the queue.
        if (config()->string('ticko.classifier.url') !== '') {
            $tenants->run($tenant, function () use ($tenant): void {
                foreach (Ticket::query()->get(['id']) as $ticket) {
                    ClassifyTicket::dispatch($tenant->id, $ticket->id);
                }
            });
        }

        $this->info('Demo tenant created. Send "X-Tenant: acme" when logging in.');
        $this->table(['Role', 'E-mail', 'Password'], $rows);

        return self::SUCCESS;
    }

    /**
     * Demo articles, published through the normal flow (indexed by the queue).
     * Returns how many were created; none if the tenant already has articles.
     */
    private function seedKnowledgeBase(): int
    {
        if (KbArticle::query()->exists()) {
            return 0;
        }

        $author = User::query()->where('role', Role::Admin)->orderBy('created_at')->firstOrFail();
        $categories = Category::query()->pluck('id', 'name');
        $save = app(SaveKnowledgeArticle::class);
        $publish = app(PublishKnowledgeArticle::class);

        foreach (DemoKnowledgeBase::articles() as [$title, $category, $visibility, $published, $body]) {
            $categoryId = $category !== null ? ($categories[$category] ?? null) : null;
            $article = $save->handle($author, [
                'title' => $title,
                'body' => $body,
                'visibility' => $visibility->value,
                'tags' => [],
                'category_id' => is_string($categoryId) ? $categoryId : null,
            ]);

            if ($published) {
                $publish->handle($author, $article);
            }
        }

        return count(DemoKnowledgeBase::articles());
    }
}
