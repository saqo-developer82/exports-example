<?php

namespace Exports\Exporters;

use Exports\Models\{
    User,
    Customer,
    CustomField
};

use Carbon\Carbon;

class CustomersExport extends BaseExport
{
    /**
     * @var string[] $headers_data
     */
    private $headers_data;

    public function __construct($userId, $request)
    {
        parent::__construct($userId, $request);
        $this->headers_data = $this->getHeadingsData();
    }

    /**
     * @return mixed
     */
    public function query()
    {
        if ($this->user->role == User::ROLE_MANAGER && $this->user->can_view_customer_list == false) {
            return collect([]);
        }

        $customer = Customer::with(['invoices', 'main_location', 'customfields', 'tags', 'leadSource']);

        $customer->where('company_id', $this->user->company_id);

        if ($this->request->has('entity_ids')) {
            $customer->whereIn('id', $this->request->entity_ids);
        }
        if ($this->request->has('from_date')) {
            $customer->where('created_at', '>=', Carbon::parse($this->request->from_date));
        }
        if ($this->request->has('to_date')) {
            $customer->where('created_at', '<=', Carbon::parse($this->request->to_date));
        }

        if (
            $this->user->role == User::ROLE_MANAGER
            && $this->user->can_view_customer_list == true
            && $this->user->view_customer_list_mode == 'assigned'
        ) {
            $customer->where('assigned_to', $this->user->id);
        }

        $customer->orderBy('created_at', 'desc');

        $this->items_count = $customer->count();

        return $customer;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        $heading_data_with_empty_values = array_fill_keys(array_keys($this->headers_data), '');

        $heading_data_with_empty_values['id'] = "If present.\n\nWill find and update the client.";
        $heading_data_with_empty_values['account_type'] = "Options: 'Individual' or 'Company'\n\nDefaults to 'Individual' if left blank";
        $heading_data_with_empty_values['status'] = "Options: 'Lead', 'Opportunity', 'Current Customer', 'Lost', 'Other'\n\nDefaults to 'Current Customer' if left blank";
        $heading_data_with_empty_values['has_different_billing_address'] = "Options: 'Yes' or 'No'\n\nDefaults to 'No' if left blank";


        return [$this->headers_data, $heading_data_with_empty_values];
    }

    /**
     * @param $item
     * @return array
     */
    public function map($item): array
    {
        $this->sendItemNotification($item);

        $total_paid = '0.00';
        $total_unpaid = '0.00';

        foreach ($item->invoices as $inv) {
            if (!$inv->deleted_at) {
                if (isset($inv->amount_paid)) {
                    $amountPaid = $inv->amount_paid === '' ? '0.00' : (string) str_replace(',', '', $inv->amount_paid);
                    $total_paid = bcadd($total_paid, $amountPaid, 2);
                }
                if (isset($inv->amount_unpaid)) {
                    $amountUnpaid = $inv->amount_unpaid === '' ? '0.00' : (string) str_replace(',', '', $inv->amount_unpaid);
                    $total_unpaid = bcadd($total_unpaid, $amountUnpaid, 2);
                }
            }
        }

        $item->address_1 = $item->main_location->address_1 ?? $item->address_1;
        $item->address_2 = $item->main_location->address_2 ?? $item->address_2;
        $item->city = $item->main_location->city ?? $item->city;
        $item->state = $item->main_location->state ?? $item->state;
        $item->zip_code = $item->main_location->zip_code ?? $item->zip_code;
        $item->postal_code = $item->main_location->zip_code ?? $item->zip_code;

        $item->total_paid = $total_paid;
        $item->total_unpaid = $total_unpaid;

        foreach ($item->customfields as $customField) {
            if (isset($this->headers_data[$customField['field_instance_id']])) {
                $item->{$customField['field_instance_id']} = $customField['value'];
            }
        }

        $item->tags = implode(',', $item->tags->pluck('title')->toArray());

        $item->lead_source_name = $item->leadSource->name ?? '';
        $item->assigned_to_first_name = $item->assigned->first_name ?? '';
        $item->assigned_to_last_name = $item->assigned->last_name ?? '';

        $data = [];
        foreach ($this->headers_data as $column_key => $column_value) {
            if (isset($item->$column_key)) {
                if ($item->$column_key instanceof Carbon) {
                    $data[$column_key] = $item->$column_key->format('m-d-Y');
                } elseif($column_key == 'has_different_billing_address'){
                    $data[$column_key] = !empty($item->$column_key) && $item->$column_key == true ? 'Yes' : 'No';
                } else {
                    $data[$column_key] = $item->$column_key;
                }
            } else {
                $data[$column_key] = '';
            }
        }
        return $data;
    }

    /**
     * @return string[]
     */
    public function getHeadingsData(): array
    {
        $data = [
            'id' => 'Customer Id',
            'account_type' => 'Account Type',
            'status' => 'Account Status',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'company_name' => 'Company Name',
            'created_at' => 'Created',
            'email' => 'Email',
            'alt_email' => 'Alternate Email',
            'phone' => 'Phone Number',
            'alt_phone' => 'Alternate Phone Number',
            'notes' => 'Notes',
            'address_1' => 'Address Line 1',
            'address_2' => 'Address Line 2',
            'city' => 'City',
            'state' => 'State',
            'zip_code' => 'Zip Code',
        ];

        if ($this->user->company->country == 'CA') {
            $data['postal_code'] = 'Postal Code';
        }

        $data['has_different_billing_address'] = 'Use Separate Billing Address?';
        $data['billing_address_1'] = 'Billing Address Line 1';
        $data['billing_address_2'] = 'Billing Address Line 2';
        $data['billing_city'] = 'Billing City';
        $data['billing_state'] = 'Billing State';
        $data['billing_zip_code'] = 'Billing Zip Code';
        $data['secondary_first_name'] = 'Secondary First Name';
        $data['secondary_last_name'] = 'Secondary Last Name';
        $data['secondary_email'] = 'Secondary Email';
        $data['secondary_phone'] = 'Secondary Phone';

        $data['total_paid'] = 'Total Paid';
        $data['total_unpaid'] = 'Total Unpaid';


        $customFields = CustomField::select(['id', 'name', 'position'])->where('company_id', $this->user->company_id)
            ->where('object', 'customer')
            ->where('is_active', true)
            ->orderBy('position', 'asc')
            ->get(['id', 'name', 'position']);


        foreach ($customFields as $customField) {
            $data[$customField->id] = $customField->name;
        }

        $data['tags'] = 'Tags';
        $data['lead_source_name'] = 'Lead Source';
        $data['assigned_to_first_name'] = 'Assigned To [First Name]';
        $data['assigned_to_last_name'] = 'Assigned To [Last Name]';

        return $data;
    }
}
