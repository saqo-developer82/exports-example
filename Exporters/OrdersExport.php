<?php

namespace Exports\Exporters;

use Exports\Models\Order;
use Carbon\Carbon;

class OrdersExport extends BaseExport
{
    /**
     * @return mixed
     */
    public function query()
    {
        $orders = Order::with('vendor');

        $orders->where('company_id', $this->user->company_id);

        if ($this->request->has('entity_ids')) {
            $orders->whereIn('id', $this->request->entity_ids);
        }
        if ($this->request->has('from_date')) {
            $orders->where('created_at', '>=', Carbon::parse($this->request->from_date));
        }
        if ($this->request->has('to_date')) {
            $orders->where('created_at', '<=', Carbon::parse($this->request->to_date));
        }

        $orders->orderBy('created_at', 'desc');

        $this->items_count = $orders->count();

        return $orders;
    }

    /**
     * @return string[]
     */
    public function headings(): array
    {
        return [
            'Purchase Order #',
            'Order #',
            'Title',
            'Vendor Type',
            'Vendor Name',
            'Tax Rate',
            'Status',
            'Payment Status',
            'Order Email',
            'Order Date',
            'Total Amount',
            'Customer Email',
            'Customer Name',
            'Delivery or Pickup',
            'Delivery Address',
            'Assigned Team Member',
            'Notes',
            'Instructions'
        ];
    }

    /**
     * @param $item
     * @return array
     */
    public function map($item): array
    {
        $this->sendItemNotification($item);

        $vendor_name = '';

        if ($item->vendor_type == 'integrated') {
            $vendor_name = $item->vendor_name;
        } elseif ($item->vendor_type == 'third_party' && isset($item->vendor)) {
            $vendor_name = $item->vendor->name;
        }

        $tz = $this->user->time_zone ?? 'America/Chicago';

        $order_details = $item->getDetails();

        return [
            $item->order_number,
            $item->cuid,
            $item->title,
            Order::$vendor_types[$item->vendor_type] ?? '',
            $vendor_name,
            $item->tax_rate,
            Order::$order_statuses[$item->status] ?? '',
            Order::$payment_statuses[$item->payment_status] ?? '',
            $item->order_email,
            isset($item->order_at) ? Carbon::parse($item->order_at)->setTimeZone(new \DateTimeZone($tz))->format('Y-m-d H:i:s') : '',
            $order_details['order_total'],
            $item->customer->email ?? '',
            $item->customer->display_full_name ?? '',
            Order::$delivery_methods[$item->delivery_method] ?? '',
            $item->delivery_address,
            $item->assigned->full_name ?? '',
            $item->comment,
            $item->delivery_instruction,
        ];
    }
}
