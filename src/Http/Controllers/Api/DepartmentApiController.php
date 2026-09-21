<?php

namespace JeffersonGoncalves\ServiceDesk\Http\Controllers\Api;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use JeffersonGoncalves\ServiceDesk\Http\Resources\CategoryApiResource;
use JeffersonGoncalves\ServiceDesk\Http\Resources\DepartmentApiResource;
use JeffersonGoncalves\ServiceDesk\Models\Department;

/**
 * Discovery endpoints for the ticket API transport -- lets a satellite look
 * up which department_id/category_id values are valid on the central
 * instance instead of hardcoding them. Not app_key-scoped, unlike every
 * other ticket-API controller: these lists aren't tenant data, every
 * satellite sees the same central taxonomy. Still behind the same signed
 * request middleware as the rest of the group.
 */
class DepartmentApiController
{
    public function index(): AnonymousResourceCollection
    {
        return DepartmentApiResource::collection(Department::active()->ordered()->get());
    }

    public function categories(int $department): AnonymousResourceCollection
    {
        $department = Department::findOrFail($department);

        return CategoryApiResource::collection($department->categories()->active()->ordered()->get());
    }
}
