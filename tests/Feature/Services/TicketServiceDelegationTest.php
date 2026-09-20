<?php

use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\ServiceDesk\Contracts\TicketTransport;
use JeffersonGoncalves\ServiceDesk\Enums\TicketStatus;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Services\TicketService;
use JeffersonGoncalves\ServiceDesk\Services\Transports\DatabaseTicketTransport;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

it('resolves TicketTransport to DatabaseTicketTransport by default', function () {
    expect(app(TicketTransport::class))->toBeInstanceOf(DatabaseTicketTransport::class);
});

it('delegates every call to whichever TicketTransport is bound', function () {
    $ticket = Ticket::factory()->create();
    $user = User::create(['name' => 'Someone', 'email' => 'someone@example.com']);
    $calls = [];

    $fake = new class($calls) implements TicketTransport
    {
        public function __construct(private array &$calls) {}

        public function create(array $data, Model $user): Ticket
        {
            $this->calls[] = 'create';

            return Ticket::factory()->make();
        }

        public function update(Ticket $ticket, array $data, ?Model $performer = null): Ticket
        {
            $this->calls[] = 'update';

            return $ticket;
        }

        public function changeStatus(Ticket $ticket, TicketStatus $newStatus, ?Model $performer = null): Ticket
        {
            $this->calls[] = 'changeStatus';

            return $ticket;
        }

        public function assign(Ticket $ticket, Model $operator, ?Model $assignedBy = null): Ticket
        {
            $this->calls[] = 'assign';

            return $ticket;
        }

        public function unassign(Ticket $ticket, ?Model $performer = null): Ticket
        {
            $this->calls[] = 'unassign';

            return $ticket;
        }

        public function close(Ticket $ticket, ?Model $performer = null): Ticket
        {
            $this->calls[] = 'close';

            return $ticket;
        }

        public function reopen(Ticket $ticket, ?Model $performer = null): Ticket
        {
            $this->calls[] = 'reopen';

            return $ticket;
        }

        public function delete(Ticket $ticket, ?Model $performer = null): bool
        {
            $this->calls[] = 'delete';

            return true;
        }

        public function findByUuid(string $uuid): Ticket
        {
            $this->calls[] = 'findByUuid';

            return Ticket::factory()->make();
        }

        public function findByReference(string $reference): Ticket
        {
            $this->calls[] = 'findByReference';

            return Ticket::factory()->make();
        }
    };

    $service = new TicketService($fake);

    $service->create([], $user);
    $service->update($ticket, []);
    $service->changeStatus($ticket, TicketStatus::InProgress);
    $service->assign($ticket, $user);
    $service->unassign($ticket);
    $service->close($ticket);
    $service->reopen($ticket);
    $service->delete($ticket);
    $service->findByUuid('irrelevant');
    $service->findByReference('irrelevant');

    expect($calls)->toBe([
        'create', 'update', 'changeStatus', 'assign', 'unassign',
        'close', 'reopen', 'delete', 'findByUuid', 'findByReference',
    ]);
});
