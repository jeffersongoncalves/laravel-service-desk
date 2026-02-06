<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use JeffersonGoncalves\ServiceDesk\Models\Category;
use JeffersonGoncalves\ServiceDesk\Models\Department;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

uses(RefreshDatabase::class);

// ── CRUD ────────────────────────────────────────────────────────────────────

it('can create a department', function () {
    $department = Department::create([
        'name' => 'IT Support',
        'slug' => 'it-support',
        'description' => 'Handles IT issues',
        'email' => 'it@example.com',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    expect($department)->toBeInstanceOf(Department::class)
        ->and($department->name)->toBe('IT Support')
        ->and($department->slug)->toBe('it-support')
        ->and($department->description)->toBe('Handles IT issues')
        ->and($department->email)->toBe('it@example.com')
        ->and($department->is_active)->toBeTrue()
        ->and($department->sort_order)->toBe(1);
});

it('can update a department', function () {
    $department = Department::create([
        'name' => 'IT Support',
        'slug' => 'it-support',
        'is_active' => true,
    ]);

    $department->update(['name' => 'Technical Support', 'slug' => 'technical-support']);

    expect($department->fresh())
        ->name->toBe('Technical Support')
        ->slug->toBe('technical-support');
});

it('can soft delete a department', function () {
    $department = Department::create([
        'name' => 'IT Support',
        'slug' => 'it-support',
    ]);

    $department->delete();

    expect(Department::find($department->id))->toBeNull()
        ->and(Department::withTrashed()->find($department->id))->not->toBeNull();
});

it('can restore a soft deleted department', function () {
    $department = Department::create([
        'name' => 'IT Support',
        'slug' => 'it-support',
    ]);

    $department->delete();
    $department->restore();

    expect(Department::find($department->id))->not->toBeNull();
});

// ── Casts ───────────────────────────────────────────────────────────────────

it('casts is_active to boolean', function () {
    $department = Department::create([
        'name' => 'IT Support',
        'slug' => 'it-support',
        'is_active' => 1,
    ]);

    expect($department->is_active)->toBeTrue()->toBeBool();
});

it('casts sort_order to integer', function () {
    $department = Department::create([
        'name' => 'IT Support',
        'slug' => 'it-support',
        'sort_order' => '5',
    ]);

    expect($department->sort_order)->toBe(5)->toBeInt();
});

// ── Relationships ───────────────────────────────────────────────────────────

it('has many tickets', function () {
    $user = User::create(['name' => 'John', 'email' => 'john@example.com']);
    $department = Department::create(['name' => 'IT', 'slug' => 'it']);

    Ticket::create([
        'department_id' => $department->id,
        'user_type' => $user->getMorphClass(),
        'user_id' => $user->id,
        'title' => 'Test Ticket',
        'description' => 'A test ticket',
    ]);

    expect($department->tickets)->toHaveCount(1)
        ->and($department->tickets->first())->toBeInstanceOf(Ticket::class);
});

it('has many categories', function () {
    $department = Department::create(['name' => 'IT', 'slug' => 'it']);

    Category::create([
        'department_id' => $department->id,
        'name' => 'Hardware',
        'slug' => 'hardware',
    ]);

    expect($department->categories)->toHaveCount(1)
        ->and($department->categories->first())->toBeInstanceOf(Category::class);
});

// ── Scopes ──────────────────────────────────────────────────────────────────

it('filters active departments', function () {
    Department::create(['name' => 'Active Dept', 'slug' => 'active', 'is_active' => true]);
    Department::create(['name' => 'Inactive Dept', 'slug' => 'inactive', 'is_active' => false]);

    $active = Department::active()->get();

    expect($active)->toHaveCount(1)
        ->and($active->first()->name)->toBe('Active Dept');
});

it('orders departments by sort_order then name', function () {
    Department::create(['name' => 'Zebra', 'slug' => 'zebra', 'sort_order' => 1]);
    Department::create(['name' => 'Alpha', 'slug' => 'alpha', 'sort_order' => 1]);
    Department::create(['name' => 'First', 'slug' => 'first', 'sort_order' => 0]);

    $ordered = Department::ordered()->get();

    expect($ordered->pluck('name')->toArray())->toBe(['First', 'Alpha', 'Zebra']);
});

// ── Uniqueness ──────────────────────────────────────────────────────────────

it('enforces unique name constraint', function () {
    Department::create(['name' => 'IT Support', 'slug' => 'it-support']);
    Department::create(['name' => 'IT Support', 'slug' => 'it-support-2']);
})->throws(\Illuminate\Database\QueryException::class);

it('enforces unique slug constraint', function () {
    Department::create(['name' => 'IT Support', 'slug' => 'it-support']);
    Department::create(['name' => 'IT Support 2', 'slug' => 'it-support']);
})->throws(\Illuminate\Database\QueryException::class);
