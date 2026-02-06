<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use JeffersonGoncalves\ServiceDesk\Enums\ArticleStatus;
use JeffersonGoncalves\ServiceDesk\Events\ArticleCreated;
use JeffersonGoncalves\ServiceDesk\Events\ArticleFeedbackReceived;
use JeffersonGoncalves\ServiceDesk\Events\ArticlePublished;
use JeffersonGoncalves\ServiceDesk\Models\Department;
use JeffersonGoncalves\ServiceDesk\Models\KbArticle;
use JeffersonGoncalves\ServiceDesk\Models\KbCategory;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Services\KnowledgeBaseService;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(KnowledgeBaseService::class);
    $this->author = User::create(['name' => 'Author', 'email' => 'author@example.com']);
    $this->category = KbCategory::create([
        'name' => 'Getting Started',
        'slug' => 'getting-started',
    ]);

    $this->kbEvents = [
        ArticleCreated::class,
        ArticlePublished::class,
        ArticleFeedbackReceived::class,
    ];
});

// ── createArticle() ─────────────────────────────────────────────────────────

it('creates an article', function () {
    Event::fake($this->kbEvents);

    $article = $this->service->createArticle([
        'category_id' => $this->category->id,
        'title' => 'How to reset password',
        'slug' => 'how-to-reset-password',
        'content' => 'Follow these steps to reset your password...',
        'excerpt' => 'Password reset guide',
    ], $this->author);

    $fresh = $article->fresh();

    expect($article)->toBeInstanceOf(KbArticle::class)
        ->and($article->title)->toBe('How to reset password')
        ->and($article->slug)->toBe('how-to-reset-password')
        ->and($article->content)->toBe('Follow these steps to reset your password...')
        ->and($article->excerpt)->toBe('Password reset guide')
        ->and($article->author_id)->toBe($this->author->id)
        ->and($article->author_type)->toBe($this->author->getMorphClass())
        ->and($article->uuid)->not->toBeNull()
        ->and($fresh->status)->toBe(ArticleStatus::Draft);

    Event::assertDispatched(ArticleCreated::class);
});

it('creates initial version on article creation', function () {
    Event::fake($this->kbEvents);

    $article = $this->service->createArticle([
        'category_id' => $this->category->id,
        'title' => 'Version test',
        'slug' => 'version-test',
        'content' => 'Initial content',
    ], $this->author);

    expect($article->versions)->toHaveCount(1)
        ->and($article->versions->first()->version_number)->toBe(1)
        ->and($article->versions->first()->title)->toBe('Version test')
        ->and($article->versions->first()->content)->toBe('Initial content')
        ->and($article->versions->first()->change_notes)->toBe('Initial version');
});

it('dispatches ArticleCreated event', function () {
    Event::fake($this->kbEvents);

    $article = $this->service->createArticle([
        'category_id' => $this->category->id,
        'title' => 'Event test',
        'slug' => 'event-test',
        'content' => 'Some content',
    ], $this->author);

    Event::assertDispatched(ArticleCreated::class, function ($event) use ($article) {
        return $event->article->id === $article->id;
    });
});

// ── updateArticle() ─────────────────────────────────────────────────────────

it('updates an article', function () {
    Event::fake($this->kbEvents);

    $article = $this->service->createArticle([
        'category_id' => $this->category->id,
        'title' => 'Original Title',
        'slug' => 'original-title',
        'content' => 'Original content',
    ], $this->author);

    $editor = User::create(['name' => 'Editor', 'email' => 'editor@example.com']);

    $updated = $this->service->updateArticle($article->fresh(), [
        'title' => 'Updated Title',
        'content' => 'Updated content',
    ], $editor, 'Fixed typos');

    expect($updated->title)->toBe('Updated Title')
        ->and($updated->content)->toBe('Updated content');
});

it('creates a new version on update when versioning is enabled', function () {
    Event::fake($this->kbEvents);

    config()->set('service-desk.knowledge_base.versioning_enabled', true);

    $article = $this->service->createArticle([
        'category_id' => $this->category->id,
        'title' => 'Versioned Article',
        'slug' => 'versioned-article',
        'content' => 'Version 1 content',
    ], $this->author);

    $editor = User::create(['name' => 'Editor', 'email' => 'editor@example.com']);

    $updated = $this->service->updateArticle($article->fresh(), [
        'title' => 'Versioned Article v2',
        'content' => 'Version 2 content',
    ], $editor, 'Major update');

    expect($updated->versions)->toHaveCount(2)
        ->and($updated->current_version)->toBe(2)
        ->and($updated->versions->last()->change_notes)->toBe('Major update');
});

it('does not create a new version when versioning is disabled', function () {
    Event::fake($this->kbEvents);

    config()->set('service-desk.knowledge_base.versioning_enabled', false);

    $article = $this->service->createArticle([
        'category_id' => $this->category->id,
        'title' => 'No Version Article',
        'slug' => 'no-version-article',
        'content' => 'Content',
    ], $this->author);

    $editor = User::create(['name' => 'Editor', 'email' => 'editor@example.com']);

    $updated = $this->service->updateArticle($article->fresh(), [
        'title' => 'No Version Article Updated',
    ], $editor);

    // Only the initial version from creation exists
    expect($updated->versions)->toHaveCount(1);
});

// ── publishArticle() ────────────────────────────────────────────────────────

it('publishes an article', function () {
    Event::fake($this->kbEvents);

    $article = $this->service->createArticle([
        'category_id' => $this->category->id,
        'title' => 'Draft Article',
        'slug' => 'draft-article',
        'content' => 'Will be published',
    ], $this->author);

    expect($article->fresh()->status)->toBe(ArticleStatus::Draft);

    $published = $this->service->publishArticle($article->fresh());

    expect($published->status)->toBe(ArticleStatus::Published)
        ->and($published->published_at)->not->toBeNull();

    Event::assertDispatched(ArticlePublished::class);
});

it('dispatches ArticlePublished event', function () {
    Event::fake($this->kbEvents);

    $article = $this->service->createArticle([
        'category_id' => $this->category->id,
        'title' => 'Publish Event',
        'slug' => 'publish-event',
        'content' => 'For publish event test',
    ], $this->author);

    $this->service->publishArticle($article->fresh());

    Event::assertDispatched(ArticlePublished::class, function ($event) use ($article) {
        return $event->article->id === $article->id;
    });
});

// ── archiveArticle() ────────────────────────────────────────────────────────

it('archives an article', function () {
    Event::fake($this->kbEvents);

    $article = $this->service->createArticle([
        'category_id' => $this->category->id,
        'title' => 'To Archive',
        'slug' => 'to-archive',
        'content' => 'Will be archived',
    ], $this->author);

    $archived = $this->service->archiveArticle($article->fresh());

    expect($archived->status)->toBe(ArticleStatus::Archived);
});

it('archives a published article', function () {
    Event::fake($this->kbEvents);

    $article = $this->service->createArticle([
        'category_id' => $this->category->id,
        'title' => 'Published to Archive',
        'slug' => 'published-to-archive',
        'content' => 'Was published, now archived',
    ], $this->author);

    $this->service->publishArticle($article->fresh());
    $archived = $this->service->archiveArticle($article->fresh());

    expect($archived->status)->toBe(ArticleStatus::Archived);
});

// ── deleteArticle() ─────────────────────────────────────────────────────────

it('deletes an article', function () {
    Event::fake($this->kbEvents);

    $article = $this->service->createArticle([
        'category_id' => $this->category->id,
        'title' => 'To Delete',
        'slug' => 'to-delete',
        'content' => 'Will be deleted',
    ], $this->author);

    $result = $this->service->deleteArticle($article);

    expect($result)->toBeTrue()
        ->and(KbArticle::find($article->id))->toBeNull()
        ->and(KbArticle::withTrashed()->find($article->id))->not->toBeNull();
});

// ── search() ────────────────────────────────────────────────────────────────

it('searches published articles by title', function () {
    Event::fake($this->kbEvents);

    $article = $this->service->createArticle([
        'category_id' => $this->category->id,
        'title' => 'How to reset password',
        'slug' => 'reset-password',
        'content' => 'Steps to reset your password',
    ], $this->author);

    $this->service->publishArticle($article->fresh());

    $results = $this->service->search('reset password');

    expect($results)->toHaveCount(1)
        ->and($results->first()->title)->toBe('How to reset password');
});

it('searches published articles by content', function () {
    Event::fake($this->kbEvents);

    $article = $this->service->createArticle([
        'category_id' => $this->category->id,
        'title' => 'General FAQ',
        'slug' => 'general-faq',
        'content' => 'To configure SMTP settings, go to admin panel',
    ], $this->author);

    $this->service->publishArticle($article->fresh());

    $results = $this->service->search('SMTP settings');

    expect($results)->toHaveCount(1);
});

it('does not return draft articles in search', function () {
    Event::fake($this->kbEvents);

    $this->service->createArticle([
        'category_id' => $this->category->id,
        'title' => 'Draft article about passwords',
        'slug' => 'draft-passwords',
        'content' => 'This is a draft',
    ], $this->author);

    $results = $this->service->search('passwords');

    expect($results)->toHaveCount(0);
});

it('does not return archived articles in search', function () {
    Event::fake($this->kbEvents);

    $article = $this->service->createArticle([
        'category_id' => $this->category->id,
        'title' => 'Archived article about passwords',
        'slug' => 'archived-passwords',
        'content' => 'This is archived',
    ], $this->author);

    $this->service->publishArticle($article->fresh());
    $this->service->archiveArticle($article->fresh());

    $results = $this->service->search('passwords');

    expect($results)->toHaveCount(0);
});

it('filters search by category', function () {
    Event::fake($this->kbEvents);

    $otherCategory = KbCategory::create(['name' => 'Advanced', 'slug' => 'advanced']);

    $article1 = $this->service->createArticle([
        'category_id' => $this->category->id,
        'title' => 'Getting started guide',
        'slug' => 'getting-started-guide',
        'content' => 'Welcome guide',
    ], $this->author);
    $this->service->publishArticle($article1->fresh());

    $article2 = $this->service->createArticle([
        'category_id' => $otherCategory->id,
        'title' => 'Advanced guide',
        'slug' => 'advanced-guide',
        'content' => 'Advanced welcome content',
    ], $this->author);
    $this->service->publishArticle($article2->fresh());

    $results = $this->service->search('guide', ['category_id' => $this->category->id]);

    expect($results)->toHaveCount(1)
        ->and($results->first()->title)->toBe('Getting started guide');
});

it('limits search results', function () {
    Event::fake($this->kbEvents);

    for ($i = 1; $i <= 5; $i++) {
        $article = $this->service->createArticle([
            'category_id' => $this->category->id,
            'title' => "Article about topic {$i}",
            'slug' => "article-topic-{$i}",
            'content' => "Content about topic {$i}",
        ], $this->author);
        $this->service->publishArticle($article->fresh());
    }

    $results = $this->service->search('topic', ['limit' => 3]);

    expect($results)->toHaveCount(3);
});

it('returns empty collection when no matches', function () {
    $results = $this->service->search('nonexistent topic');

    expect($results)->toHaveCount(0);
});

// ── createCategory() ────────────────────────────────────────────────────────

it('creates a KB category', function () {
    $category = $this->service->createCategory([
        'name' => 'Troubleshooting',
        'description' => 'Troubleshooting guides',
    ]);

    expect($category)->toBeInstanceOf(KbCategory::class)
        ->and($category->name)->toBe('Troubleshooting')
        ->and($category->slug)->toBe('troubleshooting')
        ->and($category->description)->toBe('Troubleshooting guides');
});

it('auto generates slug for category', function () {
    $category = $this->service->createCategory([
        'name' => 'How To Guides',
    ]);

    expect($category->slug)->toBe('how-to-guides');
});

// ── updateCategory() ────────────────────────────────────────────────────────

it('updates a KB category', function () {
    $category = $this->service->createCategory(['name' => 'Old Name', 'slug' => 'old-name']);

    $updated = $this->service->updateCategory($category, ['name' => 'New Name']);

    expect($updated->name)->toBe('New Name');
});

// ── deleteCategory() ────────────────────────────────────────────────────────

it('deletes a KB category', function () {
    $category = $this->service->createCategory(['name' => 'To Delete', 'slug' => 'to-delete']);
    $id = $category->id;

    $result = $this->service->deleteCategory($category);

    expect($result)->toBeTrue()
        ->and(KbCategory::find($id))->toBeNull();
});

// ── addFeedback() ───────────────────────────────────────────────────────────

it('adds helpful feedback to an article', function () {
    Event::fake($this->kbEvents);

    $article = $this->service->createArticle([
        'category_id' => $this->category->id,
        'title' => 'Feedback test',
        'slug' => 'feedback-test',
        'content' => 'Content for feedback',
    ], $this->author);

    $feedback = $this->service->addFeedback($article, true, $this->author, 'Very helpful!', '127.0.0.1');

    expect($feedback->is_helpful)->toBeTrue()
        ->and($feedback->comment)->toBe('Very helpful!')
        ->and($feedback->ip_address)->toBe('127.0.0.1')
        ->and($article->fresh()->helpful_count)->toBe(1);
});

it('adds not-helpful feedback to an article', function () {
    Event::fake($this->kbEvents);

    $article = $this->service->createArticle([
        'category_id' => $this->category->id,
        'title' => 'Not helpful test',
        'slug' => 'not-helpful-test',
        'content' => 'Content',
    ], $this->author);

    $this->service->addFeedback($article, false, null, 'Needs improvement');

    expect($article->fresh()->not_helpful_count)->toBe(1);
});

// ── linkArticleToTicket() ───────────────────────────────────────────────────

it('links an article to a ticket', function () {
    Event::fake($this->kbEvents);

    $department = Department::create(['name' => 'IT', 'slug' => 'it']);
    $user = User::create(['name' => 'User', 'email' => 'user@example.com']);

    $ticket = Ticket::create([
        'department_id' => $department->id,
        'user_type' => $user->getMorphClass(),
        'user_id' => $user->id,
        'title' => 'Linked ticket',
        'description' => 'Will be linked to article',
    ]);

    $article = $this->service->createArticle([
        'category_id' => $this->category->id,
        'title' => 'Linked article',
        'slug' => 'linked-article',
        'content' => 'Related to ticket',
    ], $this->author);

    $this->service->linkArticleToTicket($article, $ticket);

    expect($article->linkedTickets)->toHaveCount(1)
        ->and($article->linkedTickets->first()->id)->toBe($ticket->id);
});

it('does not duplicate article-ticket link', function () {
    Event::fake($this->kbEvents);

    $department = Department::create(['name' => 'IT', 'slug' => 'it']);
    $user = User::create(['name' => 'User', 'email' => 'user@example.com']);

    $ticket = Ticket::create([
        'department_id' => $department->id,
        'user_type' => $user->getMorphClass(),
        'user_id' => $user->id,
        'title' => 'Dup link ticket',
        'description' => 'No dup links',
    ]);

    $article = $this->service->createArticle([
        'category_id' => $this->category->id,
        'title' => 'Dup link article',
        'slug' => 'dup-link-article',
        'content' => 'Content',
    ], $this->author);

    $this->service->linkArticleToTicket($article, $ticket);
    $this->service->linkArticleToTicket($article, $ticket);

    expect($article->linkedTickets()->count())->toBe(1);
});
