<?php

namespace JeffersonGoncalves\ServiceDesk\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case OnHold = 'on_hold';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return __('service-desk::service-desk.status.'.$this->value);
    }

    /**
     * @return array<TicketStatus>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Open => [self::Pending, self::InProgress, self::OnHold, self::Resolved, self::Closed],
            self::Pending => [self::Open, self::InProgress, self::OnHold, self::Resolved, self::Closed],
            self::InProgress => [self::Pending, self::OnHold, self::Resolved, self::Closed],
            self::OnHold => [self::Open, self::Pending, self::InProgress, self::Resolved, self::Closed],
            self::Resolved => [self::Open, self::Closed],
            self::Closed => config('service-desk.ticket.allow_reopen', true) ? [self::Open] : [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions());
    }

    public function pausesSla(): bool
    {
        $pauseStatuses = config('service-desk.sla.pause_on_statuses', ['on_hold']);

        return in_array($this->value, $pauseStatuses);
    }

    /**
     * The canonical linear sequence for a visual progress stepper, distinct
     * from allowedTransitions() which describes the full (non-linear) graph
     * -- e.g. Resolved can go back to Open, which isn't a "step forward".
     * Pending/OnHold are lateral waiting states, not steps of their own.
     *
     * @return array<TicketStatus>
     */
    public static function pipelineSteps(): array
    {
        return [self::Open, self::InProgress, self::Resolved, self::Closed];
    }

    /**
     * 1-based position in pipelineSteps(). Pending/OnHold report the
     * InProgress position since the ticket is still being worked, just
     * temporarily waiting.
     */
    public function pipelineStep(): int
    {
        $status = match ($this) {
            self::Pending, self::OnHold => self::InProgress,
            default => $this,
        };

        foreach (self::pipelineSteps() as $index => $step) {
            if ($step === $status) {
                return $index + 1;
            }
        }

        return 1;
    }
}
