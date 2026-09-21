<?php

use JeffersonGoncalves\ServiceDesk\Api\ServiceDeskSigner;
use JeffersonGoncalves\ServiceDesk\Models\Category;
use JeffersonGoncalves\ServiceDesk\Models\Department;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

beforeEach(function () {
    config()->set('service-desk.api.clients', [
        'satellite-1' => ['secrets' => ['secret-1'], 'actor_types' => [User::class]],
    ]);

    $this->call = function (string $method, string $appKey, string $secret, string $uri) {
        $signer = new ServiceDeskSigner($appKey, $secret);
        $headers = $signer->headersFor($method, $uri, '');

        return test()->withHeaders($headers)->get($uri);
    };
});

it('lists only active departments', function () {
    Department::factory()->create(['name' => 'Active', 'is_active' => true]);
    Department::factory()->create(['name' => 'Inactive', 'is_active' => false]);

    ($this->call)('GET', 'satellite-1', 'secret-1', '/service-desk/api/departments')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Active');
});

it('lists only active categories for a department', function () {
    $department = Department::factory()->create();
    Category::factory()->create(['department_id' => $department->id, 'name' => 'Active', 'is_active' => true]);
    Category::factory()->create(['department_id' => $department->id, 'name' => 'Inactive', 'is_active' => false]);

    ($this->call)('GET', 'satellite-1', 'secret-1', "/service-desk/api/departments/{$department->id}/categories")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Active');
});

it('404s for a department that does not exist', function () {
    ($this->call)('GET', 'satellite-1', 'secret-1', '/service-desk/api/departments/99999/categories')
        ->assertStatus(404);
});
