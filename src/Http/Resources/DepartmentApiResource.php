<?php

namespace JeffersonGoncalves\ServiceDesk\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JeffersonGoncalves\ServiceDesk\Models\Department;

/**
 * @property Department $resource
 *
 * Unscoped, unsigned-id-keyed -- unlike TicketApiResource, this is just
 * create-form data (the ids a satellite must send back as department_id),
 * not tenant data, so exposing the auto-increment id is the point.
 */
class DepartmentApiResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
        ];
    }
}
