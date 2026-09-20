<?php

namespace JeffersonGoncalves\ServiceDesk\Services;

use Illuminate\Support\Collection;
use JeffersonGoncalves\ServiceDesk\Enums\AutomationAction;
use JeffersonGoncalves\ServiceDesk\Enums\AutomationConditionOperator;
use JeffersonGoncalves\ServiceDesk\Enums\AutomationTrigger;
use JeffersonGoncalves\ServiceDesk\Models\AutomationRule;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;

class AutomationService
{
    /**
     * Condition fields are checked against this allow-list -- never
     * interpolated into a raw query -- so a rule's `conditions` JSON can
     * only ever compare a known, safe set of ticket attributes.
     */
    protected const ALLOWED_FIELDS = ['department_id', 'category_id', 'status', 'priority', 'source'];

    public function __construct(
        protected TagService $tagService,
    ) {}

    /**
     * Evaluate and apply the active rules for a given trigger against a
     * ticket. Returns the IDs of rules that matched (whether or not they
     * were actually applied, e.g. in dry-run mode).
     *
     * @return array<int, int>
     */
    public function evaluate(Ticket $ticket, AutomationTrigger $trigger, bool $dryRun = false): array
    {
        if (! config('service-desk.automation.enabled', true)) {
            return [];
        }

        $rules = AutomationRule::query()->active()->forTrigger($trigger)->ordered()->get();

        return $this->applyMatchingRules($ticket, $rules, $dryRun);
    }

    /**
     * Same as evaluate(), but checks every active rule regardless of its
     * trigger_event -- used for a manual sweep (e.g. after adding a new
     * rule, to apply it to already-open tickets) rather than a live event.
     *
     * @return array<int, int>
     */
    public function evaluateAll(Ticket $ticket, bool $dryRun = false): array
    {
        if (! config('service-desk.automation.enabled', true)) {
            return [];
        }

        $rules = AutomationRule::query()->active()->ordered()->get();

        return $this->applyMatchingRules($ticket, $rules, $dryRun);
    }

    /**
     * @param  Collection<int, AutomationRule>  $rules
     * @return array<int, int>
     */
    protected function applyMatchingRules(Ticket $ticket, Collection $rules, bool $dryRun): array
    {
        // ponytail: a rule fires at most once per ticket, ever -- simpler
        // than a call-stack-scoped guard, and enough to stop the loop where
        // one rule's action re-triggers evaluation that matches it again.
        // Upgrade to a per-evaluation-chain guard if "reapply after the
        // ticket changes again" is ever actually needed.
        $applied = $ticket->metadata['automation_applied_rule_ids'] ?? [];
        $matchedIds = [];

        foreach ($rules as $rule) {
            if (in_array($rule->id, $applied, true)) {
                continue;
            }

            if (! $this->conditionsMatch($ticket, $rule->conditions ?? [])) {
                continue;
            }

            $matchedIds[] = $rule->id;

            if ($dryRun) {
                continue;
            }

            $this->applyAction($rule, $ticket);

            $applied[] = $rule->id;
            $metadata = $ticket->metadata ?? [];
            $metadata['automation_applied_rule_ids'] = $applied;
            $ticket->update(['metadata' => $metadata]);
        }

        return $matchedIds;
    }

    /** @param  array<int, array{field?: string, operator?: string, value?: mixed}>  $conditions */
    protected function conditionsMatch(Ticket $ticket, array $conditions): bool
    {
        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? null;

            if (! is_string($field) || ! in_array($field, self::ALLOWED_FIELDS, true)) {
                return false;
            }

            $operator = AutomationConditionOperator::tryFrom((string) ($condition['operator'] ?? ''));

            if (! $operator) {
                return false;
            }

            if (! $this->operatorMatches($operator, $this->fieldValue($ticket, $field), $condition['value'] ?? null)) {
                return false;
            }
        }

        return true;
    }

    protected function fieldValue(Ticket $ticket, string $field): mixed
    {
        $value = $ticket->getAttribute($field);

        return $value instanceof \BackedEnum ? $value->value : $value;
    }

    protected function operatorMatches(AutomationConditionOperator $operator, mixed $actual, mixed $expected): bool
    {
        return match ($operator) {
            AutomationConditionOperator::Equals => $actual == $expected,
            AutomationConditionOperator::NotEquals => $actual != $expected,
            AutomationConditionOperator::In => is_array($expected) && in_array($actual, $expected),
            AutomationConditionOperator::NotIn => is_array($expected) && ! in_array($actual, $expected),
        };
    }

    protected function applyAction(AutomationRule $rule, Ticket $ticket): void
    {
        match ($rule->action) {
            AutomationAction::Reassign => $this->applyReassign($rule, $ticket),
            AutomationAction::ChangePriority => $this->applyChangePriority($rule, $ticket),
            AutomationAction::ChangeStatus => $this->applyChangeStatus($rule, $ticket),
            AutomationAction::AddTag => $this->applyAddTag($rule, $ticket),
        };
    }

    protected function applyReassign(AutomationRule $rule, Ticket $ticket): void
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

    protected function applyChangePriority(AutomationRule $rule, Ticket $ticket): void
    {
        $priority = ($rule->action_config ?? [])['priority'] ?? null;

        if ($priority) {
            $ticket->update(['priority' => $priority]);
        }
    }

    protected function applyChangeStatus(AutomationRule $rule, Ticket $ticket): void
    {
        $status = ($rule->action_config ?? [])['status'] ?? null;

        if ($status) {
            $ticket->update(['status' => $status]);
        }
    }

    protected function applyAddTag(AutomationRule $rule, Ticket $ticket): void
    {
        $tagIds = ($rule->action_config ?? [])['tag_ids'] ?? [];

        if (! empty($tagIds)) {
            $this->tagService->attachTags($ticket, $tagIds);
        }
    }
}
