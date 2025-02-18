<?php


namespace Exports\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public static $vendor_types = [
        'third_party' => 'Third pary',
        'integrated' => 'Integrated',
    ];

    public static $order_statuses = [
        'compose' => 'Compose',
        'draft' => 'Draft',
        'ordered' => 'Ordered',
        'pending' => 'Pending',
        'route' => 'Route',
        'ready_for_pickup' => 'Ready for pickup',
        'partially_received' => 'Partially received',
        'received' => 'Received',
        'completed' => 'Completed',
        'canceled' => 'Canceled',
        'issue' => 'Issue',
    ];

    public static $payment_statuses = [
        'unpaid' => 'Unpaid',
        'partially_paid' => 'Partially paid',
        'paid' => 'Paid',
    ];

    public static $delivery_methods = [
        'delivery' => 'Delivery',
        'pickup' => 'Pickup',
    ];
}
