<?php

use JeffersonGoncalves\ServiceDesk\Events\TicketCreated;
use JeffersonGoncalves\ServiceDesk\Events\TicketUpdated;
use JeffersonGoncalves\ServiceDesk\Listeners\SuggestKbArticles;
use JeffersonGoncalves\ServiceDesk\Models\Department;
use JeffersonGoncalves\ServiceDesk\Models\KbCategory;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Services\KnowledgeBaseService;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

beforeEach(function () {
    $this->listener = new SuggestKbArticles(app(KnowledgeBaseService::class));

    $author = User::create(['name' => 'Author', 'email' => 'author@example.com']);
    $category = KbCategory::create(['name' => 'Networking', 'slug' => 'networking']);
    $kbService = app(KnowledgeBaseService::class);

    $this->article = $kbService->createArticle([
        'category_id' => $category->id,
        'title' => 'VPN connection issues',
        'slug' => 'vpn-connection-issues',
        'content' => 'Troubleshoot VPN connection problems',
    ], $author);
    $kbService->publishArticle($this->article->fresh());

    $department = Department::create(['name' => 'IT', 'slug' => 'it']);
    $user = User::create(['name' => 'Requester', 'email' => 'requester@example.com']);

    $this->ticket = Ticket::create([
        'department_id' => $department->id,
        'user_type' => $user->getMorphClass(),
        'user_id' => $user->id,
        'title' => 'Cannot print',
        'description' => '...',
    ]);
});

it('suggests articles when a ticket is created', function () {
    $this->ticket->update(['title' => 'VPN connection issues']);

    $this->listener->handleTicketCreated(new TicketCreated($this->ticket));

    expect($this->ticket->fresh()->metadata['suggested_articles'])->toBe([$this->article->id]);
});

it('re-suggests articles when the title changes via update', function () {
    $this->ticket->update(['title' => 'VPN connection issues']);

    $this->listener->handleTicketUpdated(new TicketUpdated($this->ticket, ['title' => 'VPN connection issues']));

    expect($this->ticket->fresh()->metadata['suggested_articles'])->toBe([$this->article->id]);
});

it('does nothing on update when neither title nor description changed', function () {
    $this->listener->handleTicketUpdated(new TicketUpdated($this->ticket, ['priority' => 'high']));

    expect($this->ticket->fresh()->metadata)->toBeNull();
});

it('subscribes to ticket created and updated events', function () {
    $subscriptions = $this->listener->subscribe(app('events'));

    expect($subscriptions)->toHaveKeys([
        TicketCreated::class,
        TicketUpdated::class,
    ]);
});
