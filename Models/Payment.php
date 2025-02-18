<?php

namespace Exports\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    public static $paymentMthods = [
        'cash' => 'Cash',
        'check' => 'Check',
        'card' => 'Credit Card',
        'bank_transfer' => 'Bank Transfer',
        'other' => 'Other',
        'credit_memo' => 'Credit Memo',
    ];

    public static function getPaymentMethodName($payment_method)
    {
        return isset(static::$paymentMthods[$payment_method]) ? static::$paymentMthods[$payment_method] : 'Cash';
    }
}
