<?php

namespace JeffersonGoncalves\ServiceDesk\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use JeffersonGoncalves\ServiceDesk\Models\Department;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Models\TicketHistory;

trait IsOperator
{
    use HasTickets;

    public function serviceDeskAssignedTickets(): MorphMany
    {
        return $this->morphMany(Ticket::class, 'assigned_to');
    }

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

    public function serviceDeskHistory(): MorphMany
    {
        return $this->morphMany(TicketHistory::class, 'performer');
    }
}
