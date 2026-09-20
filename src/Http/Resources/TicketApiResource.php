<?php

namespace JeffersonGoncalves\ServiceDesk\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;

/**
 * @property Ticket $resource
 *
 * Explicit field list, never a wholesale toArray() -- a satellite app gets
 * exactly the fields it needs and nothing this app didn't mean to expose.
 * Keys on `uuid`, never `id`: the auto-increment primary key is this app's
 * own row identity, meaningless (and needlessly informative, e.g. total
 * ticket count) to another app.
 */
class TicketApiResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->resource->uuid,
            'reference_number' => $this->resource->reference_number,
            'department_id' => $this->resource->department_id,
            'category_id' => $this->resource->category_id,
            'title' => $this->resource->title,
            'description' => $this->resource->description,
            'status' => $this->resource->status->value,
            'priority' => $this->resource->priority->value,
            'source' => $this->resource->source,
            'requester_name' => $this->resource->user_name,
            'requester_email' => $this->resource->user_email,
            'assigned_to_name' => $this->resource->assigned_to_name,
            'assigned_to_email' => $this->resource->assigned_to_email,
            'due_at' => $this->resource->due_at?->toIso8601String(),
            'closed_at' => $this->resource->closed_at?->toIso8601String(),
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}
