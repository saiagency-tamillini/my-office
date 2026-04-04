<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use App\Models\Beat;
use App\Models\PartySale;
use App\Models\Customer;
use App\Models\RouteMaster;
use App\Models\Trip;
use App\Models\TripItem;
use App\Models\PaymentEntry;
use App\Services\PartySaleBulkUpdateService;
use App\Services\PaymentEntryService;
use Carbon\Carbon;
use Illuminate\Support\Str;




class fileController extends Controller
{
    public function file_upload (){return view('files.file_upload');}

    public function uploadExcel(Request $request)
    {
        try {
            $request->validate([
                'excel_file' => 'required|file|mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,application/octet-stream',
            ]);

            $spreadsheet = IOFactory::load($request->file('excel_file')->getPathname());

            $sheet = $spreadsheet->getSheetByName('Party Wise Sales Report');
            if (!$sheet) {
                return back()->with('error', 'Sheet not found');
            }

            $rows = $sheet->toArray();

            // ✅ Validate structure & get header info
            [$headerRowIndex, $indexes] = $this->validateExcelSheet($rows);

            // ✅ Prepare formatted data
            $data = $this->prepareExcelData($rows, $headerRowIndex, $indexes);

            if (empty($data)) {
                return back()->with('error', 'No valid data found');
            }

            $this->storeExcelData($data);

            return redirect()->route('party-sales.index')
                ->with('success', 'Excel data imported successfully');
            // ✅ Download Excel
            // return $this->downloadExcel($data, 'Party_Wise_Report.xlsx');
        } catch (\Exception $e) {

            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    private function validateExcelSheet(array $rows): array
    {
        $expectedHeaders = ['sr no', 'division name', 'product name'];
        $headerRowIndex = null;

        foreach ($rows as $index => $row) {
            $row = array_map('strtolower', array_map('trim', $row));
            $found = false;
            foreach ($expectedHeaders as $key => $header) {
                if (!isset($row[$key]) || $row[$key] == $header) {
                    $found = true;
                    break;
                }
            }
            if ($found) {
                $headerRowIndex = $index;
                break;
            }
        }
        

        if ($headerRowIndex === null) {
            abort(422, 'Header row not found');
        }

        $headers = array_map('strtolower', array_map('trim', $rows[$headerRowIndex]));

        $indexes = [
            'beat'      => array_search('beat', $headers),
            'party'     => array_search('party', $headers),
            'net_amt'   => array_search('net amt', $headers),
            'bill_no'   => array_search('bill no', $headers),
            'bill_date' => array_search('bill date', $headers),
        ];

        if (in_array(false, $indexes, true)) {
            abort(422, 'Required columns missing in Excel');
        }

        return [$headerRowIndex, $indexes];
    }

    private function prepareExcelData(array $rows, int $headerRowIndex, array $indexes): array
    {
        // $beatMap = config('constants.beats');
        $dbBeats = Beat::pluck('name')->map(fn($b) => strtoupper($b))->toArray();
        // dd($dbBeats);
        $lastBeat = null;
        $groupedData = [];

        foreach ($rows as $i => $row) {
            if ($i <= $headerRowIndex) continue;

            // Date
            $date = $row[$indexes['bill_date']] ?? null;
            if (is_numeric($date)) {
                $date = ExcelDate::excelToDateTimeObject($date)->format('d-m-Y');
            }
            if (empty($date)) continue;

            $excelBeat = trim($row[$indexes['beat']] ?? '');
            if (!empty($excelBeat)) {
                $lastBeat = strtoupper($excelBeat);
            }
            if (empty($excelBeat) && $lastBeat !== null) {
                $excelBeat = $lastBeat;
            }
            if (empty($excelBeat)) continue;
            // dd(strtoupper($excelBeat));
            if (!in_array(strtoupper($excelBeat), $dbBeats)) continue;

            // $mappedBeat = $beatMap[$excelBeat];

            $customerData = [
                'S.No'            => '', 
                'Customer Name'   => trim($row[$indexes['party']] ?? ''),
                'Bill No'         => trim($row[$indexes['bill_no']] ?? ''),
                'Bill Date'       => $date,
                'Aging'           => '',
                'Amount'          => (float) ($row[$indexes['net_amt']] ?? 0),
                'CD'              => '',
                'Product Return'  => '',
                'Online Payment'  => '',
                'Amount Received' => '',
                'Balance'         => '',
                // 'Beat'            =>
            ];

            $groupedData[$excelBeat][] = $customerData;
        }

        $finalData = [];
        foreach ($groupedData as $beat => $customers) {
            $finalData[] = [
                'type' => 'beat',
                'beat' => $beat,
            ];
            foreach ($customers as $customer) {
                $customer['type'] = 'data';
                $finalData[] = $customer;
            }
        }

        return $finalData;
    }

    private function storeExcelData(array $data): void
    {
        DB::transaction(function () use ($data) {
            $currentBeatId = null;
            
            foreach ($data as $row) {
                
                if ($row['type'] === 'beat') {
                    $currentBeatId = Beat::where('name', $row['beat'])->value('id');
                    continue;
                }

                if (!$currentBeatId) continue;
                $billDate = null;
                if (!empty($row['Bill Date'])) {
                    $billDate = \Carbon\Carbon::createFromFormat('d/m/Y', $row['Bill Date'])->format('Y-m-d');
                }
                $customerName = strtoupper(trim($row['Customer Name']));
                $customer = null;
                if ($customerName) {
                    $customer = Customer::firstOrCreate(
                        ['name' => $customerName, 'beat_id' => $currentBeatId]
                    );
                }
                try {
                    PartySale::create([
                        'beat_id'        => $currentBeatId,
                        'customer_id'   => $customer ? $customer->id : null,
                        'bill_no'        => $row['Bill No'],
                        'bill_date'      => $billDate,
                        'amount'         => $row['Amount'],
                        'balance'         => $row['Amount'],
                    ]);
                } catch (QueryException $e) {
                    if ($e->getCode() == 23000) {
                        throw new \Exception(
                            'Duplicate Bill Number found: ' . ($row['Bill No'] ?? 'Unknown')
                        );
                    }
                    throw $e;
                }
            }
        });
    }

    public function trip_sheet_report(Request $request){
        $salesmen = Beat::select('salesman')->distinct()->pluck('salesman');
        $beats = Beat::orderBy('name')->get();
        $routes = RouteMaster::orderBy('name')->get();
        $is_today_report = false;
        $date = $request->filled('bill_date')
            ? Carbon::parse($request->bill_date)->format('Y-m-d')
            : Carbon::today()->format('Y-m-d');
        if($date == Carbon::today()->format('Y-m-d')){
            $is_today_report= true;
        }
        $query = PartySale::with('beat')
            ->join('beats', 'party_sales.beat_id', '=', 'beats.id')
             ->leftJoin('customers', 'party_sales.customer_id', '=', 'customers.id')
            ->whereDate('party_sales.bill_date', $date)
            ->orderBy('beats.salesman')
            ->orderBy('party_sales.bill_date')
            ->select('party_sales.*','customers.name as customer_name'); 

        if ($request->filled('salesmen')) {
            $query->whereIn('beats.salesman', $request->salesmen);
        }

        if ($request->has('sort') && in_array($request->sort, ['asc', 'desc'])) {
            $query->orderBy('customer_name', $request->sort);
        }
        // Beat filter
        if ($request->filled('beat_ids')) {
            $beatIds = array_filter((array) $request->beat_ids, fn($v) => $v !== null && $v !== '');
            if (count($beatIds)) {
                $query->whereIn('party_sales.beat_id', $beatIds);
            }
        }
        $sales = $query->get();
        $customers = Customer::with('beat')->get();
        $selectedBeats = collect();
        if ($request->filled('beat_ids')) {
            $selectedBeats = Beat::whereIn('id', (array) $request->beat_ids)->get();
        }
        return view('pages.trip_report', compact('sales', 'salesmen', 'customers','beats','selectedBeats','is_today_report','routes'));
    }

    public function credit_popup(Request $request)
    {
        $salesmen = Beat::select('salesman')->distinct()->pluck('salesman');
        $beats = Beat::orderBy('name')->get();

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
            ->orderBy('beats.salesman')
            ->orderBy('party_sales.bill_date')
            ->select(
                'party_sales.*',
                'customers.name as customer_name',
                'latest_payment.amount_received as latest_amount_received',
                'latest_payment.balance as latest_balance',
                'latest_payment.payment_date as latest_payment_date',
                'latest_payment.status as latest_status',
                'latest_payment.id as payment_entry_id'
            );

        if ($request->filled('salesmen')) {
            $query->whereIn('beats.salesman', $request->salesmen);
        }

        if ($request->has('sort') && in_array($request->sort, ['asc', 'desc'])) {
            $query->orderBy('customer_name', $request->sort);
        }

        if ($request->filled('beat_id')) {
            $query->where('party_sales.beat_id', $request->beat_id);
        }

        $sales = $query->get();

        $customers = Customer::with('beat')->get();
        $selectedBeat = $request->filled('beat_id') ? Beat::find($request->beat_id) : null;

        return view('modals.credit_details', compact('sales', 'salesmen', 'customers','beats','selectedBeat'));
    }

    public function save_trip(Request $request)
    {
        $validated = $request->validate([
            'trip_date' => ['required', 'date'],
            'route_id' => ['required', 'exists:routes,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.party_sale_id' => ['nullable', 'integer', 'exists:party_sales,id'],
            'items.*.payment_entry_id' => ['nullable', 'integer', 'exists:payment_entries,id'],
        ]);
        DB::beginTransaction();
        try {
            $tripNumber = 'TRIP-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4));

            $trip = Trip::create([
                'trip_number' => $tripNumber,
                'trip_date' => $validated['trip_date'],
                'route_id' => $validated['route_id'],
            ]);

            $tripItems = [];
            foreach ($validated['items'] as $item) {
                if (empty($item['party_sale_id']) && empty($item['payment_entry_id'])) {
                    continue;
                }

                $tripItems[] = [
                    'trip_id' => $trip->id,
                    'party_sale_id' => $item['party_sale_id'] ?? null,
                    'payment_entry_id' => $item['payment_entry_id'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (empty($tripItems)) {
                DB::rollBack();
                return response()->json([
                    'message' => 'No valid trip items found to save.'
                ], 422);
            }

            TripItem::insert($tripItems);
            DB::commit();

            return response()->json([
                'message' => 'Trip sheet saved successfully.',
                'trip_id' => $trip->id
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to save trip sheet.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function trip_details(Request $request)
    {
        $selectedDate = $request->filled('trip_date')
            ? Carbon::parse($request->trip_date)->format('Y-m-d')
            : Carbon::today()->format('Y-m-d');

        $routes = RouteMaster::orderBy('name')->get();
        $selectedRouteId = $request->filled('route_id') ? (int) $request->route_id : null;

        $tripCount = 0;
        $tripItems = collect();
        $customers = collect();
        $prevTotalAmountReceived = 0;
        $totalProductReturn = 0;
        $totalOnlinePayment = 0;
        $totalBalance = 0;

        if ($selectedRouteId) {
            $tripIds = Trip::query()
                ->whereDate('trip_date', $selectedDate)
                ->where('route_id', $selectedRouteId)
                ->pluck('id');

            $tripCount = $tripIds->count();

            if ($tripCount > 0) {
                $tripItems = DB::table('trip_items as ti')
                    ->join('trips as t', 't.id', '=', 'ti.trip_id')
                    ->leftJoin('party_sales as ps', 'ps.id', '=', 'ti.party_sale_id')
                    ->leftJoin('customers as c', 'c.id', '=', 'ps.customer_id')
                    ->leftJoin('beats as b', 'b.id', '=', 'ps.beat_id')
                    ->leftJoin('payment_entries as pe', 'pe.id', '=', 'ti.payment_entry_id')
                    ->whereIn('ti.trip_id', $tripIds)
                    ->orderByDesc('ti.trip_id')
                    ->orderBy('ti.id')
                    ->select(
                        'ti.id',
                        'ti.trip_id',
                        'ti.party_sale_id',
                        'ti.payment_entry_id',
                        't.trip_number',
                        'ps.bill_no',
                        'ps.bill_date',
                        'ps.amount as sale_amount',
                        'ps.balance as sale_balance',
                        'ps.first_entry',
                        'ps.customer_id',
                        'ps.cd as party_cd',
                        'ps.product_return as party_product_return',
                        'ps.online_payment as party_online_payment',
                        'ps.amount_received as party_amount_received',
                        'ps.remarks as party_remarks',
                        'c.name as customer_name',
                        'b.name as beat_name',
                        'pe.payment_date',
                        'pe.amount_received',
                        'pe.balance as payment_balance',
                        'pe.status as payment_status',
                        'pe.cd as credit_cd',
                        'pe.product_return as credit_product_return',
                        'pe.online_payment as credit_online_payment',
                        'pe.amount_received as credit_amount_received'
                    )
                    ->get()
                    ->map(function ($item) {
                        $itemType = $item->payment_entry_id ? 'Credit' : 'Sale';
                        $item->item_type = $itemType;
                        $item->display_balance = $item->payment_entry_id
                            ? $item->payment_balance
                            : $item->sale_balance;
                        return $item;
                    });

                $customers = Customer::with('beat')->orderBy('name')->get();

                foreach ($tripItems as $item) {
                    if ($item->payment_entry_id) {
                        $prevTotalAmountReceived += (float) ($item->credit_amount_received ?? 0);
                        $totalProductReturn += (float) ($item->credit_product_return ?? 0);
                        $totalOnlinePayment += (float) ($item->credit_online_payment ?? 0);
                        $totalBalance += (float) ($item->payment_balance ?? 0);
                    } else {
                        $prevTotalAmountReceived += (float) ($item->party_amount_received ?? 0);
                        $totalProductReturn += (float) ($item->party_product_return ?? 0);
                        $totalOnlinePayment += (float) ($item->party_online_payment ?? 0);
                        $totalBalance += (float) ($item->sale_balance ?? 0);
                    }
                }
            }
        }

        return view('pages.trip_details', compact(
            'routes',
            'selectedDate',
            'selectedRouteId',
            'tripCount',
            'tripItems',
            'customers',
            'prevTotalAmountReceived',
            'totalProductReturn',
            'totalOnlinePayment',
            'totalBalance'
        ));
    }

    public function trip_details_update(Request $request, PartySaleBulkUpdateService $partySaleBulkUpdate, PaymentEntryService $paymentEntryService)
    {
        $validated = $request->validate([
            'trip_date' => ['required', 'date'],
            'route_id' => ['required', 'exists:routes,id'],
            'items' => ['nullable', 'array'],
            'items.*.party_sale_id' => ['required', 'integer', 'exists:party_sales,id'],
            'items.*.payment_entry_id' => ['nullable', 'integer', 'exists:payment_entries,id'],
            'items.*.customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'items.*.bill_date' => ['nullable', 'date'],
            'items.*.cd' => ['nullable', 'numeric'],
            'items.*.product_return' => ['nullable', 'numeric'],
            'items.*.online_payment' => ['nullable', 'numeric'],
            'items.*.amount_received' => ['nullable', 'numeric'],
            'items.*.balance' => ['nullable', 'numeric'],
        ]);

        $items = $validated['items'] ?? [];
        if ($items === []) {
            return redirect()
                ->route('trip.details', [
                    'trip_date' => $validated['trip_date'],
                    'route_id' => $validated['route_id'],
                ])
                ->with('error', 'No rows to update.');
        }

        $saleRowsByPartyId = [];
        $creditRows = [];

        foreach ($items as $row) {
            if (! empty($row['payment_entry_id'])) {
                $creditRows[] = $row;
            } else {
                $saleRowsByPartyId[(int) $row['party_sale_id']] = $row;
            }
        }

        DB::transaction(function () use ($saleRowsByPartyId, $creditRows, $partySaleBulkUpdate, $paymentEntryService) {
            foreach ($saleRowsByPartyId as $partySaleId => $data) {
                $partySaleBulkUpdate->applyRow($partySaleId, $data);
            }
            foreach ($creditRows as $data) {
                $this->applyTripDetailsCreditRow($data, $paymentEntryService);
            }
        });

        return redirect()
            ->route('trip.details', [
                'trip_date' => $validated['trip_date'],
                'route_id' => $validated['route_id'],
            ])
            ->with('success', 'Trip details saved successfully.');
    }

    private function applyTripDetailsCreditRow(array $data, PaymentEntryService $paymentEntryService): void
    {
        $partySaleId = (int) $data['party_sale_id'];
        $entryId = (int) $data['payment_entry_id'];

        $entry = PaymentEntry::query()->find($entryId);
        if (! $entry || (int) $entry->part_sale_id !== $partySaleId) {
            return;
        }

        $entry->update([
            'cd' => $data['cd'] ?? $entry->cd,
            'product_return' => $data['product_return'] ?? $entry->product_return,
            'online_payment' => $data['online_payment'] ?? $entry->online_payment,
            'amount_received' => $data['amount_received'] ?? $entry->amount_received,
        ]);

        $paymentEntryService->syncBalancesForPartySale($partySaleId);

        $entry->refresh();
        $entry->update([
            'status' => (float) $entry->balance == 0 ? 'complete' : 'pending',
        ]);

        $latest = PaymentEntry::query()
            ->where('part_sale_id', $partySaleId)
            ->orderByDesc('id')
            ->first();

        $sale = PartySale::query()->find($partySaleId);
        if ($sale && $latest) {
            $sale->cd = $latest->cd;
            $sale->product_return = $latest->product_return;
            $sale->online_payment = $latest->online_payment;
            $sale->amount_received = $latest->amount_received;
            $sale->balance = $latest->balance;
            $sale->first_entry = true;
            $sale->save();
        }
    }

    public function trip_details_routes(Request $request)
    {
        $validated = $request->validate([
            'trip_date' => ['required', 'date'],
        ]);

        $routes = RouteMaster::query()
            ->join('trips', 'trips.route_id', '=', 'routes.id')
            ->whereDate('trips.trip_date', $validated['trip_date'])
            ->select('routes.id', 'routes.name')
            ->distinct()
            ->orderBy('routes.name')
            ->get();

        return response()->json([
            'routes' => $routes,
        ]);
    }

}
