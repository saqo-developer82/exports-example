<?php

namespace Exports\Exporters;

use Maatwebsite\Excel\Concerns\{
    Exportable,
    FromQuery,
    ShouldAutoSize,
    WithHeadings,
    WithMapping
};
use Exports\Notifications\Notification;
use Exports\Models\User;

abstract class BaseExport implements FromQuery, WithMapping, WithHeadings, ShouldAutoSize
{
    use Exportable;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var int $progress_percentage
     */
    protected $progress_percentage = 0;

    /**
     * @var int $items_count
     */
    protected $items_count = 0;

    /**
     * @var int $items_key
     */
    protected $items_key = 0;

    /**
     * @param int $userId
     * @param Request $request
     */
    public function __construct(int $userId, Request $request)
    {
        $this->user = User::find($userId);
        $this->request = $request;
    }

    abstract public function query();
    abstract public function headings(): array;
    abstract public function map($item): array;

    /**
     * @return void
     */
    protected function sendItemNotification()
    {
        $this->items_key = $this->items_key + 1;

        $current_percentage = calculateProgressPercentage($this->items_key, $this->items_count, 90);

        if ($current_percentage != $this->progress_percentage) {
            Notification::sendEntityExportNotification($this->user, [
                'entity' => $this->request->entity,
                'export_percentage' => $current_percentage
            ]);
        }

        $this->progress_percentage = $current_percentage;
    }
}