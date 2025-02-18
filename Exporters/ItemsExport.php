<?php

namespace Exports\Exporters;

use Exports\Models\Item;
use Carbon\Carbon;

class ItemsExport extends BaseExport
{
    /**
     * @return mixed
     */
    public function query()
    {
        $items = Item::select([
            'id',
            'company_id',
            'name',
            'sku',
            'type',
            'default_unit_cost',
            'default_unit_price',
            'default_quantity',
            'default_taxable',
            'default_description',
            'track_inventory',
            'quantity_available',
            'created_at'
        ])->with(['tags' => function($query) {
            $query->select(['taggable_id', 'taggable_type', 'tag_id' ,'title']);
        }]);

        $items->where('company_id', $this->user->company_id);

        if ($this->request->has('entity_ids')) {
            $items->whereIn('id', $this->request->entity_ids);
        }
        if ($this->request->has('from_date')) {
            $items->where('created_at', '>=', Carbon::parse($this->request->from_date));
        }
        if ($this->request->has('to_date')) {
            $items->where('created_at', '<=', Carbon::parse($this->request->to_date));
        }

        $items->orderBy('created_at', 'desc');

        $this->items_count = $items->count();

        return $items;
    }

    /**
     * @return string[]
     */
    public function headings(): array
    {
        return [
            'Invoice Item Name',
            'Item #/SKU',
            'Item Type',
            'Unit Cost',
            'Unit Price',
            'Quantity',
            'Taxed',
            'Description',
            'Track Inventory',
            'Quantity On Hand',
            'Tags'
        ];
    }

    /**
     * @param $item
     * @return array
     */
    public function map($item): array
    {
        $this->sendItemNotification($item);

        $description = $item->default_description;
        if (isset($description) && str_contains($description, '/')) {
            $description = strtolower(preg_replace('/[\W\s\/]+/', ' ', $description));
        }

        return [
            $item->name,
            $item->sku,
            $item->type,
            $item->default_unit_cost ?? 0,
            $item->default_unit_price ?? 0,
            $item->default_quantity ?? 0,
            $item->default_taxable ? 'Yes' : 'No',
            $description,
            $item->track_inventory ? 'Yes' : 'No',
            $item->quantity_available ?? 0,
            implode(',', $item->tags->pluck('title')->toArray())
        ];
    }
}
