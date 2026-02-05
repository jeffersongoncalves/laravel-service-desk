<?php

namespace JeffersonGoncalves\ServiceDesk\Enums;

enum FormFieldType: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Select = 'select';
    case Checkbox = 'checkbox';
    case Radio = 'radio';
    case Date = 'date';
    case DateTime = 'datetime';
    case File = 'file';
    case Number = 'number';
    case Email = 'email';
    case Url = 'url';
    case Tel = 'tel';
    case Toggle = 'toggle';
}
