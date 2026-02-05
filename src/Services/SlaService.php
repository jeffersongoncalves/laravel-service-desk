<?php

namespace JeffersonGoncalves\ServiceDesk\Services;

use Carbon\Carbon;
use JeffersonGoncalves\ServiceDesk\Contracts\SlaCalculator;
use JeffersonGoncalves\ServiceDesk\Enums\SlaBreachType;
use JeffersonGoncalves\ServiceDesk\Enums\TicketStatus;
use JeffersonGoncalves\ServiceDesk\Events\SlaApplied;
use JeffersonGoncalves\ServiceDesk\Events\SlaBreached;
use JeffersonGoncalves\ServiceDesk\Events\SlaNearBreach;
use JeffersonGoncalves\ServiceDesk\Models\SlaPolicy;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Models\TicketSla;

class SlaService
{
    public function __construct(
        protected SlaCalculator $calculator,
    ) {}

    public function applyPolicy(Ticket $ticket, ?SlaPolicy $policy = null): ?TicketSla
    {
        if (! config('service-desk.sla.enabled', true)) {
            return null;
        }

        $policy = $policy ?? $this->findMatchingPolicy($ticket);

        if (! $policy) {
            return null;
        }

        $target = $policy->targets()
            ->where('priority', $ticket->priority?->value ?? $ticket->priority)
            ->first();

        if (! $target) {
            return null;
        }

        $now = Carbon::now();
        $schedule = $policy->businessHoursSchedule;

        $firstResponseDueAt = $target->first_response_time
            ? $this->calculator->calculateDueDate($now->copy(), $target->first_response_time, $schedule)
            : null;

        $nextResponseDueAt = $target->next_response_time
            ? $this->calculator->calculateDueDate($now->copy(), $target->next_response_time, $schedule)
            : null;

        $resolutionDueAt = $target->resolution_time
            ? $this->calculator->calculateDueDate($now->copy(), $target->resolution_time, $schedule)
            : null;

        $ticketSla = TicketSla::updateOrCreate(
            ['ticket_id' => $ticket->id],
            [
                'sla_policy_id' => $policy->id,
                'priority_at_assignment' => $ticket->priority?->value ?? $ticket->priority,
                'first_response_due_at' => $firstResponseDueAt,
                'next_response_due_at' => $nextResponseDueAt,
                'resolution_due_at' => $resolutionDueAt,
            ]
        );

        $ticket->update(['sla_policy_id' => $policy->id]);

        event(new SlaApplied($ticket, $ticketSla));

        return $ticketSla;
    }

    public function findMatchingPolicy(Ticket $ticket): ?SlaPolicy
    {
        $policies = SlaPolicy::active()->ordered()->get();

        foreach ($policies as $policy) {
            if ($this->policyMatchesTicket($policy, $ticket)) {
                return $policy;
            }
        }

        return null;
    }

    public function checkBreaches(): void
    {
        $now = Carbon::now();

        $ticketSlas = TicketSla::query()
            ->whereHas('ticket', function ($query) {
                $query->whereNotIn('status', [
                    TicketStatus::Closed->value,
                    TicketStatus::Resolved->value,
                ]);
            })
            ->where(function ($query) use ($now) {
                $query->where(function ($q) use ($now) {
                    $q->where('first_response_breached', false)
                        ->whereNotNull('first_response_due_at')
                        ->whereNull('first_responded_at')
                        ->where('first_response_due_at', '<', $now);
                })->orWhere(function ($q) use ($now) {
                    $q->where('next_response_breached', false)
                        ->whereNotNull('next_response_due_at')
                        ->where('next_response_due_at', '<', $now);
                })->orWhere(function ($q) use ($now) {
                    $q->where('resolution_breached', false)
                        ->whereNotNull('resolution_due_at')
                        ->whereNull('resolved_at')
                        ->where('resolution_due_at', '<', $now);
                });
            })
            ->with('ticket')
            ->get();

        foreach ($ticketSlas as $ticketSla) {
            if (! $ticketSla->first_response_breached
                && $ticketSla->first_response_due_at
                && $ticketSla->first_responded_at === null
                && $ticketSla->first_response_due_at->isPast()
            ) {
                $ticketSla->update(['first_response_breached' => true]);
                event(new SlaBreached($ticketSla->ticket, $ticketSla, SlaBreachType::FirstResponse->value));
            }

            if (! $ticketSla->next_response_breached
                && $ticketSla->next_response_due_at
                && $ticketSla->next_response_due_at->isPast()
            ) {
                $ticketSla->update(['next_response_breached' => true]);
                event(new SlaBreached($ticketSla->ticket, $ticketSla, SlaBreachType::NextResponse->value));
            }

            if (! $ticketSla->resolution_breached
                && $ticketSla->resolution_due_at
                && $ticketSla->resolved_at === null
                && $ticketSla->resolution_due_at->isPast()
            ) {
                $ticketSla->update(['resolution_breached' => true]);
                event(new SlaBreached($ticketSla->ticket, $ticketSla, SlaBreachType::Resolution->value));
            }
        }
    }

    public function checkNearBreaches(): void
    {
        $now = Carbon::now();
        $nearBreachMinutes = (int) config('service-desk.sla.near_breach_minutes', 30);

        $ticketSlas = TicketSla::query()
            ->whereHas('ticket', function ($query) {
                $query->whereNotIn('status', [
                    TicketStatus::Closed->value,
                    TicketStatus::Resolved->value,
                ]);
            })
            ->where(function ($query) use ($now, $nearBreachMinutes) {
                $threshold = $now->copy()->addMinutes($nearBreachMinutes);

                $query->where(function ($q) use ($now, $threshold) {
                    $q->where('first_response_breached', false)
                        ->whereNotNull('first_response_due_at')
                        ->whereNull('first_responded_at')
                        ->where('first_response_due_at', '>', $now)
                        ->where('first_response_due_at', '<=', $threshold);
                })->orWhere(function ($q) use ($now, $threshold) {
                    $q->where('next_response_breached', false)
                        ->whereNotNull('next_response_due_at')
                        ->where('next_response_due_at', '>', $now)
                        ->where('next_response_due_at', '<=', $threshold);
                })->orWhere(function ($q) use ($now, $threshold) {
                    $q->where('resolution_breached', false)
                        ->whereNotNull('resolution_due_at')
                        ->whereNull('resolved_at')
                        ->where('resolution_due_at', '>', $now)
                        ->where('resolution_due_at', '<=', $threshold);
                });
            })
            ->with('ticket')
            ->get();

        foreach ($ticketSlas as $ticketSla) {
            if (! $ticketSla->first_response_breached
                && $ticketSla->first_response_due_at
                && $ticketSla->first_responded_at === null
                && $ticketSla->first_response_due_at->isFuture()
                && $ticketSla->first_response_due_at->diffInMinutes($now) <= $nearBreachMinutes
            ) {
                $minutesRemaining = (int) $now->diffInMinutes($ticketSla->first_response_due_at);
                event(new SlaNearBreach($ticketSla->ticket, $ticketSla, SlaBreachType::FirstResponse->value, $minutesRemaining));
            }

            if (! $ticketSla->next_response_breached
                && $ticketSla->next_response_due_at
                && $ticketSla->next_response_due_at->isFuture()
                && $ticketSla->next_response_due_at->diffInMinutes($now) <= $nearBreachMinutes
            ) {
                $minutesRemaining = (int) $now->diffInMinutes($ticketSla->next_response_due_at);
                event(new SlaNearBreach($ticketSla->ticket, $ticketSla, SlaBreachType::NextResponse->value, $minutesRemaining));
            }

            if (! $ticketSla->resolution_breached
                && $ticketSla->resolution_due_at
                && $ticketSla->resolved_at === null
                && $ticketSla->resolution_due_at->isFuture()
                && $ticketSla->resolution_due_at->diffInMinutes($now) <= $nearBreachMinutes
            ) {
                $minutesRemaining = (int) $now->diffInMinutes($ticketSla->resolution_due_at);
                event(new SlaNearBreach($ticketSla->ticket, $ticketSla, SlaBreachType::Resolution->value, $minutesRemaining));
            }
        }
    }

    protected function policyMatchesTicket(SlaPolicy $policy, Ticket $ticket): bool
    {
        $conditions = $policy->conditions;

        if (empty($conditions)) {
            return true;
        }

        if (! empty($conditions['department_ids'])) {
            if (! in_array($ticket->department_id, $conditions['department_ids'])) {
                return false;
            }
        }

        if (! empty($conditions['category_ids'])) {
            if (! in_array($ticket->category_id, $conditions['category_ids'])) {
                return false;
            }
        }

        if (! empty($conditions['priorities'])) {
            $ticketPriority = $ticket->priority?->value ?? $ticket->priority;
            if (! in_array($ticketPriority, $conditions['priorities'])) {
                return false;
            }
        }

        if (! empty($conditions['sources'])) {
            $ticketSource = $ticket->source?->value ?? $ticket->source;
            if (! in_array($ticketSource, $conditions['sources'])) {
                return false;
            }
        }

        return true;
    }
}
