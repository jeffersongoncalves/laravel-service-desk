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
            'subject' => 'Ticket #:reference Created: :title',
            'greeting' => 'Hello :name,',
            'body' => 'A new ticket has been created with reference #:reference. Our team will review it and get back to you shortly.',
            'action' => 'View Ticket',
        ],
        'ticket_status_changed' => [
            'subject' => 'Ticket #:reference Status Updated',
            'body' => 'The status of ticket #:reference (:title) has been changed to :status.',
        ],
        'ticket_assigned' => [
            'subject' => 'Ticket #:reference Assigned to You',
            'body' => 'Ticket #:reference (:title) has been assigned to you. Please review it at your earliest convenience.',
        ],
        'ticket_closed' => [
            'subject' => 'Ticket #:reference Closed',
            'body' => 'Ticket #:reference (:title) has been closed. If you believe this was done in error or need further assistance, you may reopen it.',
        ],
        'comment_added' => [
            'subject' => 'New Comment on Ticket #:reference',
            'body' => 'A new comment has been added to ticket #:reference (:title) by :author.',
        ],
        'sla_breached' => [
            'subject' => 'SLA Breached on Ticket #:reference',
            'body' => 'The :type SLA has been breached on ticket #:reference (:title). Immediate attention is required.',
        ],
        'sla_near_breach' => [
            'subject' => 'SLA Near Breach on Ticket #:reference',
            'body' => 'The :type SLA on ticket #:reference (:title) is approaching its deadline. Please take action to avoid a breach.',
        ],
        'escalation' => [
            'subject' => 'Ticket #:reference Escalated',
            'body' => 'Ticket #:reference (:title) has been escalated due to rule ":rule". Please review and take appropriate action.',
        ],
        'approval_requested' => [
            'subject' => 'Approval Requested for Service Request #:reference',
            'body' => 'Your approval is required for service request #:reference (:title). Please review and provide your decision.',
        ],
        'approval_decision' => [
            'subject' => 'Approval Decision on Service Request #:reference',
            'body' => 'The service request #:reference (:title) has been :decision by :approver.',
        ],
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
