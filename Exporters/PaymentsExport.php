<?php

namespace Exports\Exporters;

use Exports\Models\Payment;
use Carbon\Carbon;

class PaymentsExport extends BaseExport
{
    /**
     * @return mixed
     */
    public function query()
    {

        $payments = Payment::query();

        $payments->where('company_id', $this->user->company_id);

        if ($this->request->has('entity_ids')) {
            $payments->whereIn('id', $this->request->entity_ids);
        }
        if ($this->request->has('from_date')) {
            $payments->where('created_at', '>=', Carbon::parse($this->request->from_date));
        }
        if ($this->request->has('to_date')) {
            $payments->where('created_at', '<=', Carbon::parse($this->request->to_date));
        }

        $payments->orderBy('created_at', 'desc');

        $this->items_count = $payments->count();

        return $payments;
    }

    /**
     * @return string[]
     */
    public function headings(): array
    {
        return [
            'Number',
            'Customer',
            'Amount',
            'Method',
            'Payment date',
            'Invoice #'
        ];
    }

    /**
     * @param $item
     * @return array
     */
    public function map($item): array
    {
        $this->sendItemNotification($item);

        return [
            $item->cuid,
            isset($item->customer) ? $item->customer->display_full_name : '',
            $item->amount,
            Payment::getPaymentMethodName($item->method),
            isset($item->payment_date) ? Carbon::parse($item->payment_date)->toDateTime() : '',
            isset($item->invoice) ? $item->invoice->number : ''
        ];
    }
}
