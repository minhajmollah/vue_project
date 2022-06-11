<?php

namespace App\Http\Controllers\Chart;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Product;
use App\Model\Order\Order;
use App\Model\Order\OrderDetails;
use App\Model\Setting\PaymentSetting;
use App\User;
use App\AllStatic;
use DB, PDF, Session, Excel;
use App\Imports\ProductsImport;

class ReportController extends Controller
{
    public function getCatStock()
    {
        return Product::with('category:id,category_name')
            ->select('category_id', DB::raw('sum(current_quantity) as quantity'))
            ->groupBy('category_id')
            ->get();
    }

    public function getSaleAmount()
    {
        $today      = date("Y-m-d");
        $last_month = date("Y-m-d", strtotime("first day of this month"));
        return Order::select(DB::raw("DATE_FORMAT(payment_date, '%D %M') as payment_date"), DB::raw('sum(total_amount) as amount'))
            ->where('payment_status', AllStatic::$paid)
            ->whereBetween('payment_date', [$last_month, $today])
            ->groupBy('payment_date')
            ->get();
    }

    public function getOrderData()
    {
        $today      = date("Y-m-d");
        $last_month = date("Y-m-d", strtotime("first day of this month"));
        return Order::select(DB::raw("DATE_FORMAT(order_date, '%D %M %y') as date"), DB::raw("count(*) as total"))->whereBetween('order_date', [$last_month, $today])->groupBy('order_date')->get();
    }

    public function getCustomerData()
    {
        $today      = date("Y-m-d H:i:s");
        $last_month = date("Y-m-d H:i:s", strtotime("first day of this month"));
        return User::select(DB::raw("DATE_FORMAT(created_at, '%D %M %Y') as date"), DB::raw("count(*) as total"))->whereBetween('created_at', [$last_month, $today])->groupBy(DB::raw("DATE_FORMAT(created_at, '%D %M %Y')"))->get();
    }

    public function stockReport()
    {
        return view('admin.report.stockreport');
    }

    public function salesReport()
    {
        return view('admin.report.salesreport');
    }

    public function datewiseSalesReport()
    {
        return view('admin.report.datewiseSalesreport');
    }

    public function datewiseInvoiceReport()
    {
        return view('admin.report.datewiseInvoicereport');
    }

    public function invoiceReport()
    {
        return view('admin.report.invoicereport');
    }

    public function customerwiseOrderReport()
    {
        return view('admin.report.customerwiseInvoicereport');
    }

    public function productListPdf(Request $request)
    {
        $search_keyword = $request->keyword;
        $product        = Product::with([
            'category:id,category_name,category_native_name',
            'sub_category:id,sub_category_name,sub_category_native_name',
            'sub_sub_category:id,sub_sub_category_name,sub_sub_category_native_name',
            'brand:id,brand_name,brand_native_name',
            'multiple_image:id,product_id,image_name'
        ])
            ->orderBy('updated_at', 'desc');

        if ($request->category != 'undefined') {
            $product->where('category_id', '=', $request->category);
        }
        if ($request->sub_category != 'undefined') {
            $product->where('sub_category_id', '=', $request->sub_category);
        }

        if ($request->sub_sub_category != 'undefined') {
            $product->where('sub_sub_category_id', '=', $request->sub_sub_category);
        }

        if ($request->brand != 'undefined') {
            $product->where('brand_id', '=', $request->brand);
        }

        if ($request->range != '') {
            $date  = $request->range;
            $data  = explode(",", $date);
            $start = date_convert($data[0]);
            $end   = date_convert($data[1]);
            $product->whereBetween('updated_at', [$start, $end]);
        }

        if ($search_keyword != '') {
            // this three field  or combination doing a and comibination with all other combination in upper
            $product->where(function ($query) use ($search_keyword) {
                $query->where('product_name', 'LIKE', '%' . $search_keyword . '%')
                    ->orWhere('product_native_name', 'LIKE', '%' . $search_keyword . '%')
                    ->orWhere('product_keyword', 'LIKE', '%' . $search_keyword . '%');
            });
        }
        $product = $product->get();
        // return view('admin.report.pdf.stockreportpdf',['product' => $product]);
        $pdf = PDF::loadView('admin.report.pdf.stockreportpdf', ['product' => $product]);

        $pdf->setPaper('A4', 'landscape');
        return $pdf->download("stock-report-Pdf.pdf");

    }

    public function productListPrint(Request $request)
    {
        $search_keyword = $request->keyword;
        $product        = Product::with([
            'category:id,category_name,category_native_name',
            'sub_category:id,sub_category_name,sub_category_native_name',
            'sub_sub_category:id,sub_sub_category_name,sub_sub_category_native_name',
            'brand:id,brand_name,brand_native_name',
            'multiple_image:id,product_id,image_name'
        ])
            ->orderBy('updated_at', 'desc');

        if ($request->category != 'undefined') {
            $product->where('category_id', '=', $request->category);
        }
        if ($request->sub_category != 'undefined') {
            $product->where('sub_category_id', '=', $request->sub_category);
        }

        if ($request->sub_sub_category != 'undefined') {
            $product->where('sub_sub_category_id', '=', $request->sub_sub_category);
        }

        if ($request->brand != 'undefined') {
            $product->where('brand_id', '=', $request->brand);
        }

        if ($request->range != '') {
            $date  = $request->range;
            $data  = explode(",", $date);
            $start = date_convert($data[0]);
            $end   = date_convert($data[1]);
            $product->whereBetween('updated_at', [$start, $end]);
        }

        if ($search_keyword != '') {
            // this three field  or combination doing a and comibination with all other combination in upper
            $product->where(function ($query) use ($search_keyword) {
                $query->where('product_name', 'LIKE', '%' . $search_keyword . '%')
                    ->orWhere('product_native_name', 'LIKE', '%' . $search_keyword . '%')
                    ->orWhere('product_keyword', 'LIKE', '%' . $search_keyword . '%');
            });
        }
        $product = $product->get();
        return view('admin.report.print.stockreportprint', ['product' => $product]);

    }

    public function productSaleList(Request $request)
    {
        $product = OrderDetails::with(['product:id,product_name',
                                       'brand:id,brand_name', 'category:id,category_name',
                                       'sub_category:id,sub_category_name',
                                       'sub_sub_category:id,sub_sub_category_name,sub_sub_category_native_name'])
            ->selectRaw('product_id,
            category_id,
            sub_category_id,
            sub_sub_category_id,
            brand_id,
            sum(quantity) as sold_qty'
            )
            ->groupBy([
                'product_id',
                'category_id',
                'brand_id',
                'sub_category_id',
                'sub_sub_category_id'
            ])
            ->orderBy('updated_at', 'desc');

        if ($request->category != 'undefined') {
            $product->where('category_id', '=', $request->category);
        }
        if ($request->sub_category != 'undefined') {
            $product->where('sub_category_id', '=', $request->sub_category);
        }
        if ($request->sub_sub_category != 'undefined') {
            $product->where('sub_sub_category_id', '=', $request->sub_sub_category);
        }

        if ($request->brand != 'undefined') {
            $product->where('brand_id', '=', $request->brand);
        }

        if ($request->range != '') {
            $date  = $request->range;
            $data  = explode(",", $date);
            $start = date_convert($data[0]);
            $end   = date_convert($data[1]);
            $product->whereBetween('created_at', [$start, $end]);
        }

        return $product = $product->paginate(10);
    }

    public function productInvoiceList(Request $request)
    {
        $report_type = $request->report_type;
        if ($report_type === 'normal') {
            $order = Order::orderBy('id', 'desc');
        } elseif ($report_type === 'datewise') {
            $order =
                Order::leftJoin(\DB::raw('(SELECT order_id, sum(quantity) as total_sold_qty, sum(total_buying_price) as total_buying_price, sum(total_selling_price) as total_selling_price from `order_details` group by order_id ) tem_order_details '),
                    function ($join) {
                        $join->on('orders.id', '=', 'tem_order_details.order_id');
                    })
                    ->select(\DB::raw("count(orders.id) as total_order, tem_order_details.total_sold_qty, tem_order_details.total_buying_price, tem_order_details.total_selling_price, DATE_FORMAT(orders.order_date,'%d-%m-%y') as order_date"))
                    ->groupBy('orders.order_date');
        }elseif ($report_type === 'customerwise') {
            $order =
                Order::with('user')->leftJoin(\DB::raw('(SELECT order_id, sum(quantity) as total_sold_qty, sum(total_buying_price) as total_buying_price, sum(total_selling_price) as total_selling_price from `order_details` group by order_id ) tem_order_details '),
                    function ($join) {
                        $join->on('orders.id', '=', 'tem_order_details.order_id');
                    })
                    ->select(\DB::raw("orders.id as order_id, orders.user_id, tem_order_details.total_sold_qty, tem_order_details.total_buying_price, tem_order_details.total_selling_price, DATE_FORMAT(orders.order_date,'%d-%m-%y') as order_date"))
                    ->groupBy('orders.id');
        }

        if ($request->range != '') {
            $date  = $request->range;
            $data  = explode(",", $date);
            $start = date("Y-m-d", strtotime(date_convert($data[0])));
            $end   = date("Y-m-d", strtotime(date_convert($data[1])));
            $order->whereBetween('order_date', [$start, $end]);
        }

        if ($request->city != 'undefined') {
            $order->where('shipping_area_id', '=', $request->city);
        }

        if (isset($request->customer) && $request->customer != 'undefined') {
            $order->where('user_id', '=', $request->customer);
        }

        return $order = $order->paginate(10);
    }

    public function invoiceListPdf(Request $request)
    {
        $report_type = $request->report_type;
        if ($report_type === 'normal') {
            $order = Order::orderBy('id', 'desc');
        } elseif ($report_type === 'datewise') {
            $order =
                Order::leftJoin(\DB::raw('(SELECT order_id, sum(quantity) as total_sold_qty, sum(total_buying_price) as total_buying_price, sum(total_selling_price) as total_selling_price from `order_details` group by order_id ) tem_order_details '),
                    function ($join) {
                        $join->on('orders.id', '=', 'tem_order_details.order_id');
                    })
                    ->select(\DB::raw("count(orders.id) as total_order, tem_order_details.total_sold_qty, tem_order_details.total_buying_price, tem_order_details.total_selling_price, DATE_FORMAT(orders.order_date,'%d-%m-%y') as order_date"))
                    ->groupBy('orders.order_date');
        }elseif ($report_type === 'customerwise') {
            $order =
                Order::with('user')->leftJoin(\DB::raw('(SELECT order_id, sum(quantity) as total_sold_qty, sum(total_buying_price) as total_buying_price, sum(total_selling_price) as total_selling_price from `order_details` group by order_id ) tem_order_details '),
                    function ($join) {
                        $join->on('orders.id', '=', 'tem_order_details.order_id');
                    })
                    ->select(\DB::raw("orders.id as order_id, orders.user_id, tem_order_details.total_sold_qty, tem_order_details.total_buying_price, tem_order_details.total_selling_price, DATE_FORMAT(orders.order_date,'%d-%m-%y') as order_date"))
                    ->groupBy('orders.id');
        }

        if ($request->range != '') {
            $date  = $request->range;
            $data  = explode(",", $date);
            $start = date("Y-m-d", strtotime(date_convert($data[0])));
            $end   = date("Y-m-d", strtotime(date_convert($data[1])));
            $order->whereBetween('order_date', [$start, $end]);
        }
        if ($request->city != 'undefined') {
            $order->where('shipping_area_id', '=', $request->city);
        }
        if (isset($request->customer) && $request->customer != 'undefined') {
            $order->where('user_id', '=', $request->customer);
        }
        $order = $order->get();

        if ($report_type === 'normal') {
            $pdf = PDF::loadView('admin.report.pdf.invoicereportpdf', ['product' => $order]);
            $pdf->setPaper('A4', 'landscape');
            return $pdf->download("invoice-report-Pdf.pdf");
        } elseif ($report_type === 'datewise') {
            $pdf = PDF::loadView('admin.report.pdf.datewiseinvoicereportpdf', ['product' => $order]);
            $pdf->setPaper('A4', 'landscape');
            return $pdf->download("datewise-invoice-report-Pdf.pdf");
        } elseif ($report_type === 'customerwise') {
            $pdf = PDF::loadView('admin.report.pdf.customerwiseinvoicereportpdf', ['product' => $order]);
            $pdf->setPaper('A4', 'landscape');
            return $pdf->download("customerwise-order-details-Pdf.pdf");
        }
    }

    public function invoiceListPrint(Request $request)
    {
        $report_type = $request->report_type;
        if ($report_type === 'normal') {
            $order = Order::orderBy('id', 'desc');
        } elseif ($report_type === 'datewise') {
            $order =
                Order::leftJoin(\DB::raw('(SELECT order_id, sum(quantity) as total_sold_qty, sum(total_buying_price) as total_buying_price, sum(total_selling_price) as total_selling_price from `order_details` group by order_id ) tem_order_details '),
                    function ($join) {
                        $join->on('orders.id', '=', 'tem_order_details.order_id');
                    })
                    ->select(\DB::raw("count(orders.id) as total_order, tem_order_details.total_sold_qty, tem_order_details.total_buying_price, tem_order_details.total_selling_price, DATE_FORMAT(orders.order_date,'%d-%m-%y') as order_date"))
                    ->groupBy('orders.order_date');
        }elseif ($report_type === 'customerwise') {
            $order =
                Order::with('user')->leftJoin(\DB::raw('(SELECT order_id, sum(quantity) as total_sold_qty, sum(total_buying_price) as total_buying_price, sum(total_selling_price) as total_selling_price from `order_details` group by order_id ) tem_order_details '),
                    function ($join) {
                        $join->on('orders.id', '=', 'tem_order_details.order_id');
                    })
                    ->select(\DB::raw("orders.id as order_id, orders.user_id, tem_order_details.total_sold_qty, tem_order_details.total_buying_price, tem_order_details.total_selling_price, DATE_FORMAT(orders.order_date,'%d-%m-%y') as order_date"))
                    ->groupBy('orders.id');
        }

        if ($request->range != '') {
            $date  = $request->range;
            $data  = explode(",", $date);
            $start = date("Y-m-d", strtotime(date_convert($data[0])));
            $end   = date("Y-m-d", strtotime(date_convert($data[1])));

            $order->whereBetween('order_date', [$start, $end]);
        }
        if ($request->city != 'undefined') {
            $order->where('shipping_area_id', '=', $request->city);
        }
        if (isset($request->customer) && $request->customer != 'undefined') {
            $order->where('user_id', '=', $request->customer);
        }
        $order = $order->get();

        if ($report_type === 'normal') {
            return view('admin.report.print.invoicereportprint', ['product' => $order]);
        } elseif ($report_type === 'datewise') {
            return view('admin.report.print.datewiseinvoicereportprint', ['product' => $order]);
        } elseif ($report_type === 'customerwise') {
            return view('admin.report.print.customerwiseinvoicereportprint', ['product' => $order]);
        }
    }

    public function saleList(Request $request)
    {
        $report_type = $request->report_type;
        if ($report_type === 'normal') {
            $selectRaw = "product_id,
            category_id,
            sub_category_id,
            sub_sub_category_id,
            brand_id,
            sum(total_selling_price) as total_sales_amount,
            sum(total_buying_price) as total_buying_amount,
            sum(quantity) as total_sold_qty";

            $groupBy = [
                'product_id',
                'category_id',
                'brand_id',
                'sub_category_id',
                'sub_sub_category_id',
            ];
        } elseif ($report_type === 'datewise') {
            $selectRaw = "product_id,
            category_id,
            sub_category_id,
            sub_sub_category_id,
            brand_id,
            DATE_FORMAT(created_at,'%d-%m-%y') as date,
            sum(total_selling_price) as total_sales_amount,
            sum(total_buying_price) as total_buying_amount,
            sum(quantity) as total_sold_qty";

            $groupBy = [
                'product_id',
                'date',
                'category_id',
                'brand_id',
                'sub_category_id',
                'sub_sub_category_id',
            ];
        }

        $product = OrderDetails::with(['product:id,product_name',
                                       'brand:id,brand_name', 'category:id,category_name',
                                       'sub_category:id,sub_category_name',
                                       'sub_sub_category:id,sub_sub_category_name,sub_sub_category_native_name'])
            ->selectRaw($selectRaw)
            ->groupBy($groupBy)
            ->orderBy('updated_at', 'desc');

        if ($request->category != 'undefined') {
            $product->where('category_id', '=', $request->category);
        }
        if ($request->sub_category != 'undefined') {
            $product->where('sub_category_id', '=', $request->sub_category);
        }
        if ($request->sub_sub_category != 'undefined') {
            $product->where('sub_sub_category_id', '=', $request->sub_sub_category);
        }

        if ($request->brand != 'undefined') {
            $product->where('brand_id', '=', $request->brand);
        }

        if ($request->range != '') {
            $date  = $request->range;
            $data  = explode(",", $date);
            $start = date_convert($data[0]);
            $end   = date_convert($data[1]);
            $product->whereBetween('created_at', [$start, $end]);
        }

        return $product = $product->paginate(10);
    }

    public function salesListPdf(Request $request)
    {
        $report_type = $request->report_type;
        if ($report_type === 'normal') {
            $selectRaw = "product_id,
            category_id,
            sub_category_id,
            sub_sub_category_id,
            brand_id,
            sum(total_selling_price) as total_sales_amount,
            sum(total_buying_price) as total_buying_amount,
            sum(quantity) as total_sold_qty";

            $groupBy = [
                'product_id',
                'category_id',
                'brand_id',
                'sub_category_id',
                'sub_sub_category_id',
            ];
        } elseif ($report_type === 'datewise') {
            $selectRaw = "product_id,
            category_id,
            sub_category_id,
            sub_sub_category_id,
            brand_id,
            DATE_FORMAT(created_at,'%d-%m-%y') as date,
            sum(total_selling_price) as total_sales_amount,
            sum(total_buying_price) as total_buying_amount,
            sum(quantity) as total_sold_qty";

            $groupBy = [
                'product_id',
                'date',
                'category_id',
                'brand_id',
                'sub_category_id',
                'sub_sub_category_id',
            ];
        }

        $product = OrderDetails::with(['product:id,product_name',
                                       'brand:id,brand_name', 'category:id,category_name',
                                       'sub_category:id,sub_category_name',
                                       'sub_sub_category:id,sub_sub_category_name,sub_sub_category_native_name'])
            ->selectRaw($selectRaw)
            ->groupBy($groupBy)
            ->orderBy('updated_at', 'desc');

        if ($request->category != 'undefined') {
            $product->where('category_id', '=', $request->category);
        }
        if ($request->sub_category != 'undefined') {
            $product->where('sub_category_id', '=', $request->sub_category);
        }
        if ($request->sub_sub_category != 'undefined') {
            $product->where('sub_sub_category_id', '=', $request->sub_sub_category);
        }
        if ($request->brand != 'undefined') {
            $product->where('brand_id', '=', $request->brand);
        }

        if ($request->range != '') {
            $date  = $request->range;
            $data  = explode(",", $date);
            $start = date_convert($data[0]);
            $end   = date_convert($data[1]);
            $product->whereBetween('created_at', [$start, $end]);
        }
        $product = $product->get();
        // return view('admin.report.pdf.salesreportpdf',['product' => $product]);
        if ($report_type === 'normal') {
            $pdf = PDF::loadView('admin.report.pdf.salesreportpdf', ['product' => $product]);
            $pdf->setPaper('A4', 'landscape');
            return $pdf->download("sales-report-Pdf.pdf");
        } elseif ($report_type === 'datewise') {
            $pdf = PDF::loadView('admin.report.pdf.datewisesalesreportpdf', ['product' => $product]);
            $pdf->setPaper('A4', 'landscape');
            return $pdf->download("datewise-sales-report-Pdf.pdf");
        }
    }

    public function salesListPrint(Request $request)
    {
        $report_type = $request->report_type;
        if ($report_type === 'normal') {
            $selectRaw = "product_id,
            category_id,
            sub_category_id,
            sub_sub_category_id,
            brand_id,
            sum(total_selling_price) as total_sales_amount,
            sum(total_buying_price) as total_buying_amount,
            sum(quantity) as total_sold_qty";

            $groupBy = [
                'product_id',
                'category_id',
                'brand_id',
                'sub_category_id',
                'sub_sub_category_id',
            ];
        } elseif ($report_type === 'datewise') {
            $selectRaw = "product_id,
            category_id,
            sub_category_id,
            sub_sub_category_id,
            brand_id,
            DATE_FORMAT(created_at,'%d-%m-%y') as date,
            sum(total_selling_price) as total_sales_amount,
            sum(total_buying_price) as total_buying_amount,
            sum(quantity) as total_sold_qty";

            $groupBy = [
                'product_id',
                'date',
                'category_id',
                'brand_id',
                'sub_category_id',
                'sub_sub_category_id',
            ];
        }

        $product = OrderDetails::with(['product:id,product_name',
                                       'brand:id,brand_name', 'category:id,category_name',
                                       'sub_category:id,sub_category_name',
                                       'sub_sub_category:id,sub_sub_category_name,sub_sub_category_native_name'])
            ->selectRaw($selectRaw)
            ->groupBy($groupBy)
            ->orderBy('updated_at', 'desc');

        if ($request->category != 'undefined') {
            $product->where('category_id', '=', $request->category);
        }
        if ($request->sub_category != 'undefined') {
            $product->where('sub_category_id', '=', $request->sub_category);
        }
        if ($request->sub_sub_category != 'undefined') {
            $product->where('sub_sub_category_id', '=', $request->sub_sub_category);
        }
        if ($request->brand != 'undefined') {
            $product->where('brand_id', '=', $request->brand);
        }

        if ($request->range != '') {
            $date  = $request->range;
            $data  = explode(",", $date);
            $start = date_convert($data[0]);
            $end   = date_convert($data[1]);
            $product->whereBetween('created_at', [$start, $end]);
        }
        $product = $product->get();

        if ($report_type === 'normal') {
            return view('admin.report.print.salesreportprint', ['product' => $product]);
        } elseif ($report_type === 'datewise') {
            return view('admin.report.print.datewisesalesreportprint', ['product' => $product]);
        }

    }

    public function showTransection()
    {
        return view('admin.report.transaction');
    }

    public function getMethodAmount(Request $request)
    {
        // return "Hello";
        $order =
            Order::with('provider:id,provider')->select(DB::raw('payment_method,payment_date,SUM(total_amount) as amount'))
                ->where('payment_method', '!=', 0)
                ->where('payment_status', '=', 1);

        if ($request->methods != 'undefined') {
            $order->where('payment_method', '=', $request->methods);
        }

        if ($request->singledate != '') {
            $date = explode(' ', date_convert($request->singledate))[0];
            $order->where('payment_date', $date);
        }

        if ($request->range != '') {
            $date  = $request->range;
            $data  = explode(",", $date);
            $start = date_convert($data[0]);
            $end   = date_convert($data[1]);
            $order->whereBetween('payment_date', [$start, $end]);
        }

        return $order->groupBy('payment_method')->groupBy('payment_date')->paginate(10);

    }

    public function getMethods()
    {
        return PaymentSetting::get();
    }

    public function amountListPdf(Request $request)
    {
        $order =
            Order::with('provider:id,provider')->select(DB::raw('payment_method,payment_date,SUM(total_amount) as amount'))
                ->where('payment_method', '!=', 0)
                ->where('payment_status', '=', 1);

        if ($request->methods != 'undefined') {
            $order->where('payment_method', '=', $request->methods);
        }

        if ($request->singledate != '') {
            $date = explode(' ', date_convert($request->singledate))[0];
            $order->where('payment_date', $date);
        }

        if ($request->range != '') {
            $date  = $request->range;
            $data  = explode(",", $date);
            $start = date_convert($data[0]);
            $end   = date_convert($data[1]);
            $order->whereBetween('payment_date', [$start, $end]);
        }
        $order = $order->groupBy('payment_method')->groupBy('payment_date')->get();
        // return view('admin.report.pdf.amountreportpdf',['payments' => $order]);
        $pdf = PDF::loadView('admin.report.pdf.amountreportpdf', ['payments' => $order]);

        $pdf->setPaper('A4', 'landscape');
        return $pdf->download("amount-report-Pdf.pdf");
    }

    public function amountListPrint(Request $request)
    {
        $order =
            Order::with('provider:id,provider')->select(DB::raw('payment_method,payment_date,SUM(total_amount) as amount'))
                ->where('payment_method', '!=', 0)
                ->where('payment_status', '=', 1);

        if ($request->methods != 'undefined') {
            $order->where('payment_method', '=', $request->methods);
        }

        if ($request->singledate != '') {
            $date = explode(' ', date_convert($request->singledate))[0];
            $order->where('payment_date', $date);
        }

        if ($request->range != '') {
            $date  = $request->range;
            $data  = explode(",", $date);
            $start = date_convert($data[0]);
            $end   = date_convert($data[1]);
            $order->whereBetween('payment_date', [$start, $end]);
        }
        $order = $order->groupBy('payment_method')->groupBy('payment_date')->get();
        return view('admin.report.print.amountreportprint', ['payments' => $order]);
    }

    public function export(Request $request)
    {
        if ($request->req == 'payment') {
            return Excel::download(new \App\Exports\PaymentExportView($request->methods, $request->singledate, $request->range), 'payment-report.xlsx');
        } elseif ($request->req == 'stock') {
            return Excel::download(new \App\Exports\StockReportExportView($request->keyword, $request->category, $request->sub_category, $request->sub_sub_category, $request->brand, $request->bulkstore), 'stock-report.xlsx');
        } elseif ($request->req == 'invoice') {
            if ($request->report_type === 'normal') {
                return Excel::download(new \App\Exports\InvoiceReportExportView($request->city, $request->range, $request->report_type), 'invoice-report.xlsx');
            } elseif ($request->report_type === 'datewise') {
                return Excel::download(new \App\Exports\InvoiceReportExportView($request->city, $request->range, $request->report_type), 'datewise-invoice-report.xlsx');
            }elseif ($request->report_type === 'customerwise') {
                return Excel::download(new \App\Exports\InvoiceReportExportView($request->city, $request->range, $request->report_type, $request->customer), 'customerwise-order-details.xlsx');
            }
        } else {
            if ($request->report_type === 'normal') {
                return Excel::download(new \App\Exports\SalesReportExportView($request->category, $request->sub_category, $request->sub_sub_category, $request->brand, $request->range, $request->report_type), 'sales-report.xlsx');
            } elseif ($request->report_type === 'datewise') {
                return Excel::download(new \App\Exports\SalesReportExportView($request->category, $request->sub_category, $request->sub_sub_category, $request->brand, $request->range, $request->report_type), 'datewise-sales-report.xlsx');
            }
        }
    }

    public function import(Request $request)
    {
        $this->validate($request, [
            'file' => 'required|mimes:xls,xlsx'
        ]);

        $data = Excel::toCollection(new ProductsImport, $request->file('file'));

        foreach ($data as $key => $value) {
            foreach ($value as $k => $v) {
                if (is_int($v[0]) && is_int($v[7])) {
                    $product                   = Product::find($v[0]);
                    $product->current_quantity += $v[7];
                    $product->stock_quantity   += $v[7];
                    $product->update();
                }
            }

        }
        return response()->json(['status' => 'success', 'message' => 'Stock Updated !']);
    }
}
