<?php

use JeffersonGoncalves\ServiceDesk\Models\Service;
use JeffersonGoncalves\ServiceDesk\Models\ServiceCategory;
use JeffersonGoncalves\ServiceDesk\Models\ServiceFormField;
use JeffersonGoncalves\ServiceDesk\Services\ServiceCatalogService;

beforeEach(function () {
    $this->service = app(ServiceCatalogService::class);
});

// ── createCategory() ────────────────────────────────────────────────────────

it('creates a service category', function () {
    $category = $this->service->createCategory(['name' => 'Hardware']);

    expect($category)->toBeInstanceOf(ServiceCategory::class)
        ->and($category->name)->toBe('Hardware')
        ->and($category->slug)->toBe('hardware');
});

it('keeps an explicitly provided slug for a category', function () {
    $category = $this->service->createCategory(['name' => 'Hardware', 'slug' => 'custom-slug']);

    expect($category->slug)->toBe('custom-slug');
});

// ── updateCategory() ────────────────────────────────────────────────────────

it('updates a service category and regenerates the slug from the new name', function () {
    $category = $this->service->createCategory(['name' => 'Old Name']);

    $updated = $this->service->updateCategory($category, ['name' => 'New Name']);

    expect($updated->name)->toBe('New Name')
        ->and($updated->slug)->toBe('new-name');
});

it('does not overwrite an explicitly provided slug on update', function () {
    $category = $this->service->createCategory(['name' => 'Old Name']);

    $updated = $this->service->updateCategory($category, ['name' => 'New Name', 'slug' => 'kept-slug']);

    expect($updated->slug)->toBe('kept-slug');
});

// ── deleteCategory() ────────────────────────────────────────────────────────

it('deletes a service category', function () {
    $category = $this->service->createCategory(['name' => 'To Delete']);

    $result = $this->service->deleteCategory($category);

    expect($result)->toBeTrue()
        ->and(ServiceCategory::find($category->id))->toBeNull();
});

// ── createService() ─────────────────────────────────────────────────────────

it('creates a service with an auto-generated slug', function () {
    $category = $this->service->createCategory(['name' => 'Hardware']);

    $service = $this->service->createService([
        'category_id' => $category->id,
        'name' => 'New Laptop Request',
        'default_priority' => 'medium',
    ]);

    expect($service)->toBeInstanceOf(Service::class)
        ->and($service->name)->toBe('New Laptop Request')
        ->and($service->slug)->toBe('new-laptop-request')
        ->and($service->category_id)->toBe($category->id);
});

it('keeps an explicitly provided slug for a service', function () {
    $category = $this->service->createCategory(['name' => 'Hardware']);

    $service = $this->service->createService([
        'category_id' => $category->id,
        'name' => 'New Laptop Request',
        'slug' => 'custom-service-slug',
        'default_priority' => 'medium',
    ]);

    expect($service->slug)->toBe('custom-service-slug');
});

// ── updateService() ─────────────────────────────────────────────────────────

it('updates a service and regenerates the slug from the new name', function () {
    $category = $this->service->createCategory(['name' => 'Hardware']);
    $service = $this->service->createService([
        'category_id' => $category->id,
        'name' => 'Old Service Name',
        'default_priority' => 'medium',
    ]);

    $updated = $this->service->updateService($service, ['name' => 'New Service Name']);

    expect($updated->name)->toBe('New Service Name')
        ->and($updated->slug)->toBe('new-service-name');
});

// ── deleteService() ─────────────────────────────────────────────────────────

it('deletes a service', function () {
    $category = $this->service->createCategory(['name' => 'Hardware']);
    $service = $this->service->createService([
        'category_id' => $category->id,
        'name' => 'To Delete',
        'default_priority' => 'medium',
    ]);

    $result = $this->service->deleteService($service);

    expect($result)->toBeTrue()
        ->and(Service::find($service->id))->toBeNull();
});

// ── createFormField() / updateFormField() / deleteFormField() ──────────────

it('creates a form field for a service', function () {
    $category = $this->service->createCategory(['name' => 'Hardware']);
    $service = $this->service->createService([
        'category_id' => $category->id,
        'name' => 'Laptop Request',
        'default_priority' => 'medium',
    ]);

    $field = $this->service->createFormField($service, [
        'name' => 'model',
        'label' => 'Laptop Model',
        'type' => 'text',
        'is_required' => true,
        'sort_order' => 0,
    ]);

    expect($field)->toBeInstanceOf(ServiceFormField::class)
        ->and($field->service_id)->toBe($service->id)
        ->and($field->label)->toBe('Laptop Model')
        ->and($field->is_required)->toBeTrue();
});

it('updates a form field', function () {
    $category = $this->service->createCategory(['name' => 'Hardware']);
    $service = $this->service->createService([
        'category_id' => $category->id,
        'name' => 'Laptop Request',
        'default_priority' => 'medium',
    ]);

    $field = $this->service->createFormField($service, [
        'name' => 'model',
        'label' => 'Laptop Model',
        'type' => 'text',
        'sort_order' => 0,
    ]);

    $updated = $this->service->updateFormField($field, ['label' => 'Preferred Model']);

    expect($updated->label)->toBe('Preferred Model');
});

it('deletes a form field', function () {
    $category = $this->service->createCategory(['name' => 'Hardware']);
    $service = $this->service->createService([
        'category_id' => $category->id,
        'name' => 'Laptop Request',
        'default_priority' => 'medium',
    ]);

    $field = $this->service->createFormField($service, [
        'name' => 'model',
        'label' => 'Laptop Model',
        'type' => 'text',
        'sort_order' => 0,
    ]);

    $result = $this->service->deleteFormField($field);

    expect($result)->toBeTrue()
        ->and(ServiceFormField::find($field->id))->toBeNull();
});

// ── reorderFormFields() ──────────────────────────────────────────────────────

it('reorders form fields by the given id order', function () {
    $category = $this->service->createCategory(['name' => 'Hardware']);
    $service = $this->service->createService([
        'category_id' => $category->id,
        'name' => 'Laptop Request',
        'default_priority' => 'medium',
    ]);

    $first = $this->service->createFormField($service, [
        'name' => 'model', 'label' => 'Model', 'type' => 'text', 'sort_order' => 0,
    ]);
    $second = $this->service->createFormField($service, [
        'name' => 'color', 'label' => 'Color', 'type' => 'text', 'sort_order' => 1,
    ]);

    $this->service->reorderFormFields($service, [$second->id, $first->id]);

    expect($first->fresh()->sort_order)->toBe(1)
        ->and($second->fresh()->sort_order)->toBe(0);
});
