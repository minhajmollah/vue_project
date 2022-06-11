<?php

namespace App\Exports;

use App\Model\Order\Order;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use DB;

class InvoiceReportExportView implements FromView
{
    protected $city, $range;

    public function __construct($city, $range, $report_type, $customer = null)
    {
        $this->city        = $city;
        $this->range       = $range;
        $this->report_type = $report_type;
        $this->customer    = $customer;
    }

    public function view(): View
    {
        if ($this->report_type === 'normal') {
            $order = Order::orderBy('id', 'desc');
        } elseif ($this->report_type === 'datewise') {
            $order =
                Order::leftJoin(\DB::raw('(SELECT order_id, sum(quantity) as total_sold_qty, sum(total_buying_price) as total_buying_price, sum(total_selling_price) as total_selling_price from `order_details` group by order_id ) tem_order_details '),
                    function ($join) {
                        $join->on('orders.id', '=', 'tem_order_details.order_id');
                    })
                    ->select(\DB::raw("count(orders.id) as total_order, tem_order_details.total_sold_qty, tem_order_details.total_buying_price, tem_order_details.total_selling_price, DATE_FORMAT(orders.order_date,'%d-%m-%y') as order_date"))
                    ->groupBy('orders.order_date');
        }elseif ($this->report_type === 'customerwise') {
            $order =
                Order::with('user')->leftJoin(\DB::raw('(SELECT order_id, sum(quantity) as total_sold_qty, sum(total_buying_price) as total_buying_price, sum(total_selling_price) as total_selling_price from `order_details` group by order_id ) tem_order_details '),
                    function ($join) {
                        $join->on('orders.id', '=', 'tem_order_details.order_id');
                    })
                    ->select(\DB::raw("orders.id as order_id, orders.user_id, tem_order_details.total_sold_qty, tem_order_details.total_buying_price, tem_order_details.total_selling_price, DATE_FORMAT(orders.order_date,'%d-%m-%y') as order_date"))
                    ->groupBy('orders.id');
        }

        if ($this->range != '') {
            $date  = $this->range;
            $data  = explode(",", $date);
            $start = date("Y-m-d", strtotime(date_convert($data[0])));
            $end   = date("Y-m-d", strtotime(date_convert($data[1])));
            $order->whereBetween('order_date', [$start, $end]);
        }
        if ($this->city != 'undefined') {
            $order->where('shipping_area_id', '=', $this->city);
        }
        if ($this->customer && $this->customer != 'undefined') {
            $order->where('user_id', '=', $this->customer);
        }
        $order = $order->get();

        if ($this->report_type === 'normal') {
            return view('admin.report.excel.invoicereport', ['product' => $order]);
        } elseif ($this->report_type === 'datewise') {
            return view('admin.report.excel.datewiseinvoicereport', ['product' => $order]);
        } elseif ($this->report_type === 'customerwise') {
            return view('admin.report.excel.customerwiseinvoicereport', ['product' => $order]);
        }
    }
}
