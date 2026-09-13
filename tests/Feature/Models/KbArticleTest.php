<?php

use JeffersonGoncalves\ServiceDesk\Models\KbArticle;
use JeffersonGoncalves\ServiceDesk\Models\KbCategory;
use JeffersonGoncalves\ServiceDesk\Models\Tag;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

beforeEach(function () {
    $this->category = KbCategory::create(['name' => 'Getting Started', 'slug' => 'getting-started']);
    $this->author = User::create(['name' => 'Author', 'email' => 'author@example.com']);
});

function makeKbArticle(KbCategory $category, User $author, array $overrides = []): KbArticle
{
    return KbArticle::create(array_merge([
        'category_id' => $category->id,
        'title' => 'How to reset your password',
        'content' => 'Steps to reset your password...',
        'author_type' => $author->getMorphClass(),
        'author_id' => $author->id,
    ], $overrides));
}

// ── booted() uuid + slug generation ─────────────────────────────────────────

it('auto generates a uuid on creation', function () {
    $article = makeKbArticle($this->category, $this->author);

    expect($article->uuid)->not->toBeNull()
        ->and($article->uuid)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
});

it('generates the slug from the title', function () {
    $article = makeKbArticle($this->category, $this->author, ['title' => 'How To Reset Your Password']);

    expect($article->slug)->toBe('how-to-reset-your-password');
});

// ── incrementViewCount() ─────────────────────────────────────────────────────

it('increments the view count', function () {
    $article = makeKbArticle($this->category, $this->author, ['view_count' => 0]);

    $article->incrementViewCount();

    expect($article->fresh()->view_count)->toBe(1);
});

// ── relatedArticles() ────────────────────────────────────────────────────────

it('relates articles to each other', function () {
    $article = makeKbArticle($this->category, $this->author);
    $related = makeKbArticle($this->category, $this->author, ['title' => 'Related article']);

    $article->relatedArticles()->attach($related->id);

    expect($article->relatedArticles)->toHaveCount(1)
        ->and($article->relatedArticles->first()->id)->toBe($related->id);
});

// ── tags() ───────────────────────────────────────────────────────────────────

it('can be tagged via morphToMany', function () {
    $article = makeKbArticle($this->category, $this->author);

    $tag = Tag::create(['name' => 'FAQ', 'slug' => 'faq']);
    $article->tags()->attach($tag->id);

    expect($article->tags)->toHaveCount(1)
        ->and($article->tags->first()->name)->toBe('FAQ');
});

// ── route key / slug source ──────────────────────────────────────────────────

it('uses slug as route key name', function () {
    expect((new KbArticle)->getRouteKeyName())->toBe('slug');
});

it('uses title as the slug source', function () {
    expect((new KbArticle)->getSlugSource())->toBe('title');
});
