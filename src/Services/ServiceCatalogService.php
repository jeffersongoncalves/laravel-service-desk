<?php

namespace JeffersonGoncalves\ServiceDesk\Services;

use Illuminate\Support\Str;
use JeffersonGoncalves\ServiceDesk\Models\Service;
use JeffersonGoncalves\ServiceDesk\Models\ServiceCategory;
use JeffersonGoncalves\ServiceDesk\Models\ServiceFormField;

class ServiceCatalogService
{
    public function createCategory(array $data): ServiceCategory
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        return ServiceCategory::create($data);
    }

    public function updateCategory(ServiceCategory $category, array $data): ServiceCategory
    {
        if (isset($data['name']) && empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $category->update($data);

        return $category->fresh();
    }

    public function deleteCategory(ServiceCategory $category): bool
    {
        return $category->delete();
    }

    public function createService(array $data): Service
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        return Service::create($data);
    }

    public function updateService(Service $service, array $data): Service
    {
        if (isset($data['name']) && empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $service->update($data);

        return $service->fresh();
    }

    public function deleteService(Service $service): bool
    {
        return $service->delete();
    }

    public function createFormField(Service $service, array $data): ServiceFormField
    {
        $data['service_id'] = $service->id;

        return ServiceFormField::create($data);
    }

    public function updateFormField(ServiceFormField $formField, array $data): ServiceFormField
    {
        $formField->update($data);

        return $formField->fresh();
    }

    public function deleteFormField(ServiceFormField $formField): bool
    {
        return $formField->delete();
    }

    public function reorderFormFields(Service $service, array $orderedIds): void
    {
        foreach ($orderedIds as $sortOrder => $fieldId) {
            ServiceFormField::where('id', $fieldId)
                ->where('service_id', $service->id)
                ->update(['sort_order' => $sortOrder]);
        }
    }
}
