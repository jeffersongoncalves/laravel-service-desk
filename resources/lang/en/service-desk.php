<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ticket Statuses
    |--------------------------------------------------------------------------
    */
    'status' => [
        'open' => 'Open',
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'on_hold' => 'On Hold',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ticket Priorities
    |--------------------------------------------------------------------------
    */
    'priority' => [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ticket Sources
    |--------------------------------------------------------------------------
    */
    'source' => [
        'web' => 'Web',
        'email' => 'Email',
        'api' => 'API',
        'service_request' => 'Service Request',
        'phone' => 'Phone',
        'chat' => 'Chat',
    ],

    /*
    |--------------------------------------------------------------------------
    | Comment Types
    |--------------------------------------------------------------------------
    */
    'comment_type' => [
        'reply' => 'Reply',
        'note' => 'Internal Note',
        'system' => 'System',
    ],

    /*
    |--------------------------------------------------------------------------
    | History Actions
    |--------------------------------------------------------------------------
    */
    'history_action' => [
        'created' => 'Ticket created',
        'status_changed' => 'Status changed to :status',
        'priority_changed' => 'Priority changed to :priority',
        'assigned' => 'Assigned to :agent',
        'unassigned' => 'Agent unassigned',
        'department_changed' => 'Department changed to :department',
        'category_changed' => 'Category changed to :category',
        'comment_added' => 'Comment added',
        'attachment_added' => 'Attachment added',
        'attachment_removed' => 'Attachment removed',
        'closed' => 'Ticket closed',
        'reopened' => 'Ticket reopened',
        'title_changed' => 'Title changed',
        'merged' => 'Ticket merged with :reference',
        'tags_changed' => 'Tags updated',
        'sla_applied' => 'SLA policy applied',
        'sla_breached' => 'SLA breached',
        'escalated' => 'Ticket escalated',
        'service_request_linked' => 'Service request linked',
        'article_linked' => 'Knowledge base article linked',
    ],

    /*
    |--------------------------------------------------------------------------
    | SLA
    |--------------------------------------------------------------------------
    */
    'sla' => [
        'breach_type' => [
            'first_response' => 'First Response',
            'next_response' => 'Next Response',
            'resolution' => 'Resolution',
        ],
        'applied' => 'SLA policy ":policy" applied to ticket :reference',
        'breached' => 'SLA :type breached on ticket :reference',
        'near_breach' => 'SLA :type is about to breach on ticket :reference',
        'metric_met' => 'SLA :type met on ticket :reference',
    ],

    /*
    |--------------------------------------------------------------------------
    | Escalation
    |--------------------------------------------------------------------------
    */
    'escalation' => [
        'action' => [
            'notify' => 'Notify',
            'reassign' => 'Reassign',
            'change_priority' => 'Change Priority',
            'custom' => 'Custom',
        ],
        'triggered' => 'Escalation rule ":rule" triggered on ticket :reference',
    ],

    /*
    |--------------------------------------------------------------------------
    | Automation
    |--------------------------------------------------------------------------
    */
    'automation' => [
        'trigger' => [
            'ticket_created' => 'Ticket Created',
            'ticket_status_changed' => 'Ticket Status Changed',
            'comment_added' => 'Comment Added',
        ],
        'action' => [
            'reassign' => 'Reassign',
            'change_priority' => 'Change Priority',
            'change_status' => 'Change Status',
            'add_tag' => 'Add Tag',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Knowledge Base
    |--------------------------------------------------------------------------
    */
    'knowledge_base' => [
        'article_status' => [
            'draft' => 'Draft',
            'published' => 'Published',
            'archived' => 'Archived',
        ],
        'visibility' => [
            'public' => 'Public',
            'internal' => 'Internal',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Service Catalog
    |--------------------------------------------------------------------------
    */
    'service_catalog' => [
        'visibility' => [
            'public' => 'Public',
            'internal' => 'Internal',
            'draft' => 'Draft',
        ],
        'request_status' => [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'in_progress' => 'In Progress',
            'fulfilled' => 'Fulfilled',
            'cancelled' => 'Cancelled',
        ],
        'approval_status' => [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Form Field Types
    |--------------------------------------------------------------------------
    */
    'form_field_type' => [
        'text' => 'Text',
        'textarea' => 'Text Area',
        'select' => 'Select',
        'checkbox' => 'Checkbox',
        'radio' => 'Radio',
        'date' => 'Date',
        'datetime' => 'Date & Time',
        'file' => 'File',
        'number' => 'Number',
        'email' => 'Email',
        'url' => 'URL',
        'tel' => 'Phone',
        'toggle' => 'Toggle',
    ],

    /*
    |--------------------------------------------------------------------------
    | Days of Week
    |--------------------------------------------------------------------------
    */
    'day_of_week' => [
        'sunday' => 'Sunday',
        'monday' => 'Monday',
        'tuesday' => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday' => 'Thursday',
        'friday' => 'Friday',
        'saturday' => 'Saturday',
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'ticket_created' => [
            'subject' => 'Ticket created',
            'greeting' => 'Hello!',
            'body' => 'Your ticket #:reference (:title) has been created. Our team will review it and get back to you shortly.',
            'department' => 'Department: :department',
            'priority' => 'Priority: :priority',
        ],
        'status_changed' => [
            'subject' => 'Status updated',
            'greeting' => 'Hello!',
            'body' => 'The status of ticket #:reference (:title) has changed.',
            'transition' => 'From :old to :new.',
        ],
        'ticket_assigned' => [
            'subject' => 'Ticket assigned to you',
            'greeting' => 'Hello!',
            'body' => 'Ticket #:reference (:title) has been assigned to you.',
            'priority' => 'Priority: :priority',
            'department' => 'Department: :department',
        ],
        'ticket_closed' => [
            'subject' => 'Ticket closed',
            'greeting' => 'Hello!',
            'body' => 'Ticket #:reference (:title) has been closed.',
            'info' => 'If you believe this was done in error or need further assistance, you may reopen it.',
        ],
        'new_comment' => [
            'subject' => 'New comment',
            'greeting' => 'Hello!',
            'body' => ':author commented on ticket #:reference (:title).',
            'comment_type' => 'Type: :type',
        ],
        'unknown_user' => 'Someone',
        'sla_breached' => [
            'subject' => 'SLA breached',
            'greeting' => 'Hello!',
            'body' => 'The :breach_type SLA was breached on ticket #:reference (:title). Immediate attention is required.',
            'priority' => 'Priority: :priority',
        ],
        'sla_near_breach' => [
            'subject' => 'SLA about to be breached',
            'greeting' => 'Hello!',
            'body' => 'The :breach_type SLA on ticket #:reference (:title) is due in :minutes minutes.',
            'priority' => 'Priority: :priority',
        ],
        'escalation' => [
            'subject' => 'Ticket escalated',
            'greeting' => 'Hello!',
            'body' => 'Ticket #:reference (:title) has been escalated (:breach_type, action: :action).',
            'priority' => 'Priority: :priority',
        ],
        'approval_requested' => [
            'subject' => 'Approval requested: :service',
            'greeting' => 'Hello!',
            'body' => ':requester requested :service, which needs your approval.',
            'action_required' => 'Please review it and record your decision.',
        ],
        'approval_decision' => [
            'subject' => 'Approval decision: :service (:status)',
            'greeting' => 'Hello!',
            'body' => 'Your request for :service was :status.',
            'comment' => 'Comment: :comment',
        ],
        'operator_new_ticket' => [
            'subject' => 'New Ticket: :title',
            'greeting' => 'Hello!',
            'body' => ':requester opened a new support ticket.',
            'title' => 'Title: :title',
            'priority' => 'Priority: :priority',
        ],
        'operator_requester_reply' => [
            'subject' => 'New Reply: :title',
            'greeting' => 'Hello!',
            'body' => ':requester replied to a support ticket.',
            'title' => 'Title: :title',
            'priority' => 'Priority: :priority',
        ],
        'view_ticket' => 'View Ticket',
    ],

    /*
    |--------------------------------------------------------------------------
    | General / Misc
    |--------------------------------------------------------------------------
    */
    'ticket' => 'Ticket',
    'tickets' => 'Tickets',
    'department' => 'Department',
    'category' => 'Category',
    'attachment' => 'Attachment',
    'comment' => 'Comment',
    'tag' => 'Tag',
    'article' => 'Article',
    'service' => 'Service',
];
