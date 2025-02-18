<?php

namespace Exports\Exporters;

use Exports\Models\Comment;
use Carbon\Carbon;

class CommentsExport extends BaseExport
{
    /**
     * @return mixed
     */
    public function query()
    {
        $comment = Comment::query();

        $comment->where('company_id', $this->user->company_id);

        if ($this->request->has('entity_ids')) {
            $comment->whereIn('id', $this->request->entity_ids);
        }
        if ($this->request->has('from_date')) {
            $comment->where('created_at', '>=', Carbon::parse($this->request->from_date));
        }
        if ($this->request->has('to_date')) {
            $comment->where('created_at', '<=', Carbon::parse($this->request->to_date));
        }

        $comment->orderBy('created_at', 'desc');

        $this->items_count = $comment->count();

        return $comment;
    }

    /**
     * @return string[]
     */
    public function headings(): array
    {
        return [
            'Author',
            'Message',
            'Customer',
            'Job',
            'File',
            'Invoice',
            'Payment',
            'Project'
        ];
    }

    /**
     * @param $item
     * @return array
     */
    public function map($item): array
    {
        $this->sendItemNotification();

        return [
            isset($item->author) ? $item->author->full_name : '',
            $item->text,
            isset($item->customer) ? $item->customer->display_full_name : '',
            isset($item->job) ? $item->job->job_type : '',
            isset($item->file) ? $item->file->name : '',
            isset($item->invoice) ? $item->invoice->number : '',
            isset($item->payment) ? $item->payment->cuid : '',
            isset($item->project) ? $item->project->name : '',
        ];
    }
}
