<?php

use Illuminate\Support\Facades\Notification;
use JeffersonGoncalves\ServiceDesk\Enums\TicketStatus;
use JeffersonGoncalves\ServiceDesk\Events\TicketClosed;
use JeffersonGoncalves\ServiceDesk\Events\TicketStatusChanged;
use JeffersonGoncalves\ServiceDesk\Listeners\SendTicketClosedNotification;
use JeffersonGoncalves\ServiceDesk\Listeners\SendTicketStatusChangedNotification;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Notifications\TicketClosedNotification;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

it('sends one closed email instead of a status change email on close', function () {
    Notification::fake();

    $owner = User::create(['name' => 'Owner', 'email' => 'owner@example.com']);
    $ticket = Ticket::factory()->create(['user_type' => $owner->getMorphClass(), 'user_id' => $owner->id]);

    (new SendTicketStatusChangedNotification)->handle(new TicketStatusChanged($ticket, TicketStatus::Open, TicketStatus::Closed));
    (new SendTicketClosedNotification)->handle(new TicketClosed($ticket));

    Notification::assertSentToTimes($owner, TicketClosedNotification::class, 1);
    Notification::assertCount(1);
});
