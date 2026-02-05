<?php

namespace JeffersonGoncalves\ServiceDesk\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use JeffersonGoncalves\ServiceDesk\Models\Tag;

class TagService
{
    public function create(array $data): Tag
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        return Tag::create($data);
    }

    public function update(Tag $tag, array $data): Tag
    {
        if (isset($data['name']) && empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $tag->update($data);

        return $tag->fresh();
    }

    public function delete(Tag $tag): bool
    {
        return $tag->delete();
    }

    public function syncTags(Model $model, array $tagIds): void
    {
        $model->tags()->sync($tagIds);
    }

    public function attachTags(Model $model, array $tagIds): void
    {
        $model->tags()->attach($tagIds);
    }

    public function detachTags(Model $model, array $tagIds): void
    {
        $model->tags()->detach($tagIds);
    }
}
