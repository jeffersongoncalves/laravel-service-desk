<?php

namespace JeffersonGoncalves\ServiceDesk\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use JeffersonGoncalves\ServiceDesk\Models\Department;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Models\TicketHistory;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Ticket> $serviceDeskAssignedTickets
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Department> $serviceDeskDepartments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TicketHistory> $serviceDeskHistory
 */
trait IsOperator
{
    use HasTickets;

    /** @return MorphMany<Ticket, $this> */
    public function serviceDeskAssignedTickets(): MorphMany
    {
        return $this->morphMany(Ticket::class, 'assigned_to');
    }

    /** @return MorphToMany<Department, $this> */
    public function serviceDeskDepartments(): MorphToMany
    {
        return $this->morphToMany(
            Department::class,
            'operator',
            'service_desk_department_operator',
            null,
            'department_id'
        )->withPivot('role')->withTimestamps();
    }

    /** @return MorphMany<TicketHistory, $this> */
    public function serviceDeskHistory(): MorphMany
    {
        return $this->morphMany(TicketHistory::class, 'performer');
    }
}
