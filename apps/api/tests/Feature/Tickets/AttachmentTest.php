<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Enums\TicketStatus;
use App\Models\Attachment;
use App\Models\Tenant;
use App\Models\Ticket;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Images;

beforeEach(function (): void {
    Storage::fake('local');

    $this->tenant = Tenant::factory()->create();
    $this->customer = $this->userIn($this->tenant, Role::Customer);
    $this->agent = $this->userIn($this->tenant, Role::Agent);
    $this->ticket = $this->inTenant($this->tenant, fn () => Ticket::factory()->create(['requester_id' => $this->customer->id]));
});

function upload(object $test, UploadedFile $file)
{
    return $test->post("/api/v1/tickets/{$test->ticket->id}/attachments", ['file' => $file]);
}

it('stores an uploaded image privately under a random name', function (): void {
    $response = upload($this->actingAsUser($this->customer), Images::png(20, 10))
        ->assertCreated()
        ->assertJsonPath('data.mime_type', 'image/png')
        ->assertJsonPath('data.width', 20)
        ->assertJsonPath('data.height', 10)
        ->assertJsonPath('data.original_name', 'image.png')
        ->assertJsonMissingPath('data.path')
        ->assertJsonMissingPath('data.disk');

    $attachment = $this->inTenant($this->tenant, fn () => Attachment::firstOrFail());

    expect($attachment->path)
        ->toStartWith("tenants/{$this->tenant->id}/tickets/{$this->ticket->id}/")
        ->not->toContain('image.png');
    Storage::disk('local')->assertExists($attachment->path);
    expect($response->json('data.url'))->toContain('signature=');
});

it('strips EXIF metadata by re-encoding', function (): void {
    $upload = Images::jpegWithExif();
    expect(file_get_contents($upload->getRealPath()))->toContain('GPSLatitude');

    upload($this->actingAsUser($this->customer), $upload)->assertCreated()->assertJsonPath('data.mime_type', 'image/jpeg');

    $attachment = $this->inTenant($this->tenant, fn () => Attachment::firstOrFail());
    $stored = Storage::disk('local')->get($attachment->path);

    expect($stored)->not->toContain('GPSLatitude')->not->toContain('Exif')
        ->and($attachment->checksum)->toBe(hash('sha256', $stored));
});

it('drops payloads appended to an image', function (): void {
    upload($this->actingAsUser($this->customer), Images::png(appended: '<?php system($_GET["c"]); ?>'))->assertCreated();

    $attachment = $this->inTenant($this->tenant, fn () => Attachment::firstOrFail());

    expect(Storage::disk('local')->get($attachment->path))->not->toContain('<?php');
});

it('accepts WebP images', function (): void {
    upload($this->actingAsUser($this->customer), Images::webp())->assertCreated()->assertJsonPath('data.mime_type', 'image/webp');
});

it('rejects files whose content is not an allowed image', function (UploadedFile $file): void {
    upload($this->actingAsUser($this->customer), $file)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file');

    $this->inTenant($this->tenant, fn () => expect(Attachment::count())->toBe(0));
})->with([
    'php script named .png' => fn () => Images::file('shell.png', '<?php echo "pwned"; ?>'),
    'svg with script' => fn () => Images::file('logo.png', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
    'gif' => fn () => Images::file('anim.png', base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7')),
    'truncated png' => fn () => Images::file('broken.png', substr((string) file_get_contents(Images::png()->getRealPath()), 0, 40)),
    'pdf' => fn () => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
]);

it('rejects oversized files and dimensions', function (): void {
    $this->actingAsUser($this->customer);

    config(['ticko.attachments_max_kb' => 1]);
    upload($this, Images::png(400, 400))->assertUnprocessable()->assertJsonValidationErrors('file');

    config(['ticko.attachments_max_kb' => 5120]);
    upload($this, Images::png(10_001, 1))->assertUnprocessable()->assertJsonValidationErrors('file');
});

it('forbids uploads by other customers and on closed tickets', function (): void {
    $stranger = $this->userIn($this->tenant, Role::Customer);
    upload($this->actingAsUser($stranger), Images::png())->assertForbidden();

    $this->inTenant($this->tenant, fn () => $this->ticket->forceFill(['status' => TicketStatus::Closed])->save());
    upload($this->actingAsUser($this->customer), Images::png())->assertForbidden();
});

it('lists and shows attachments to people who can see the ticket', function (): void {
    upload($this->actingAsUser($this->customer), Images::png())->assertCreated();
    $attachment = $this->inTenant($this->tenant, fn () => Attachment::firstOrFail());

    $this->actingAsUser($this->agent)->getJson("/api/v1/tickets/{$this->ticket->id}/attachments")->assertOk()->assertJsonCount(1, 'data');
    $this->getJson("/api/v1/attachments/{$attachment->id}")->assertOk()->assertJsonPath('data.id', $attachment->id);

    $stranger = $this->userIn($this->tenant, Role::Customer);
    $this->actingAsUser($stranger)->getJson("/api/v1/attachments/{$attachment->id}")->assertForbidden();
    $this->getJson("/api/v1/tickets/{$this->ticket->id}/attachments")->assertForbidden();
});

it('serves content only through a valid, unexpired signed URL', function (): void {
    $url = upload($this->actingAsUser($this->customer), Images::png())->json('data.url');
    $this->app['auth']->forgetGuards();

    // The signed URL works without any credentials (e.g. in an <img> tag).
    $response = $this->withoutToken()->get($url)
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
    expect($response->headers->get('Content-Security-Policy'))->toContain('sandbox');

    $this->get($url.'x')->assertForbidden();
    $this->get(preg_replace('/tenant=[^&]+/', 'tenant='.Tenant::factory()->create()->id, $url))->assertForbidden();
    $this->get(strtok($url, '?'))->assertForbidden();

    $this->travel(config('ticko.attachment_url_ttl_minutes') + 1)->minutes();
    $this->get($url)->assertForbidden();
});
