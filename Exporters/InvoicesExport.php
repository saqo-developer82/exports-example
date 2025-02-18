<?php

namespace Exports\Exporters;

use Exports\Models\Invoice;
use Carbon\Carbon;

class InvoicesExport extends BaseExport
{
    /**
     * @return mixed
     */
    public function query()
    {
        $invoices = Invoice::query();

        $invoices->where('company_id', $this->user->company_id);

        if ($this->request->has('entity_ids')) {
            $invoices->whereIn('id', $this->request->entity_ids);
        }
        if ($this->request->has('from_date')) {
            $invoices->where('created_at', '>=', Carbon::parse($this->request->from_date));
        }
        if ($this->request->has('to_date')) {
            $invoices->where('created_at', '<=', Carbon::parse($this->request->to_date));
        }

        $invoices->orderBy('created_at', 'desc');

        $this->items_count = $invoices->count();

        return $invoices;
    }

    /**
     * @return string[]
     */
    public function headings(): array
    {
        return [
            'Number',
            'Status',
            'Team member',
            'Customer',
            'Project',
            'Job title',
            'Sent date',
            'Due date',
            'Amount',
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
            $item->number,
            Invoice::$invoiceStatuses[$item->status],
            isset($item->author) ? $item->author->full_name : '',
            isset($item->customer) ? $item->customer->display_full_name : '',
            isset($item->project) ? $item->project->name : '',
            isset($item->job) ? $item->job->job_type : '',
            isset($item->invoiced_date) ? Carbon::parse($item->invoiced_date)->toDateTime() : '',
            isset($item->due_date) ? Carbon::parse($item->due_date)->toDateTime() : '',
            $item->total,
        ];
    }
}
