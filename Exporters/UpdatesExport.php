<?php

namespace Exports\Exporters;

use Exports\Models\Update;
use Exports\Models\Job;
use Carbon\Carbon;

class UpdatesExport extends BaseExport
{
    /**
     * @return mixed
     */
    public function query()
    {
        $updates = Update::with('author', 'customer');

        $updates->where('company_id', $this->user->company_id);

        if ($this->request->has('entity_ids')) {
            $updates->whereIn('id', $this->request->entity_ids);
        }
        if ($this->request->has('from_date')) {
            $updates->where('created_at', '>=', Carbon::parse($this->request->from_date));
        }
        if ($this->request->has('to_date')) {
            $updates->where('created_at', '<=', Carbon::parse($this->request->to_date));
        }

        $updates->orderBy('created_at', 'desc');

        $this->items_count = $updates->count();

        return $updates;
    }

    /**
     * @return string[]
     */
    public function headings(): array
    {
        return [
            'Author',
            'Message',
            'Activity date'
        ];
    }

    /**
     * @param $item
     * @return array
     */
    public function map($item): array
    {
        $this->sendItemNotification($item);

        if (!isset($item->author) || !isset($item->customer)) {
            return false;
        }

        $author_name = $item->author->full_name;
        $customer_name = $item->customer->full_name;

        switch ($item->type) {
            case 'created':
                $typeTxt = ' created a new ';
                break;
            case 'updated':
                $typeTxt = ' updated the ';
                break;
            case 'deleted':
                $typeTxt = ' deleted the ';
                break;
            default:
                $typeTxt = ' ' . $item->type . ' ';
                break;
        }

        $message = $author_name . $typeTxt . $item->class . ' ' . Carbon::parse($item->created_at);

        switch ($item->class) {
            case 'job':
                if ($item->type == 'updated') {
                    if (isset($item->updated_attributes['status']['new'])) {
                        $message = $author_name . ' changed the job status to ' . Job::$jobStatuses[$item->updated_attributes['status']['new']] . ' ' . Carbon::parse($item->created_at);
                    } elseif (isset($item->members_added)) {
                        $message = $author_name . ' changed the assignment ' . Carbon::parse($item->created_at);
                    }
                }
                break;
            case 'email':
                switch ($item->type) {
                    case 'status':
                        if (isset($item->updated_attributes['status']['new'])) {
                            $message = 'Email status changed to ' . $item->updated_attributes['status']['new'] . ' ' . Carbon::parse($item->created_at);
                        } elseif ($item->email_status == 'open') {
                            $message = $customer_name . ' has opened the email ' . Carbon::parse($item->created_at);
                            $author_name = $customer_name;
                        }
                        break;
                    case 'created':
                        if ($item->email_status == 'open') {
                            $message = $author_name . ' sent an estimate/invoice PDF to the customer ' . Carbon::parse($item->created_at);
                        } elseif ($item->email_status == 'delivered') {
                            $message = $customer_name . ' has received the email ' . Carbon::parse($item->created_at);
                            $author_name = $customer_name;
                        }
                        break;
                }
                break;
            case 'file':
                switch ($item->type) {
                    case 'accepted':
                        $message = $author_name . ' has accepted and signed the invoice ' . Carbon::parse($item->created_at);
                        break;
                    case 'rejected':
                        $message = $author_name . ' has rejected the invoice ' . Carbon::parse($item->created_at);
                        break;
                    case 'created':
                        if (isset($item->job_id)) {
                            $message = $author_name . ' attached a file to the job ' . Carbon::parse($item->created_at);
                        }
                        if (isset($item->customer_id)) {
                            $message = $author_name . ' attached a file to the customer ' . Carbon::parse($item->created_at);
                        }
                        if (isset($item->file_id)) {
                            $message = $author_name . ' attached a file to the file ' . Carbon::parse($item->created_at);
                        }
                        if (isset($item->invoice_id)) {
                            $message = $author_name . ' attached a file to the invoice ' . Carbon::parse($item->created_at);
                        }
                        if (isset($item->payment_id)) {
                            $message = $author_name . ' attached a file to the payment ' . Carbon::parse($item->created_at);
                        }
                        if (isset($item->subtask_id)) {
                            $message = $author_name . ' attached a file to the subtask ' . Carbon::parse($item->created_at);
                        }
                        break;
                }
                break;
            case 'comment':
                if ($item->type == 'created') {
                    $message = $author_name . ' added an internal comment to the ' . $item->class . ' record ' . Carbon::parse($item->created_at);
                }
                break;
        }

        return [
            $author_name,
            $message,
            isset($item->created_at) ? Carbon::parse($item->created_at)->toDateTime() : '',
        ];
    }
}
