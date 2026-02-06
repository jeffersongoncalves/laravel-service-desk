<?php

namespace JeffersonGoncalves\ServiceDesk\Enums;

enum CommentType: string
{
    case Reply = 'reply';
    case Note = 'note';
    case System = 'system';

    public function label(): string
    {
        return __('service-desk::service-desk.comment_type.'.$this->value);
    }
}
