<?php

namespace JeffersonGoncalves\ServiceDesk\Enums;

enum HistoryAction: string
{
    case Created = 'created';
    case StatusChanged = 'status_changed';
    case PriorityChanged = 'priority_changed';
    case Assigned = 'assigned';
    case Unassigned = 'unassigned';
    case DepartmentChanged = 'department_changed';
    case CategoryChanged = 'category_changed';
    case CommentAdded = 'comment_added';
    case AttachmentAdded = 'attachment_added';
    case AttachmentRemoved = 'attachment_removed';
    case Closed = 'closed';
    case Reopened = 'reopened';
    case TitleChanged = 'title_changed';
    case Merged = 'merged';
    case TagsChanged = 'tags_changed';
    case SlaApplied = 'sla_applied';
    case SlaBreached = 'sla_breached';
    case Escalated = 'escalated';
    case ServiceRequestLinked = 'service_request_linked';
    case ArticleLinked = 'article_linked';

    public function label(): string
    {
        return __('service-desk::service-desk.history_action.'.$this->value);
    }
}
