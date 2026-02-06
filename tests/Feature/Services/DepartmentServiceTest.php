<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use JeffersonGoncalves\ServiceDesk\Models\Department;
use JeffersonGoncalves\ServiceDesk\Services\DepartmentService;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(DepartmentService::class);
});

// ── create() ────────────────────────────────────────────────────────────────

it('creates a department with all fields', function () {
    $department = $this->service->create([
        'name' => 'IT Support',
        'slug' => 'it-support',
        'description' => 'IT Help',
        'email' => 'it@example.com',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    expect($department)->toBeInstanceOf(Department::class)
        ->and($department->name)->toBe('IT Support')
        ->and($department->slug)->toBe('it-support')
        ->and($department->description)->toBe('IT Help')
        ->and($department->email)->toBe('it@example.com')
        ->and($department->is_active)->toBeTrue()
        ->and($department->sort_order)->toBe(1);
});

it('auto generates slug from name when slug is empty', function () {
    $department = $this->service->create([
        'name' => 'Human Resources',
    ]);

    expect($department->slug)->toBe('human-resources');
});

it('uses provided slug when given', function () {
    $department = $this->service->create([
        'name' => 'Human Resources',
        'slug' => 'hr-dept',
    ]);

    expect($department->slug)->toBe('hr-dept');
});

// ── update() ────────────────────────────────────────────────────────────────

it('updates a department', function () {
    $department = $this->service->create(['name' => 'IT Support']);

    $updated = $this->service->update($department, [
        'name' => 'Technical Support',
        'email' => 'tech@example.com',
    ]);

    expect($updated->name)->toBe('Technical Support')
        ->and($updated->slug)->toBe('technical-support')
        ->and($updated->email)->toBe('tech@example.com');
});

it('auto generates new slug when name is updated without slug', function () {
    $department = $this->service->create(['name' => 'IT Support']);

    $updated = $this->service->update($department, ['name' => 'Network Operations']);

    expect($updated->slug)->toBe('network-operations');
});

it('keeps existing slug when only non-name fields are updated', function () {
    $department = $this->service->create(['name' => 'IT Support', 'slug' => 'it-support']);

    $updated = $this->service->update($department, ['email' => 'new@example.com']);

    expect($updated->slug)->toBe('it-support');
});

// ── delete() ────────────────────────────────────────────────────────────────

it('deletes a department', function () {
    $department = $this->service->create(['name' => 'To Delete']);
    $id = $department->id;

    $result = $this->service->delete($department);

    expect($result)->toBeTrue()
        ->and(Department::find($id))->toBeNull();
});

// ── addOperator() ───────────────────────────────────────────────────────────

it('adds an operator to a department', function () {
    $department = $this->service->create(['name' => 'IT Support']);
    $operator = User::create(['name' => 'Operator', 'email' => 'operator@example.com']);

    $this->service->addOperator($department, $operator);

    $operators = $this->service->getOperators($department);

    expect($operators)->toHaveCount(1)
        ->and($operators->first()->operator_id)->toBe($operator->id)
        ->and($operators->first()->role)->toBe('operator');
});

it('adds an operator with a custom role', function () {
    $department = $this->service->create(['name' => 'IT Support']);
    $operator = User::create(['name' => 'Manager', 'email' => 'manager@example.com']);

    $this->service->addOperator($department, $operator, 'manager');

    $operators = $this->service->getOperators($department);

    expect($operators->first()->role)->toBe('manager');
});

it('does not duplicate operators (updateOrInsert)', function () {
    $department = $this->service->create(['name' => 'IT Support']);
    $operator = User::create(['name' => 'Operator', 'email' => 'operator@example.com']);

    $this->service->addOperator($department, $operator, 'operator');
    $this->service->addOperator($department, $operator, 'manager');

    $operators = $this->service->getOperators($department);

    expect($operators)->toHaveCount(1)
        ->and($operators->first()->role)->toBe('manager');
});

it('adds multiple operators to a department', function () {
    $department = $this->service->create(['name' => 'IT Support']);
    $operator1 = User::create(['name' => 'Op 1', 'email' => 'op1@example.com']);
    $operator2 = User::create(['name' => 'Op 2', 'email' => 'op2@example.com']);

    $this->service->addOperator($department, $operator1);
    $this->service->addOperator($department, $operator2);

    $operators = $this->service->getOperators($department);

    expect($operators)->toHaveCount(2);
});

// ── removeOperator() ────────────────────────────────────────────────────────

it('removes an operator from a department', function () {
    $department = $this->service->create(['name' => 'IT Support']);
    $operator = User::create(['name' => 'Operator', 'email' => 'operator@example.com']);

    $this->service->addOperator($department, $operator);
    $this->service->removeOperator($department, $operator);

    $operators = $this->service->getOperators($department);

    expect($operators)->toHaveCount(0);
});

it('removing a non-existent operator does nothing', function () {
    $department = $this->service->create(['name' => 'IT Support']);
    $operator = User::create(['name' => 'Operator', 'email' => 'operator@example.com']);

    $this->service->removeOperator($department, $operator);

    $operators = $this->service->getOperators($department);

    expect($operators)->toHaveCount(0);
});

// ── getOperators() ──────────────────────────────────────────────────────────

it('returns empty collection when no operators', function () {
    $department = $this->service->create(['name' => 'Empty Dept']);

    $operators = $this->service->getOperators($department);

    expect($operators)->toHaveCount(0);
});
