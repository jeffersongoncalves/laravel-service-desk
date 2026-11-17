<?php

use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');

afterEach(fn () => Ticket::resolveNotificationUrlUsing(null));
