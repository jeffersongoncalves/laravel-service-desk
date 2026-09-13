<?php

namespace JeffersonGoncalves\ServiceDesk\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use JeffersonGoncalves\ServiceDesk\Models\Tag;

class TagService
{
    /** @param  array<string, mixed>  $data */
    public function create(array $data): Tag
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        return Tag::create($data);
    }

    /** @param  array<string, mixed>  $data */
    public function update(Tag $tag, array $data): Tag
    {
        if (isset($data['name']) && empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $tag->update($data);

        return $tag->fresh() ?? $tag;
    }

    public function delete(Tag $tag): bool
    {
        return (bool) $tag->delete();
    }

    /** @param  array<int, int|string>  $tagIds */
    public function syncTags(Model $model, array $tagIds): void
    {
        /** @phpstan-ignore method.notFound */
        $model->tags()->sync($tagIds);
    }

    /** @param  array<int, int|string>  $tagIds */
    public function attachTags(Model $model, array $tagIds): void
    {
        /** @phpstan-ignore method.notFound */
        $model->tags()->attach($tagIds);
    }

    /** @param  array<int, int|string>  $tagIds */
    public function detachTags(Model $model, array $tagIds): void
    {
        /** @phpstan-ignore method.notFound */
        $model->tags()->detach($tagIds);
    }
}
