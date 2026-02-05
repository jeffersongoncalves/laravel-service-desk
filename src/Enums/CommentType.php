<?php

namespace JeffersonGoncalves\ServiceDesk\Enums;

enum CommentType: string
{
    case Reply = 'reply';
    case Note = 'note';
    case System = 'system';
}
