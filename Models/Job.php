<?php

namespace Exports\Models;

use Illuminate\Database\Eloquent\Model;
class Job extends Model
{
    public static $jobStatuses = [
        1 => 'New',
        2 => 'In Progress',
        3 => 'Pending',
        4 => 'Completed',
        5 => 'Canceled',
        6 => 'On The Way',
    ];
}
