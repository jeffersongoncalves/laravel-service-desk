<?php

namespace JeffersonGoncalves\ServiceDesk\Services;

use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\ServiceDesk\Models\CannedResponse;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;

class CannedResponseService
{
    /** @var array<string, callable(Ticket, ?Model): (string|null)> */
    protected array $resolvers;

    public function __construct()
    {
        $this->resolvers = $this->defaultResolvers();
    }

    /**
     * Register (or override) the resolver for a `{{variable.name}}`
     * placeholder. The callback receives the ticket and the performer
     * passed to render() (typically the acting operator), and returns the
     * substitution value, or null to render it as an empty string.
     *
     * @param  callable(Ticket, ?Model): (string|null)  $resolver
     */
    public function resolveVariable(string $key, callable $resolver): void
    {
        $this->resolvers[$key] = $resolver;
    }

    /**
     * Interpolate `{{variable.name}}` placeholders in a canned response's
     * body. A placeholder with no registered resolver is left untouched
     * rather than silently dropped, so a typo in the template is visible.
     */
    public function render(CannedResponse $response, Ticket $ticket, ?Model $operator = null): string
    {
        $rendered = preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/',
            function (array $matches) use ($ticket, $operator) {
                $key = $matches[1];

                if (! isset($this->resolvers[$key])) {
                    return $matches[0];
                }

                return ($this->resolvers[$key])($ticket, $operator) ?? '';
            },
            $response->body
        );

        return $rendered ?? $response->body;
    }

    /** @return array<string, callable(Ticket, ?Model): (string|null)> */
    protected function defaultResolvers(): array
    {
        return [
            'ticket.reference' => fn (Ticket $ticket) => $ticket->reference_number,
            'ticket.title' => fn (Ticket $ticket) => $ticket->title,
            'ticket.status' => fn (Ticket $ticket) => $ticket->status->label(),
            'ticket.priority' => fn (Ticket $ticket) => $ticket->priority->label(),
            'requester.name' => fn (Ticket $ticket) => $ticket->user?->getAttribute('name'),
            'requester.email' => fn (Ticket $ticket) => $ticket->user?->getAttribute('email'),
            'operator.name' => fn (Ticket $ticket, ?Model $operator) => $operator?->getAttribute('name') ?? $ticket->assignedTo?->getAttribute('name'),
            'operator.email' => fn (Ticket $ticket, ?Model $operator) => $operator?->getAttribute('email') ?? $ticket->assignedTo?->getAttribute('email'),
        ];
    }
}
