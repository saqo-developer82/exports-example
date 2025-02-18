<?php

namespace Exports\Exporters;

use Exports\Models\MaintenanceAgreement;
use Carbon\Carbon;
use RRule\RRule;

class MaintenanceAgreementsExport extends BaseExport
{
    /**
     * @var null $occurrence_from_date
     */
    private $occurrence_from_date = null;

    /**
     * @var null $occurrence_to_date
     */
    private $occurrence_to_date = null;

    /**
     * @return mixed
     */
    public function query()
    {
        $maintenanceAgreements = MaintenanceAgreement::query();

        $maintenanceAgreements->where('company_id', $this->user->company_id);

        if ($this->request->filled('entity_ids')) {
            $maintenanceAgreements->whereIn('id', $this->request->get('entity_ids'));
        }
        if ($this->request->filled('occurrence_from_date')) {
            $this->occurrence_from_date = $this->request->get('occurrence_from_date');
        }
        if ($this->request->filled('occurrence_to_date')) {
            $this->occurrence_to_date = $this->request->get('occurrence_to_date');
        }

        $maintenanceAgreements->orderBy('created_at', 'desc');

        $this->items_count = $maintenanceAgreements->count();

        return $maintenanceAgreements;
    }

    /**
     * @return string[]
     */
    public function headings(): array
    {
        return [
            'Number',
            'Month',
            'Status',
            'Last Job',
            'Title',
            'Frequency',
            'Customer',
            'Address',
            "Phone",
            "Email"
        ];
    }

    /**
     * @param $item
     * @return array
     */
    public function map($item): array
    {
        $this->sendItemNotification($item);
        $serviceOccurrencesQuery = $item->service_occurrences();
        if (!empty($this->occurrence_from_date) && !empty($this->occurrence_to_date)) {
            $serviceOccurrencesQuery->where('created_at', '>=', Carbon::parse($this->occurrence_from_date))->where('created_at', '<=', Carbon::parse($this->occurrence_to_date));
        }
        $scheduled = $serviceOccurrencesQuery->whereScheduled(true)->count();
        $rrule_service = !empty($item->rrule_service) ? new RRule($item->rrule_service) : null;
        return [
            $item->cuid,
            !empty($rrule_service) && isset($rrule_service->getRule()['DTSTART']) ? $rrule_service->getRule()['DTSTART']->format('F Y') : '',
            $scheduled ? 'Scheduled' : 'Unscheduled',
            !empty($item->last_job_occurrence) ? Carbon::parse($item->last_job_occurrence->date)->format('F Y') : '',
            $item->title,
            !empty($rrule_service) && !empty($rrule_service->humanReadable(['use_intl' => false, 'locale' => 'en'])) ? explode(',', $rrule_service->humanReadable(['use_intl' => false, 'locale' => 'en']))[0] : '',
            $item->customer->display_name ?? '',
            $item->customer->main_location->address_1 ?? '',
            $item->customer->phone ?? '',
            $item->customer->email ?? ''
        ];
    }
}
