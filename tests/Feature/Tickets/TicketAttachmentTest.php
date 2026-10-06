<?php

namespace Tests\Feature\Tickets;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class TicketAttachmentTest extends TestCase
{
    use CreatesTicketUsers, RefreshDatabase;

    protected User $employee;

    protected Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpTicketReferenceData();
        Storage::fake('local');
        $this->employee = $this->employee();
        $this->ticket = Ticket::factory()->for($this->employee, 'creator')->create();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function storedAttachment(array $attributes = []): TicketAttachment
    {
        $attachment = TicketAttachment::factory()->for($this->ticket)->create([
            'original_name' => 'error.png',
            'mime_type' => 'image/png',
            ...$attributes,
        ]);
        Storage::disk('local')->put($attachment->path, 'fake image contents');

        return $attachment;
    }

    public function test_requesters_can_download_their_attachments(): void
    {
        $attachment = $this->storedAttachment();

        $response = $this->actingAs($this->employee)->get(route('attachments.show', $attachment));

        $response->assertOk()->assertDownload('error.png');
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    public function test_images_can_be_shown_inline(): void
    {
        $attachment = $this->storedAttachment();

        $response = $this->actingAs($this->employee)->get(route('attachments.show', [$attachment, 'inline' => 1]));

        $response->assertOk();
        $this->assertStringStartsWith('inline', $response->headers->get('Content-Disposition'));
    }

    public function test_non_images_are_always_downloaded(): void
    {
        $attachment = $this->storedAttachment(['original_name' => 'notes.pdf', 'mime_type' => 'application/pdf']);

        $this->actingAs($this->employee)
            ->get(route('attachments.show', [$attachment, 'inline' => 1]))
            ->assertDownload('notes.pdf');
    }

    public function test_other_employees_cannot_download_attachments(): void
    {
        $attachment = $this->storedAttachment();

        $this->actingAs($this->employee())
            ->get(route('attachments.show', $attachment))
            ->assertForbidden();
    }

    public function test_files_on_internal_notes_are_only_for_it_staff(): void
    {
        $note = TicketComment::factory()->internal()->for($this->ticket)->create();
        $attachment = $this->storedAttachment(['ticket_comment_id' => $note->id]);

        $this->actingAs($this->employee)->get(route('attachments.show', $attachment))->assertForbidden();
        $this->actingAs($this->supportAgent())->get(route('attachments.show', $attachment))->assertOk();
    }

    public function test_missing_files_return_not_found(): void
    {
        $attachment = TicketAttachment::factory()->for($this->ticket)->create();

        $this->actingAs($this->employee)
            ->get(route('attachments.show', $attachment))
            ->assertNotFound();
    }
}
