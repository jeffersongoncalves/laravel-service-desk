<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use JeffersonGoncalves\ServiceDesk\Models\Department;
use JeffersonGoncalves\ServiceDesk\Models\Tag;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

uses(RefreshDatabase::class);

// ── CRUD ────────────────────────────────────────────────────────────────────

it('can create a tag', function () {
    $tag = Tag::create([
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

it('can update a tag', function () {
    $tag = Tag::create(['name' => 'Bug', 'slug' => 'bug']);

    $tag->update(['name' => 'Feature Request', 'slug' => 'feature-request']);

    expect($tag->fresh())
        ->name->toBe('Feature Request')
        ->slug->toBe('feature-request');
});

it('can delete a tag', function () {
    $tag = Tag::create(['name' => 'Bug', 'slug' => 'bug']);
    $id = $tag->id;

    $tag->delete();

    expect(Tag::find($id))->toBeNull();
});

it('enforces unique name constraint', function () {
    Tag::create(['name' => 'Bug', 'slug' => 'bug']);
    Tag::create(['name' => 'Bug', 'slug' => 'bug-2']);
})->throws(\Illuminate\Database\QueryException::class);

it('enforces unique slug constraint', function () {
    Tag::create(['name' => 'Bug', 'slug' => 'bug']);
    Tag::create(['name' => 'Bug 2', 'slug' => 'bug']);
})->throws(\Illuminate\Database\QueryException::class);

// ── Polymorphic Relationships ───────────────────────────────────────────────

it('can be attached to tickets via morphToMany', function () {
    $user = User::create(['name' => 'John', 'email' => 'john@example.com']);
    $department = Department::create(['name' => 'IT', 'slug' => 'it']);

    $ticket = Ticket::create([
        'department_id' => $department->id,
        'user_type' => $user->getMorphClass(),
        'user_id' => $user->id,
        'title' => 'Tagged ticket',
        'description' => 'Has a tag',
    ]);

    $tag = Tag::create(['name' => 'Urgent', 'slug' => 'urgent', 'color' => '#FF0000']);

    $ticket->tags()->attach($tag->id);

    expect($tag->tickets)->toHaveCount(1)
        ->and($tag->tickets->first()->id)->toBe($ticket->id);
});

it('can be attached to multiple tickets', function () {
    $user = User::create(['name' => 'John', 'email' => 'john@example.com']);
    $department = Department::create(['name' => 'IT', 'slug' => 'it']);

    $ticket1 = Ticket::create([
        'department_id' => $department->id,
        'user_type' => $user->getMorphClass(),
        'user_id' => $user->id,
        'title' => 'Ticket 1',
        'description' => 'First ticket',
    ]);

    $ticket2 = Ticket::create([
        'department_id' => $department->id,
        'user_type' => $user->getMorphClass(),
        'user_id' => $user->id,
        'title' => 'Ticket 2',
        'description' => 'Second ticket',
    ]);

    $tag = Tag::create(['name' => 'Bug', 'slug' => 'bug']);

    $ticket1->tags()->attach($tag->id);
    $ticket2->tags()->attach($tag->id);

    expect($tag->tickets()->count())->toBe(2);
});

it('ticket can have multiple tags', function () {
    $user = User::create(['name' => 'John', 'email' => 'john@example.com']);
    $department = Department::create(['name' => 'IT', 'slug' => 'it']);

    $ticket = Ticket::create([
        'department_id' => $department->id,
        'user_type' => $user->getMorphClass(),
        'user_id' => $user->id,
        'title' => 'Multi-tagged ticket',
        'description' => 'Has many tags',
    ]);

    $tag1 = Tag::create(['name' => 'Bug', 'slug' => 'bug']);
    $tag2 = Tag::create(['name' => 'Urgent', 'slug' => 'urgent']);
    $tag3 = Tag::create(['name' => 'Backend', 'slug' => 'backend']);

    $ticket->tags()->attach([$tag1->id, $tag2->id, $tag3->id]);

    expect($ticket->tags)->toHaveCount(3);
});

it('detaching a tag removes the pivot record', function () {
    $user = User::create(['name' => 'John', 'email' => 'john@example.com']);
    $department = Department::create(['name' => 'IT', 'slug' => 'it']);

    $ticket = Ticket::create([
        'department_id' => $department->id,
        'user_type' => $user->getMorphClass(),
        'user_id' => $user->id,
        'title' => 'Detach test',
        'description' => 'Remove a tag',
    ]);

    $tag = Tag::create(['name' => 'Bug', 'slug' => 'bug']);
    $ticket->tags()->attach($tag->id);

    expect($ticket->tags)->toHaveCount(1);

    $ticket->tags()->detach($tag->id);
    $ticket->load('tags');

    expect($ticket->tags)->toHaveCount(0);
});

it('syncing tags replaces existing tags', function () {
    $user = User::create(['name' => 'John', 'email' => 'john@example.com']);
    $department = Department::create(['name' => 'IT', 'slug' => 'it']);

    $ticket = Ticket::create([
        'department_id' => $department->id,
        'user_type' => $user->getMorphClass(),
        'user_id' => $user->id,
        'title' => 'Sync test',
        'description' => 'Sync tags',
    ]);

    $tag1 = Tag::create(['name' => 'Bug', 'slug' => 'bug']);
    $tag2 = Tag::create(['name' => 'Feature', 'slug' => 'feature']);
    $tag3 = Tag::create(['name' => 'Urgent', 'slug' => 'urgent']);

    $ticket->tags()->attach([$tag1->id, $tag2->id]);

    expect($ticket->tags)->toHaveCount(2);

    $ticket->tags()->sync([$tag2->id, $tag3->id]);
    $ticket->load('tags');

    expect($ticket->tags)->toHaveCount(2)
        ->and($ticket->tags->pluck('id')->toArray())->toEqualCanonicalizing([$tag2->id, $tag3->id]);
});

it('deleting a tag cascades to pivot table', function () {
    $user = User::create(['name' => 'John', 'email' => 'john@example.com']);
    $department = Department::create(['name' => 'IT', 'slug' => 'it']);

    $ticket = Ticket::create([
        'department_id' => $department->id,
        'user_type' => $user->getMorphClass(),
        'user_id' => $user->id,
        'title' => 'Cascade test',
        'description' => 'Delete tag cascade',
    ]);

    $tag = Tag::create(['name' => 'Bug', 'slug' => 'bug']);
    $ticket->tags()->attach($tag->id);

    $tag->delete();
    $ticket->load('tags');

    expect($ticket->tags)->toHaveCount(0);
});
