<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SalesExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param \Illuminate\Support\Collection $orders
     */
    public function __construct(protected \Illuminate\Support\Collection $orders) {}

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->orders;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Order ID',
            'Customer Name',
            'Customer Email',
            'Total Amount',
            'Discount',
            'Final Amount',
            'Status',
            'Date Placed',
        ];
    }

    /**
     * @param mixed $order
     * @return array
     */
    public function map($order): array
    {
        return [
            $order->id,
            $order->user->name ?? 'Guest',
            $order->user->email ?? 'N/A',
            $order->total_amount,
            $order->discount_amount,
            $order->final_amount,
            ucfirst($order->status),
            $order->placed_at ? $order->placed_at->toDateTimeString() : 'N/A',
        ];
    }
}
