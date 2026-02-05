<?php

namespace JeffersonGoncalves\ServiceDesk\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use JeffersonGoncalves\ServiceDesk\Concerns\IsOperator;

class User extends Model
{
    use IsOperator, Notifiable;

    protected $table = 'users';

    protected $guarded = [];
}
