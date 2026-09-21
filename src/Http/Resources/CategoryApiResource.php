<?php

namespace JeffersonGoncalves\ServiceDesk\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JeffersonGoncalves\ServiceDesk\Models\Category;

/**
 * @property Category $resource
 *
 * Same reasoning as DepartmentApiResource -- create-form data, id exposed
 * on purpose since it's the category_id a satellite sends back.
 */
class CategoryApiResource extends JsonResource
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
