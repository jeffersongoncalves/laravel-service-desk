<?php

namespace JeffersonGoncalves\ServiceDesk\Facades;

use Illuminate\Support\Facades\Facade;
use JeffersonGoncalves\ServiceDesk\ServiceDeskManager;

/**
 * @method static \JeffersonGoncalves\ServiceDesk\Models\Ticket createTicket(array $data, \Illuminate\Database\Eloquent\Model $user)
 * @method static \JeffersonGoncalves\ServiceDesk\Models\Ticket updateTicket(\JeffersonGoncalves\ServiceDesk\Models\Ticket $ticket, array $data, ?\Illuminate\Database\Eloquent\Model $performer = null)
 * @method static \JeffersonGoncalves\ServiceDesk\Models\Ticket changeStatus(\JeffersonGoncalves\ServiceDesk\Models\Ticket $ticket, \JeffersonGoncalves\ServiceDesk\Enums\TicketStatus $status, ?\Illuminate\Database\Eloquent\Model $performer = null)
 * @method static \JeffersonGoncalves\ServiceDesk\Models\Ticket assignTicket(\JeffersonGoncalves\ServiceDesk\Models\Ticket $ticket, \Illuminate\Database\Eloquent\Model $operator, ?\Illuminate\Database\Eloquent\Model $assignedBy = null)
 * @method static \JeffersonGoncalves\ServiceDesk\Models\Ticket closeTicket(\JeffersonGoncalves\ServiceDesk\Models\Ticket $ticket, ?\Illuminate\Database\Eloquent\Model $performer = null)
 * @method static \JeffersonGoncalves\ServiceDesk\Models\Ticket reopenTicket(\JeffersonGoncalves\ServiceDesk\Models\Ticket $ticket, ?\Illuminate\Database\Eloquent\Model $performer = null)
 * @method static \JeffersonGoncalves\ServiceDesk\Models\Ticket findTicketByUuid(string $uuid)
 * @method static \JeffersonGoncalves\ServiceDesk\Models\Ticket findTicketByReference(string $reference)
 * @method static \JeffersonGoncalves\ServiceDesk\Models\TicketComment addComment(\JeffersonGoncalves\ServiceDesk\Models\Ticket $ticket, \Illuminate\Database\Eloquent\Model $author, string $body, array $options = [])
 * @method static \JeffersonGoncalves\ServiceDesk\Models\TicketComment addNote(\JeffersonGoncalves\ServiceDesk\Models\Ticket $ticket, \Illuminate\Database\Eloquent\Model $author, string $body, array $options = [])
 * @method static \JeffersonGoncalves\ServiceDesk\Models\Department createDepartment(array $data)
 * @method static \JeffersonGoncalves\ServiceDesk\Services\TicketService tickets()
 * @method static \JeffersonGoncalves\ServiceDesk\Services\CommentService comments()
 * @method static \JeffersonGoncalves\ServiceDesk\Services\DepartmentService departments()
 * @method static \JeffersonGoncalves\ServiceDesk\Services\AttachmentService attachments()
 *
 * @see \JeffersonGoncalves\ServiceDesk\ServiceDeskManager
 */
class ServiceDesk extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ServiceDeskManager::class;
    }
}
