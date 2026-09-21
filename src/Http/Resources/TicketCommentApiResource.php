<?php

namespace JeffersonGoncalves\ServiceDesk\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JeffersonGoncalves\ServiceDesk\Models\TicketComment;

/**
 * @property TicketComment $resource
 *
 * Keys on `uuid`, same reasoning as TicketApiResource. `is_internal`/note
 * type are never exposed -- this surface only ever creates replies, so
 * every comment reaching a satellite is public by construction, but the
 * field is left out entirely rather than always emitting `false`.
 */
class TicketCommentApiResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->resource->uuid,
            'body' => $this->resource->body,
            'author_name' => $this->resource->author_name,
            'author_email' => $this->resource->author_email,
            'created_at' => $this->resource->created_at?->toIso8601String(),
        ];
    }
}
