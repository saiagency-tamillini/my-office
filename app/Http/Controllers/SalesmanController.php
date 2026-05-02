<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\PartySale;
use App\Models\Beat;
use App\Models\Customer;
use App\Models\PaymentEntry;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\SalesReportService;

class SalesmanController extends Controller
{
    protected $salesReportService;

    public function __construct(SalesReportService $salesReportService)
    {
        $this->salesReportService = $salesReportService;
    }

    public function index()
    {
        $salesmen = Beat::with('customers')->withCount('customers')->get()
        ->groupBy('salesman')
        ->map(function ($beats, $salesmanName) {

            $totalCustomers = $beats->sum('customers_count');

            $customerIds = $beats->pluck('customers.*.id')->flatten();

            $paymentEntries = PaymentEntry::with('customer')
                ->whereIn('customer_id', $customerIds)
                ->orderBy('bill_no')
                ->orderBy('created_at')
                ->get()
                ->groupBy('bill_no');

            $totalPending = $paymentEntries->map(function ($entries) {
                if ($entries->contains(fn($entry) => $entry->status === 'complete')) {
                    return 0;
                }
                return $entries->last()->balance;
            })->sum();

            $beats = $beats->map(function ($beat) use ($paymentEntries) {

                $beatCustomerIds = $beat->customers->pluck('id');

                $beatPending = $paymentEntries
                    ->filter(fn($entries) => $beatCustomerIds->contains($entries->first()->customer_id))
                    ->map(function ($entries) {
                        if ($entries->contains(fn($entry) => $entry->status === 'complete')) {
                            return 0;
                        }
                        return $entries->last()->balance;
                    })
                    ->sum();

                $beat->customers->map(function ($customer) use ($paymentEntries) {
                    $customerEntries = $paymentEntries
                        ->filter(fn($entries) => $entries->first()->customer_id == $customer->id);

                    $customerPending = $customerEntries
                        ->map(function ($entries) {
                            if ($entries->contains(fn($entry) => $entry->status === 'complete')) {
                                return 0;
                            }
                            return $entries->last()->balance;
                        })
                        ->sum();

                    $customer->pending = $customerPending;
                    return $customer;
                });

                $beat->pending = $beatPending;
                return $beat;
            });

            return [
                'beats' => $beats,
                'total_customers' => $totalCustomers,
                'total_pending' => $totalPending,
            ];
        });

        // dd($salesmen);
        return view('pages.salesman.index', compact('salesmen'));
    }

    public function report_table(Request $request)
    {
        $salesmen = Beat::select('salesman')->distinct()->pluck('salesman');
        $beats = Beat::orderBy('name')->get();
        // $query = $this->buildReportQuery($request);
        $query = $this->salesReportService->buildReportQuery($request);
        $sales = $query->get();
        $customers = Customer::with('beat')->get();
        $selectedBeats = collect();
        if ($request->filled('beat_ids')) {
            $selectedBeats = Beat::whereIn('id', (array) $request->beat_ids)->get();
        }
        return view('pages.sales_report', compact('sales', 'salesmen', 'customers','beats','selectedBeats'));
    }

    public function downloadReport(Request $request)
    {
        // $sales = $this->buildReportQuery($request)->get();
        $query = $this->salesReportService->buildReportQuery($request);
        $sales = $query->get();

        
        // Group by salesman (same as your existing approach)
        $grouped = $sales->groupBy(function ($item) {
            return optional($item->beat)->salesman ?? 'Unknown';
        });

        // Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'S.No',
            'Customer Name',
            'Bill No',
            'Bill Date',
            'Aging (days)',
            'Amount',
            'CD',
            'Product Return',
            'Online Payment',
            'Amount Received',
            'Balance',
            'Beat'
        ];

        $sheet->fromArray($headers, null, 'A1');

        $rowNo = 2;

        foreach ($grouped as $salesman => $salesGroup) {
            // Salesman header row
            $sheet->mergeCells("A{$rowNo}:L{$rowNo}");
            $sheet->setCellValue("A{$rowNo}", $salesman);

            $sheet->getStyle("A{$rowNo}:L{$rowNo}")->applyFromArray([
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'A3A1A1'],
                ],
            ]);

            $rowNo++;
            $serial = 1;

            foreach ($salesGroup as $sale) {
                $aging = $sale->bill_date
                    ? Carbon::parse($sale->bill_date)->diffInDays(Carbon::today(), false)
                    : 0;

                $sheet->fromArray([
                    $serial++,
                    optional($sale->customer)->name ?? '',
                    $sale->bill_no,
                    $sale->bill_date ? Carbon::parse($sale->bill_date)->format('d-m-Y') : '',
                    $aging,
                    $sale->amount,
                    $sale->cd,
                    $sale->product_return,
                    $sale->online_payment,
                    '',
                    // $sale->latest_amount_received ?? '',
                    $sale->latest_balance?? '',
                    optional($sale->beat)->name ?? '',
                ], null, "A{$rowNo}");

                $rowNo++;
            }
        }

        $writer = new Xlsx($spreadsheet);

        return Response::streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'Sales_Man_Report.xlsx');
            // return Excel::download(new SalesReportExport($sales), 'sales_report.xlsx');
    }

    public function bulkSaleUpdate(Request $request)
    {
        // dd($request->all());
        foreach ($request->sales as $id => $data) {
            $sale = PartySale::find($id);
            if (!$sale) {
                continue;
            }


            $changedFields = [];

            if (isset($data['cd']) && $data['cd'] !== '') {
                $changedFields['cd'] = $data['cd'];
            }

            if (isset($data['product_return']) && $data['product_return'] !== '') {
                $changedFields['product_return'] = $data['product_return'];
            }

            if (isset($data['online_payment']) && $data['online_payment'] !== '') {
                $changedFields['online_payment'] = $data['online_payment'];
            }

            if (isset($data['amount_received']) && $data['amount_received'] !== '') {
                $changedFields['amount_received'] = $data['amount_received'];
            }
            if (!empty($changedFields)) {
                // dd($changedFields);
                // dd($data);

                PaymentEntry::create([
                            'part_sale_id'     => $sale->id,
                            'customer_id'      => $sale->customer_id,
                            'bill_no'          => $sale->bill_no,
                            'amount'           => $sale->amount,
                            'cd'               => $changedFields['cd'] ?? null,
                            'product_return'   => $changedFields['product_return'] ?? null,
                            'online_payment'   => $changedFields['online_payment'] ?? null,
                            'amount_received'  => $changedFields['amount_received'] ?? null,
                            'balance'          => $data['balance'],
                            'remarks'          => $data['remarks'] ?? null,
                            'status'           => $data['balance'] == 0 ? 'complete' : 'pending',
                        ]);
            }
        }
        return redirect()->back()->with('success', 'Sales updated successfully');
    }

    public function salesManDetails(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
        ]);

        $customer = Customer::findOrFail($request->customer_id);

        $paymentEntries = PaymentEntry::with('customer')
            ->where('customer_id', $customer->id)
            ->orderBy('bill_no')
            ->orderBy('created_at')
            ->get()
            ->groupBy('bill_no')
            ->map(function ($entries, $billNo) {
                $isPaid = $entries->contains(fn($entry) => $entry->balance == 0 || $entry->status === 'complete');

                $entries = $entries->map(function ($entry) use ($isPaid) {
                    $entry->is_paid = $isPaid;
                    return $entry;
                });
            return $entries;
        });
        $pendingBills = $paymentEntries->filter(fn($entries) =>
            optional($entries->first())->is_paid === false
        );

        $completedBills = $paymentEntries->filter(fn($entries) =>
            optional($entries->first())->is_paid === true
        );

        $paymentEntries = $pendingBills->merge($completedBills);
        return view('pages.salesman.details', compact('customer', 'paymentEntries'));
    }

    private function buildReportQuery(Request $request)
    {
        $partySaleIds = PartySale::whereExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('payment_entries as pe1')
                ->whereColumn('pe1.part_sale_id', 'party_sales.id')
                ->whereRaw('pe1.created_at = (
                    SELECT MAX(pe2.created_at)
                    FROM payment_entries pe2
                    WHERE pe2.part_sale_id = pe1.part_sale_id
                )')
                ->where('pe1.status', 'pending');
        })->pluck('id');

        $latestPayments = DB::table('payment_entries as pe1')
            ->select('pe1.*')
            ->whereRaw('pe1.id = (
                SELECT MAX(pe2.id)
                FROM payment_entries pe2
                WHERE pe2.part_sale_id = pe1.part_sale_id
            )');

        $query = PartySale::with('beat')
            ->join('beats', 'party_sales.beat_id', '=', 'beats.id')
            ->leftJoin('customers', 'party_sales.customer_id', '=', 'customers.id')
            ->joinSub($latestPayments, 'latest_payment', function ($join) {
                $join->on('latest_payment.part_sale_id', '=', 'party_sales.id');
            })
            ->whereIn('party_sales.id', $partySaleIds)
            ->select(
                'party_sales.*',
                'customers.name as customer_name',
                'latest_payment.amount_received as latest_amount_received',
                'latest_payment.balance as latest_balance',
                'latest_payment.payment_date as latest_payment_date',
                'latest_payment.status as latest_status'
            );

        // Date filters
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $from = Carbon::parse($request->from_date)->startOfDay();
            $to   = Carbon::parse($request->to_date)->endOfDay();
            $query->whereBetween('party_sales.bill_date', [$from, $to]);
        } elseif ($request->filled('from_date')) {
            $from = Carbon::parse($request->from_date)->startOfDay();
            $query->where('party_sales.bill_date', '>=', $from);
        } elseif ($request->filled('to_date')) {
            $to = Carbon::parse($request->to_date)->endOfDay();
            $query->where('party_sales.bill_date', '<=', $to);
        }

        // Salesman filter
        if ($request->filled('salesmen')) {
            $query->whereIn('beats.salesman', $request->salesmen);
        }

        // Beat filter
        if ($request->filled('beat_ids')) {
            $beatIds = array_filter((array) $request->beat_ids, fn($v) => $v !== null && $v !== '');
            if (count($beatIds)) {
                $query->whereIn('party_sales.beat_id', $beatIds);
            }
        }

        // Sorting
        if ($request->has('sort') && in_array($request->sort, ['asc', 'desc'])) {
            $query->orderBy('customer_name', $request->sort);
        } else {
            $query->orderBy('beats.salesman')->orderBy('party_sales.bill_date');
        }
        return $query;
    }
    public function downloadPdf($customerId)
    {
        $customer = Customer::findOrFail($customerId);

        $paymentEntries = PaymentEntry::with('customer')
            ->where('customer_id', $customer->id)
            ->orderBy('bill_no')
            ->orderBy('created_at')
            ->get()
            ->groupBy('bill_no')
            ->map(function ($entries, $billNo) {

                $isPaid = $entries->contains(fn($entry) =>
                    $entry->balance == 0 || $entry->status === 'complete'
                );

                return $entries->map(function ($entry) use ($isPaid) {
                    $entry->is_paid = $isPaid;
                    return $entry;
                });
            });

        $pendingBills = $paymentEntries->filter(fn($entries) =>
            optional($entries->first())->is_paid === false
        );

        $completedBills = $paymentEntries->filter(fn($entries) =>
            optional($entries->first())->is_paid === true
        );

        $paymentEntries = $pendingBills->merge($completedBills);
        $pdf = Pdf::loadView('pages.salesman.pdf', compact('customer', 'paymentEntries'));
        return $pdf->stream('salesman-report-'.$customer->name.'.pdf');

        // return $pdf->download('salesman-report-'.$customer->name.'.pdf');
    }

}

