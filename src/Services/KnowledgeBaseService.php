<?php

namespace JeffersonGoncalves\ServiceDesk\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use JeffersonGoncalves\ServiceDesk\Contracts\KnowledgeBaseSearchable;
use JeffersonGoncalves\ServiceDesk\Enums\ArticleStatus;
use JeffersonGoncalves\ServiceDesk\Events\ArticleCreated;
use JeffersonGoncalves\ServiceDesk\Events\ArticleFeedbackReceived;
use JeffersonGoncalves\ServiceDesk\Events\ArticlePublished;
use JeffersonGoncalves\ServiceDesk\Models\KbArticle;
use JeffersonGoncalves\ServiceDesk\Models\KbArticleFeedback;
use JeffersonGoncalves\ServiceDesk\Models\KbCategory;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;

class KnowledgeBaseService implements KnowledgeBaseSearchable
{
    public function createArticle(array $data, Model $author): KbArticle
    {
        return DB::transaction(function () use ($data, $author) {
            $article = new KbArticle;
            $article->fill($data);
            $article->author_type = $author->getMorphClass();
            $article->author_id = $author->getKey();
            $article->save();

            $article->versions()->create([
                'version_number' => 1,
                'title' => $article->title,
                'content' => $article->content,
                'excerpt' => $article->excerpt,
                'editor_type' => $author->getMorphClass(),
                'editor_id' => $author->getKey(),
                'change_notes' => 'Initial version',
                'created_at' => now(),
            ]);

            event(new ArticleCreated($article));

            return $article;
        });
    }

    public function updateArticle(KbArticle $article, array $data, Model $editor, ?string $changeNotes = null): KbArticle
    {
        return DB::transaction(function () use ($article, $data, $editor, $changeNotes) {
            $article->fill($data);
            $article->save();

            if (config('service-desk.knowledge_base.versioning_enabled', true)) {
                $newVersion = $article->current_version + 1;

                $article->versions()->create([
                    'version_number' => $newVersion,
                    'title' => $article->title,
                    'content' => $article->content,
                    'excerpt' => $article->excerpt,
                    'editor_type' => $editor->getMorphClass(),
                    'editor_id' => $editor->getKey(),
                    'change_notes' => $changeNotes,
                    'created_at' => now(),
                ]);

                $article->update(['current_version' => $newVersion]);
            }

            return $article->fresh();
        });
    }

    public function publishArticle(KbArticle $article): KbArticle
    {
        $article->update([
            'status' => ArticleStatus::Published,
            'published_at' => now(),
        ]);

        event(new ArticlePublished($article));

        return $article->fresh();
    }

    public function archiveArticle(KbArticle $article): KbArticle
    {
        $article->update([
            'status' => ArticleStatus::Archived,
        ]);

        return $article->fresh();
    }

    public function deleteArticle(KbArticle $article): bool
    {
        return $article->delete();
    }

    public function createCategory(array $data): KbCategory
    {
        if (! isset($data['slug']) && isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        return KbCategory::create($data);
    }

    public function updateCategory(KbCategory $category, array $data): KbCategory
    {
        $category->update($data);

        return $category->fresh();
    }

    public function deleteCategory(KbCategory $category): bool
    {
        return $category->delete();
    }

    public function addFeedback(KbArticle $article, bool $isHelpful, ?Model $user = null, ?string $comment = null, ?string $ipAddress = null): KbArticleFeedback
    {
        $feedback = $article->feedback()->create([
            'user_type' => $user?->getMorphClass(),
            'user_id' => $user?->getKey(),
            'is_helpful' => $isHelpful,
            'comment' => $comment,
            'ip_address' => $ipAddress,
            'created_at' => now(),
        ]);

        if ($isHelpful) {
            $article->increment('helpful_count');
        } else {
            $article->increment('not_helpful_count');
        }

        event(new ArticleFeedbackReceived($article, $feedback));

        return $feedback;
    }

    public function search(string $query, array $options = []): Collection
    {
        $builder = KbArticle::query()
            ->published()
            ->where(function ($q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                    ->orWhere('content', 'LIKE', "%{$query}%");
            });

        if (isset($options['category_id'])) {
            $builder->where('category_id', $options['category_id']);
        }

        if (isset($options['visibility'])) {
            $builder->where('visibility', $options['visibility']);
        }

        $limit = $options['limit'] ?? 20;

        return $builder->orderByDesc('view_count')
            ->limit($limit)
            ->get();
    }

    public function linkArticleToTicket(KbArticle $article, Ticket $ticket): void
    {
        $article->linkedTickets()->syncWithoutDetaching([$ticket->id]);
    }
}
