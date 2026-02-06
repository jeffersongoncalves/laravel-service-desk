<?php

namespace JeffersonGoncalves\ServiceDesk\Services;

use Carbon\Carbon;
use JeffersonGoncalves\ServiceDesk\Contracts\EscalationHandler;
use JeffersonGoncalves\ServiceDesk\Enums\EscalationAction;
use JeffersonGoncalves\ServiceDesk\Enums\SlaBreachType;
use JeffersonGoncalves\ServiceDesk\Enums\TicketStatus;
use JeffersonGoncalves\ServiceDesk\Events\EscalationTriggered;
use JeffersonGoncalves\ServiceDesk\Models\EscalationRule;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Models\TicketSla;
use JeffersonGoncalves\ServiceDesk\Notifications\EscalationNotification;

class EscalationService implements EscalationHandler
{
    public function handle(EscalationRule $rule, Ticket $ticket): void
    {
        match ($rule->action) {
            EscalationAction::Notify => $this->processNotify($rule, $ticket),
            EscalationAction::Reassign => $this->processReassign($rule, $ticket),
            EscalationAction::ChangePriority => $this->processChangePriority($rule, $ticket),
            EscalationAction::Custom => $this->processCustom($rule, $ticket),
        };

        event(new EscalationTriggered($ticket, $rule));
    }

    protected function processNotify(EscalationRule $rule, Ticket $ticket): void
    {
        $config = $rule->action_config ?? [];
        $notifyUsers = $config['notify_users'] ?? [];

        $userModel = config('service-desk.models.operator', config('service-desk.models.user'));

        foreach ($notifyUsers as $userId) {
            $user = $userModel::find($userId);

            if ($user && method_exists($user, 'notify')) {
                $user->notify(new EscalationNotification($ticket, $rule));
            }
        }

        if ($ticket->assignedTo && ! in_array($ticket->assigned_to_id, $notifyUsers)) {
            /** @phpstan-ignore method.notFound */
            $ticket->assignedTo->notify(new EscalationNotification($ticket, $rule));
        }
    }

    protected function processReassign(EscalationRule $rule, Ticket $ticket): void
    {
        $config = $rule->action_config ?? [];
        $assignToId = $config['assign_to_id'] ?? null;
        $assignToType = $config['assign_to_type'] ?? config('service-desk.models.operator', config('service-desk.models.user'));

        if ($assignToId) {
            $ticket->update([
                'assigned_to_type' => $assignToType,
                'assigned_to_id' => $assignToId,
            ]);
        }
    }

    protected function processChangePriority(EscalationRule $rule, Ticket $ticket): void
    {
        $config = $rule->action_config ?? [];
        $newPriority = $config['priority'] ?? null;

        if ($newPriority) {
            $ticket->update([
                'priority' => $newPriority,
            ]);
        }
    }

    protected function processCustom(EscalationRule $rule, Ticket $ticket): void
    {
        $config = $rule->action_config ?? [];
        $handlerClass = $config['handler'] ?? null;

        if ($handlerClass && class_exists($handlerClass)) {
            $handler = app($handlerClass);

            if ($handler instanceof EscalationHandler) {
                $handler->handle($rule, $ticket);
            }
        }
    }

    public function processPendingEscalations(): void
    {
        $now = Carbon::now();

        $ticketSlas = TicketSla::query()
            ->whereHas('ticket', function ($query) {
                $query->whereNotIn('status', [
                    TicketStatus::Closed->value,
                    TicketStatus::Resolved->value,
                ]);
            })
            ->whereNull('paused_at')
            ->with(['ticket', 'slaPolicy.escalationRules' => function ($query) {
                $query->active()->ordered();
            }])
            ->get();

        foreach ($ticketSlas as $ticketSla) {
            $rules = $ticketSla->slaPolicy->escalationRules ?? collect();

            foreach ($rules as $rule) {
                if ($this->shouldTriggerRule($rule, $ticketSla, $now)) {
                    $this->handle($rule, $ticketSla->ticket);
                }
            }
        }
    }

    protected function shouldTriggerRule(EscalationRule $rule, TicketSla $ticketSla, Carbon $now): bool
    {
        $dueAt = match ($rule->breach_type) {
            SlaBreachType::FirstResponse => $ticketSla->first_response_due_at,
            SlaBreachType::NextResponse => $ticketSla->next_response_due_at,
            SlaBreachType::Resolution => $ticketSla->resolution_due_at,
        };

        if (! $dueAt) {
            return false;
        }

        $alreadyBreached = match ($rule->breach_type) {
            SlaBreachType::FirstResponse => $ticketSla->first_responded_at !== null,
            SlaBreachType::NextResponse => false,
            SlaBreachType::Resolution => $ticketSla->resolved_at !== null,
        };

        if ($alreadyBreached) {
            return false;
        }

        if ($rule->trigger_type === 'before') {
            $triggerAt = $dueAt->copy()->subMinutes($rule->minutes_before);

            return $now->greaterThanOrEqualTo($triggerAt) && $now->lessThan($dueAt);
        }

        if ($rule->trigger_type === 'after') {
            $triggerAt = $dueAt->copy()->addMinutes($rule->minutes_before);

            return $now->greaterThanOrEqualTo($triggerAt);
        }

        return false;
    }
}
