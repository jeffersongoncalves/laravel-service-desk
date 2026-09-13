<?php

namespace JeffersonGoncalves\ServiceDesk\Services;

use Illuminate\Support\Str;
use JeffersonGoncalves\ServiceDesk\Models\Service;
use JeffersonGoncalves\ServiceDesk\Models\ServiceCategory;
use JeffersonGoncalves\ServiceDesk\Models\ServiceFormField;

class ServiceCatalogService
{
    /** @param  array<string, mixed>  $data */
    public function createCategory(array $data): ServiceCategory
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        return ServiceCategory::create($data);
    }

    /** @param  array<string, mixed>  $data */
    public function updateCategory(ServiceCategory $category, array $data): ServiceCategory
    {
        if (isset($data['name']) && empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $category->update($data);

        return $category->fresh() ?? $category;
    }

    public function deleteCategory(ServiceCategory $category): bool
    {
        return (bool) $category->delete();
    }

    /** @param  array<string, mixed>  $data */
    public function createService(array $data): Service
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        return Service::create($data);
    }

    /** @param  array<string, mixed>  $data */
    public function updateService(Service $service, array $data): Service
    {
        if (isset($data['name']) && empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $service->update($data);

        return $service->fresh() ?? $service;
    }

    public function deleteService(Service $service): bool
    {
        return (bool) $service->delete();
    }

    /** @param  array<string, mixed>  $data */
    public function createFormField(Service $service, array $data): ServiceFormField
    {
        $data['service_id'] = $service->id;

        return ServiceFormField::create($data);
    }

    /** @param  array<string, mixed>  $data */
    public function updateFormField(ServiceFormField $formField, array $data): ServiceFormField
    {
        $formField->update($data);

        return $formField->fresh() ?? $formField;
    }

    public function deleteFormField(ServiceFormField $formField): bool
    {
        return (bool) $formField->delete();
    }

    /** @param  array<int, int|string>  $orderedIds */
    public function reorderFormFields(Service $service, array $orderedIds): void
    {
        foreach ($orderedIds as $sortOrder => $fieldId) {
            ServiceFormField::where('id', $fieldId)
                ->where('service_id', $service->id)
                ->update(['sort_order' => $sortOrder]);
        }
    }
}
