<?php

use JeffersonGoncalves\ServiceDesk\Enums\HistoryAction;
use JeffersonGoncalves\ServiceDesk\Enums\TicketPriority;
use JeffersonGoncalves\ServiceDesk\Enums\TicketStatus;
use JeffersonGoncalves\ServiceDesk\Events\AttachmentAdded;
use JeffersonGoncalves\ServiceDesk\Events\AttachmentRemoved;
use JeffersonGoncalves\ServiceDesk\Events\CommentAdded;
use JeffersonGoncalves\ServiceDesk\Events\TicketAssigned;
use JeffersonGoncalves\ServiceDesk\Events\TicketClosed;
use JeffersonGoncalves\ServiceDesk\Events\TicketCreated;
use JeffersonGoncalves\ServiceDesk\Events\TicketPriorityChanged;
use JeffersonGoncalves\ServiceDesk\Events\TicketReopened;
use JeffersonGoncalves\ServiceDesk\Events\TicketStatusChanged;
use JeffersonGoncalves\ServiceDesk\Listeners\LogTicketHistory;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Models\TicketAttachment;
use JeffersonGoncalves\ServiceDesk\Models\TicketComment;
use JeffersonGoncalves\ServiceDesk\Models\TicketHistory;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

beforeEach(function () {
    $this->listener = new LogTicketHistory;
    $this->ticket = Ticket::factory()->create();
    $this->performer = User::create(['name' => 'Operator', 'email' => 'operator@example.com']);
});

it('logs ticket created history', function () {
    $this->listener->handleTicketCreated(new TicketCreated($this->ticket));

    $history = TicketHistory::where('ticket_id', $this->ticket->id)->first();

    expect($history->action)->toBe(HistoryAction::Created)
        ->and($history->performer_type)->toBe($this->ticket->user_type)
        ->and($history->performer_id)->toBe($this->ticket->user_id);
});

it('logs status changed history', function () {
    $this->listener->handleTicketStatusChanged(new TicketStatusChanged(
        $this->ticket, TicketStatus::Open, TicketStatus::InProgress, $this->performer
    ));

    $history = TicketHistory::where('ticket_id', $this->ticket->id)->first();

    expect($history->action)->toBe(HistoryAction::StatusChanged)
        ->and($history->field)->toBe('status')
        ->and($history->old_value)->toBe('open')
        ->and($history->new_value)->toBe('in_progress')
        ->and($history->performer_id)->toBe($this->performer->id);
});

it('logs priority changed history', function () {
    $this->listener->handleTicketPriorityChanged(new TicketPriorityChanged(
        $this->ticket, TicketPriority::Medium, TicketPriority::High, $this->performer
    ));

    $history = TicketHistory::where('ticket_id', $this->ticket->id)->first();

    expect($history->action)->toBe(HistoryAction::PriorityChanged)
        ->and($history->old_value)->toBe('medium')
        ->and($history->new_value)->toBe('high');
});

it('logs assigned history with the assigned operator metadata', function () {
    $this->listener->handleTicketAssigned(new TicketAssigned($this->ticket, $this->performer));

    $history = TicketHistory::where('ticket_id', $this->ticket->id)->first();

    expect($history->action)->toBe(HistoryAction::Assigned)
        ->and($history->field)->toBe('assigned_to')
        ->and($history->metadata['assigned_to_id'])->toBe($this->performer->id);
});

it('logs closed history', function () {
    $this->listener->handleTicketClosed(new TicketClosed($this->ticket, $this->performer));

    $history = TicketHistory::where('ticket_id', $this->ticket->id)->first();

    expect($history->action)->toBe(HistoryAction::Closed)
        ->and($history->performer_id)->toBe($this->performer->id);
});

it('logs reopened history', function () {
    $this->listener->handleTicketReopened(new TicketReopened($this->ticket, $this->performer));

    $history = TicketHistory::where('ticket_id', $this->ticket->id)->first();

    expect($history->action)->toBe(HistoryAction::Reopened);
});

it('logs comment added history with metadata', function () {
    $comment = TicketComment::factory()->for($this->ticket)->create();

    $this->listener->handleCommentAdded(new CommentAdded($this->ticket, $comment));

    $history = TicketHistory::where('ticket_id', $this->ticket->id)->first();

    expect($history->action)->toBe(HistoryAction::CommentAdded)
        ->and($history->metadata['comment_id'])->toBe($comment->id);
});

it('logs attachment added history with metadata', function () {
    $attachment = TicketAttachment::create([
        'ticket_id' => $this->ticket->id,
        'uploaded_by_type' => $this->performer->getMorphClass(),
        'uploaded_by_id' => $this->performer->id,
        'file_name' => 'report.pdf',
        'file_path' => 'attachments/report.pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 1024,
    ]);

    $this->listener->handleAttachmentAdded(new AttachmentAdded($this->ticket, $attachment));

    $history = TicketHistory::where('ticket_id', $this->ticket->id)->first();

    expect($history->action)->toBe(HistoryAction::AttachmentAdded)
        ->and($history->metadata['file_name'])->toBe('report.pdf');
});

it('logs attachment removed history with metadata', function () {
    $attachment = TicketAttachment::create([
        'ticket_id' => $this->ticket->id,
        'uploaded_by_type' => $this->performer->getMorphClass(),
        'uploaded_by_id' => $this->performer->id,
        'file_name' => 'old.pdf',
        'file_path' => 'attachments/old.pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 512,
    ]);

    $this->listener->handleAttachmentRemoved(new AttachmentRemoved($this->ticket, $attachment, $this->performer));

    $history = TicketHistory::where('ticket_id', $this->ticket->id)->first();

    expect($history->action)->toBe(HistoryAction::AttachmentRemoved)
        ->and($history->metadata['file_name'])->toBe('old.pdf')
        ->and($history->performer_id)->toBe($this->performer->id);
});

it('subscribes to all the relevant ticket events', function () {
    $subscriptions = $this->listener->subscribe(app('events'));

    expect($subscriptions)->toHaveKeys([
        TicketCreated::class,
        TicketStatusChanged::class,
        TicketPriorityChanged::class,
        TicketAssigned::class,
        TicketClosed::class,
        TicketReopened::class,
        CommentAdded::class,
        AttachmentAdded::class,
        AttachmentRemoved::class,
    ]);
});
