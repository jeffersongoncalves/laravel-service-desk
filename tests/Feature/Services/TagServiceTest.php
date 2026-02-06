<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use JeffersonGoncalves\ServiceDesk\Models\Department;
use JeffersonGoncalves\ServiceDesk\Models\Tag;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Services\TagService;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(TagService::class);
});

// ── create() ────────────────────────────────────────────────────────────────

it('creates a tag with all fields', function () {
    $tag = $this->service->create([
        'name' => 'Bug',
        'slug' => 'bug',
        'color' => '#FF0000',
        'description' => 'Bug reports',
    ]);

    expect($tag)->toBeInstanceOf(Tag::class)
        ->and($tag->name)->toBe('Bug')
        ->and($tag->slug)->toBe('bug')
        ->and($tag->color)->toBe('#FF0000')
        ->and($tag->description)->toBe('Bug reports');
});

it('auto generates slug from name when slug is empty', function () {
    $tag = $this->service->create([
        'name' => 'Feature Request',
    ]);

    expect($tag->slug)->toBe('feature-request');
});

it('uses provided slug when given', function () {
    $tag = $this->service->create([
        'name' => 'Feature Request',
        'slug' => 'feat-req',
    ]);

    expect($tag->slug)->toBe('feat-req');
});

// ── update() ────────────────────────────────────────────────────────────────

it('updates a tag', function () {
    $tag = $this->service->create(['name' => 'Bug']);

    $updated = $this->service->update($tag, [
        'name' => 'Critical Bug',
        'color' => '#990000',
    ]);

    expect($updated->name)->toBe('Critical Bug')
        ->and($updated->slug)->toBe('critical-bug')
        ->and($updated->color)->toBe('#990000');
});

it('auto generates new slug when name is updated without slug', function () {
    $tag = $this->service->create(['name' => 'Bug', 'slug' => 'bug']);

    $updated = $this->service->update($tag, ['name' => 'Enhancement']);

    expect($updated->slug)->toBe('enhancement');
});

it('keeps existing slug when only non-name fields are updated', function () {
    $tag = $this->service->create(['name' => 'Bug', 'slug' => 'bug']);

    $updated = $this->service->update($tag, ['color' => '#00FF00']);

    expect($updated->slug)->toBe('bug');
});

// ── delete() ────────────────────────────────────────────────────────────────

it('deletes a tag', function () {
    $tag = $this->service->create(['name' => 'To Delete']);
    $id = $tag->id;

    $result = $this->service->delete($tag);

    expect($result)->toBeTrue()
        ->and(Tag::find($id))->toBeNull();
});

// ── syncTags() ──────────────────────────────────────────────────────────────

it('syncs tags on a model', function () {
    $department = Department::create(['name' => 'IT', 'slug' => 'it']);
    $user = User::create(['name' => 'John', 'email' => 'john@example.com']);

    $ticket = Ticket::create([
        'department_id' => $department->id,
        'user_type' => $user->getMorphClass(),
        'user_id' => $user->id,
        'title' => 'Sync test',
        'description' => 'Sync tags',
    ]);

    $tag1 = $this->service->create(['name' => 'Bug']);
    $tag2 = $this->service->create(['name' => 'Feature']);
    $tag3 = $this->service->create(['name' => 'Urgent']);

    // Attach initial tags
    $this->service->syncTags($ticket, [$tag1->id, $tag2->id]);
    expect($ticket->tags()->count())->toBe(2);

    // Sync replaces with new set
    $this->service->syncTags($ticket, [$tag2->id, $tag3->id]);
    $ticket->load('tags');

    expect($ticket->tags)->toHaveCount(2)
        ->and($ticket->tags->pluck('id')->toArray())->toEqualCanonicalizing([$tag2->id, $tag3->id]);
});

it('syncs to empty removes all tags', function () {
    $department = Department::create(['name' => 'IT', 'slug' => 'it']);
    $user = User::create(['name' => 'John', 'email' => 'john@example.com']);

    $ticket = Ticket::create([
        'department_id' => $department->id,
        'user_type' => $user->getMorphClass(),
        'user_id' => $user->id,
        'title' => 'Clear tags',
        'description' => 'Remove all tags',
    ]);

    $tag = $this->service->create(['name' => 'Bug']);
    $this->service->syncTags($ticket, [$tag->id]);

    $this->service->syncTags($ticket, []);
    $ticket->load('tags');

    expect($ticket->tags)->toHaveCount(0);
});

// ── attachTags() ────────────────────────────────────────────────────────────

it('attaches tags to a model', function () {
    $department = Department::create(['name' => 'IT', 'slug' => 'it']);
    $user = User::create(['name' => 'John', 'email' => 'john@example.com']);

    $ticket = Ticket::create([
        'department_id' => $department->id,
        'user_type' => $user->getMorphClass(),
        'user_id' => $user->id,
        'title' => 'Attach test',
        'description' => 'Attach tags',
    ]);

    $tag1 = $this->service->create(['name' => 'Bug']);
    $tag2 = $this->service->create(['name' => 'Feature']);

    $this->service->attachTags($ticket, [$tag1->id, $tag2->id]);

    expect($ticket->tags()->count())->toBe(2);
});

// ── detachTags() ────────────────────────────────────────────────────────────

it('detaches tags from a model', function () {
    $department = Department::create(['name' => 'IT', 'slug' => 'it']);
    $user = User::create(['name' => 'John', 'email' => 'john@example.com']);

    $ticket = Ticket::create([
        'department_id' => $department->id,
        'user_type' => $user->getMorphClass(),
        'user_id' => $user->id,
        'title' => 'Detach test',
        'description' => 'Detach tags',
    ]);

    $tag1 = $this->service->create(['name' => 'Bug']);
    $tag2 = $this->service->create(['name' => 'Feature']);

    $this->service->attachTags($ticket, [$tag1->id, $tag2->id]);
    $this->service->detachTags($ticket, [$tag1->id]);

    $ticket->load('tags');

    expect($ticket->tags)->toHaveCount(1)
        ->and($ticket->tags->first()->id)->toBe($tag2->id);
});

it('detaching all tags empties the relationship', function () {
    $department = Department::create(['name' => 'IT', 'slug' => 'it']);
    $user = User::create(['name' => 'John', 'email' => 'john@example.com']);

    $ticket = Ticket::create([
        'department_id' => $department->id,
        'user_type' => $user->getMorphClass(),
        'user_id' => $user->id,
        'title' => 'Detach all',
        'description' => 'Detach all tags',
    ]);

    $tag1 = $this->service->create(['name' => 'Bug']);
    $tag2 = $this->service->create(['name' => 'Feature']);

    $this->service->attachTags($ticket, [$tag1->id, $tag2->id]);
    $this->service->detachTags($ticket, [$tag1->id, $tag2->id]);

    $ticket->load('tags');

    expect($ticket->tags)->toHaveCount(0);
});
