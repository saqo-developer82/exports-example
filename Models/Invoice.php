<?php

namespace Exports\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    public static $invoiceStatuses = [
        1 => 'Estimate',
        2 => 'Draft',
        3 => 'Invoiced',
        4 => 'Paid',
        5 => 'Partially Paid',
        6 => 'Void',
    ];
}
