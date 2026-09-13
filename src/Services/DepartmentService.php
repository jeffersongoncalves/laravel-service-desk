<?php

namespace JeffersonGoncalves\ServiceDesk\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use JeffersonGoncalves\ServiceDesk\Models\Department;

class DepartmentService
{
    /** @param  array<string, mixed>  $data */
    public function create(array $data): Department
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        return Department::create($data);
    }

    /** @param  array<string, mixed>  $data */
    public function update(Department $department, array $data): Department
    {
        if (isset($data['name']) && empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $department->update($data);

        return $department->fresh() ?? $department;
    }

    public function delete(Department $department): bool
    {
        return (bool) $department->delete();
    }

    public function addOperator(Department $department, Model $operator, string $role = 'operator'): void
    {
        $department->getConnection()
            ->table('service_desk_department_operator')
            ->updateOrInsert(
                [
                    'department_id' => $department->id,
                    'operator_type' => $operator->getMorphClass(),
                    'operator_id' => $operator->getKey(),
                ],
                [
                    'role' => $role,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
    }

    public function removeOperator(Department $department, Model $operator): void
    {
        $department->getConnection()
            ->table('service_desk_department_operator')
            ->where('department_id', $department->id)
            ->where('operator_type', $operator->getMorphClass())
            ->where('operator_id', $operator->getKey())
            ->delete();
    }

    /** @return Collection<int, \stdClass> */
    public function getOperators(Department $department): Collection
    {
        return $department->getConnection()
            ->table('service_desk_department_operator')
            ->where('department_id', $department->id)
            ->get();
    }
}
