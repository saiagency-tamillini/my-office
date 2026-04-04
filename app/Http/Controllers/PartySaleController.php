<?php

namespace App\Http\Controllers;

use App\Models\PartySale;
use App\Models\Beat;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ManualBillItem;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\PaymentEntryService;
use App\Services\PartySaleBulkUpdateService;

class PartySaleController extends Controller
{
    public function index(Request $request)
    {
        // Get all salesmen for the filter checkboxes
        $salesmen = Beat::select('salesman')->distinct()->pluck('salesman');
        $beats = Beat::orderBy('name')->get();
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
        if ($request->filled('beat_id')) {
            $query->where('party_sales.beat_id', $request->beat_id);
        }
        $sales = $query->get();
        $customers = Customer::with('beat')->get();
        $selectedBeat = null;
        if ($request->filled('beat_id')) {
            $selectedBeat = Beat::find($request->beat_id);
        }
        // dd($is_today_report);
        return view('party_sales.index', compact('sales', 'salesmen', 'customers','beats','selectedBeat','is_today_report'));
    }

    public function create()
    {
        $beats = Beat::all();
        $customers = Customer::with('beat')->get();
        $products = Product::orderBy('name')->get();
        return view('party_sales.create', compact('beats','customers','products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'bill_date' => 'nullable|date',
            'aging' => 'nullable|string',
            'amount' => 'nullable|numeric',
            'cd' => 'nullable|numeric',
            'product_return' => 'nullable|numeric',
            'online_payment' => 'nullable|numeric',
            'amount_received' => 'nullable|numeric',
            'balance' => 'nullable|numeric',
            'remarks' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.box' => 'nullable|integer|min:0',
            'items.*.pcs' => 'nullable|integer|min:0',
        ]);
        DB::transaction(function () use ($request) {
            // $manualBeat = \App\Models\Beat::where('name', 'Manual')->firstOrFail();
            $manual_customer = Customer::where('id', $request->customer_id)->firstOrFail();
            $lastSale = PartySale::where('bill_no', 'like', 'MAN%')
                                ->orderBy('id', 'desc')
                                ->first();

            if ($lastSale && preg_match('/MAN(\d+)/', $lastSale->bill_no, $matches)) {
                $lastNumber = (int) $matches[1];
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }

            $bill_no = 'MAN' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            $partySale = PartySale::create([
                'beat_id' => $manual_customer->beat_id,
                'customer_id' => $request->customer_id,
                'bill_no' => $bill_no,
                'bill_date' => $request->bill_date,
                'aging' => $request->aging,
                'amount' => $request->amount,
                'cd' => $request->cd,
                'product_return' => $request->product_return,
                'online_payment' => $request->online_payment,
                'amount_received' => $request->amount_received,
                'balance' => $request->amount,
                'remarks' => $request->remarks,
            ]);
            // ✅ 2) Save ManualBillItem rows (DETAILS)
            $items = collect($request->items ?? [])
                ->filter(function ($item) {
                    return ((int)($item['box'] ?? 0) > 0) || ((int)($item['pcs'] ?? 0) > 0);
                })
                ->map(function ($item) {
                    return [
                        'product_id' => $item['product_id'],
                        'box' => (int)($item['box'] ?? 0),
                        'pcs' => (int)($item['pcs'] ?? 0),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                })
                ->values()
                ->all();

            if (!empty($items)) {
               $partySale->manualItems()->createMany($items);
            }
        });

        return redirect()->route('party-sales.index')->with('success', 'Record added successfully.');
    }

    public function edit(PartySale $partySale)
    {
        $partySale->load(['manualItems.product']);
        $beats = Beat::all();
        $customers = Customer::with('beat')->get();
        $isManualBill = str_starts_with($partySale->bill_no, 'MAN');
        $products = $isManualBill
            ? Product::orderBy('name')->get()
            : collect(); 
        $hasExistingItems = ManualBillItem::where(
                'party_sale_id',
                $partySale->id
            )->exists();
        return view('party_sales.edit', compact('partySale', 'beats', 'customers', 'products', 'isManualBill', 'hasExistingItems'));
    }

    public function update(Request $request, PartySale $partySale, PaymentEntryService $paymentEntryService)
    {
        $validated = $request->validate([
            'beat_id' => 'required|exists:beats,id',
            'customer_id' => 'required|exists:customers,id',
            'bill_no' => 'nullable|string|max:100', 
            'bill_date' => 'nullable|date',
            'aging' => 'nullable|string',
            'amount' => 'nullable|numeric',
            'cd' => 'nullable|numeric',
            'product_return' => 'nullable|numeric',
            'online_payment' => 'nullable|numeric',
            'amount_received' => 'nullable|numeric',
            'balance' => 'nullable|numeric',
            'remarks' => 'nullable|string',
            'modified' => 'nullable|boolean',
            'items' => 'nullable|array',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.box' => 'nullable|integer|min:0',
            'items.*.pcs' => 'nullable|integer|min:0',
        ]);
        DB::transaction(function () use ($request, $partySale, $validated, $paymentEntryService) {
            $amount = (float)($validated['amount'] ?? 0);
            $received = (float)($validated['amount_received'] ?? 0);
            $online_payment = (float)($validated['online_payment'] ?? 0);
            $product_return = (float)($validated['product_return'] ?? 0);
            $validated['balance'] = max(0, $amount - $received - $online_payment - $product_return);
            $validated['modified'] = $request->has('modified');
            $partySale->update($validated);
            $paymentEntryService->syncBalancesForPartySale($partySale->id);
            $isManualBill = str_starts_with($partySale->bill_no, 'MAN');
            if (!$isManualBill && $request->filled('items')) {
                abort(403, 'Products cannot be modified for this bill');
            }
            if($isManualBill){
                ManualBillItem::where('party_sale_id', $partySale->id)->delete();
                $items = collect($request->items ?? [])
                    ->filter(fn($i) => ((int)($i['box'] ?? 0) > 0) || ((int)($i['pcs'] ?? 0) > 0))
                    ->map(fn($i) => [
                        'party_sale_id' => $partySale->id,
                        'product_id' => $i['product_id'],
                        'box' => (int)($i['box'] ?? 0),
                        'pcs' => (int)($i['pcs'] ?? 0),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                    ->values()
                    ->all();

                if (!empty($items)) {
                    ManualBillItem::insert($items);
                }
            }
        });
        return redirect()->route('party-sales.index')->with('success', 'Record updated successfully.');
    }

    public function destroy($id)
    {
        try {
            $sale = PartySale::findOrFail($id);
            if(!empty($sale)){
                $sale->delete();
            }
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function download(Request $request)
    {
        $query = PartySale::with('beat')
            ->leftJoin('customers', 'party_sales.customer_id', '=', 'customers.id')
            ->select('party_sales.*', 'customers.name as customer_name');
        if ($request->filled('sort') && in_array($request->sort, ['asc', 'desc'])) {
            $query->orderBy('customers.name', $request->sort);
        }
        $billDate = $request->filled('bill_date') ? $request->bill_date : now()->format('Y-m-d');
        $query->whereDate('bill_date', $billDate);
        if ($request->filled('salesmen')) {
            $query->whereIn('beat_id', function($q) use ($request) {
                $q->select('id')
                ->from('beats')
                ->whereIn('salesman', $request->salesmen);
            });
        }

        $sales = $query->orderBy('beat_id')->orderBy('bill_date')->get();


        // Group by salesman
        $grouped = $sales->groupBy(function ($item) {
            return $item->beat->salesman;
        });

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Column headers
        $headers = [
            'S.No',
            'Customer Name',
            'Bill No',
            'Bill Date',
            'Aging',
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
            $sheet->mergeCells("A{$rowNo}:K{$rowNo}");
            $sheet->setCellValue("A{$rowNo}", $salesman);
            
            // Style header row
            $sheet->getStyle("A{$rowNo}:K{$rowNo}")->applyFromArray([
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
                    ? \Carbon\Carbon::parse($sale->bill_date)->diffInDays(\Carbon\Carbon::today(), false)
                    : 0;
                $sheet->fromArray([
                    $serial++,
                    $sale->customer->name,
                    $sale->bill_no,
                    $sale->bill_date ? \Carbon\Carbon::parse($sale->bill_date)->format('d-m-Y') : '',
                    $aging,
                    $sale->amount,
                    $sale->cd,
                    $sale->product_return,
                    $sale->online_payment,
                    $sale->amount_received,
                    $sale->balance,
                    $sale->beat->name,
                ], null, "A{$rowNo}");

                $rowNo++;
            }
        }

        $writer = new Xlsx($spreadsheet);

        return Response::streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'Party_Sales.xlsx');
    }

    public function bulkUpdate(Request $request, PartySaleBulkUpdateService $partySaleBulkUpdate)
    {
        foreach ($request->sales ?? [] as $id => $data) {
            $partySaleBulkUpdate->applyRow((int) $id, $data);
        }

        return redirect()->back()->with('success', 'Sales updated successfully');
    }
}


