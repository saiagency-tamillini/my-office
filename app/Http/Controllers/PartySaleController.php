<?php

namespace App\Http\Controllers;

use App\Models\PartySale;
use App\Models\Beat;
use App\Models\Customer;
use App\Models\PaymentEntry;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;

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
        return view('party_sales.create', compact('beats','customers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'bill_date' => 'nullable|date',
            'aging' => 'nullable|string',
            'amount' => 'nullable|numeric',
            'cd' => 'nullable|string',
            'product_return' => 'nullable|string',
            'online_payment' => 'nullable|string',
            'amount_received' => 'nullable|numeric',
            'balance' => 'nullable|numeric',
            'remarks' => 'nullable|string',
        ]);

        $manualBeat = \App\Models\Beat::where('name', 'Manual')->firstOrFail();

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
        PartySale::create([
            'beat_id' => $manualBeat->id,
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

        return redirect()->route('party-sales.index')->with('success', 'Record added successfully.');
    }

    public function edit(PartySale $partySale)
    {
        $beats = Beat::all();
        $customers = Customer::with('beat')->get(); 
        return view('party_sales.edit', compact('partySale', 'beats', 'customers'));
    }

    public function update(Request $request, PartySale $partySale)
    {
        $validated = $request->validate([
            'beat_id' => 'required|exists:beats,id',
            'customer_id' => 'required|exists:customers,id',
            'bill_no' => 'nullable|string|max:100', 
            'bill_date' => 'nullable|date',
            'aging' => 'nullable|string',
            'amount' => 'nullable|numeric',
            'cd' => 'nullable|string',
            'product_return' => 'nullable|string',
            'online_payment' => 'nullable|string',
            'amount_received' => 'nullable|numeric',
            'balance' => 'nullable|numeric',
            'remarks' => 'nullable|string',
            'modified' => 'nullable|boolean',
        ]);
        $validated['modified'] = $request->has('modified');
        $partySale->update($validated);

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

    public function bulkUpdate(Request $request)
    {
        foreach ($request->sales as $id => $data) {

            $sale = PartySale::find($id);
            if (!$sale) continue;

            $sale->fill([
                'customer_id'     => $data['customer_id'] ?? $sale->customer_id,
                'aging'           => $data['aging'] ?? $sale->aging,
                'cd'              => $data['cd'] ?? $sale->cd,
                'product_return'  => $data['product_return'] ?? $sale->product_return,
                'online_payment'  => $data['online_payment'] ?? $sale->online_payment,
                'amount_received' => $data['amount_received'] ?? $sale->amount_received,
                'balance'         => $data['balance'] ?? $sale->balance,
            ]);

            $customerChanged = $sale->isDirty('customer_id');
            $paymentChanged = $sale->isDirty([
                'amount_received',
                'cd',
                'product_return',
                'online_payment',
                'balance'
            ]);

            if ($sale->isDirty()) {
                $sale->modified = $customerChanged;
                $sale->save();
            }

            if ($customerChanged && !$paymentChanged) {
                PaymentEntry::where('bill_no', $sale->bill_no)
                    ->update(['customer_id' => $sale->customer_id]);

                continue;
            }

            if ($paymentChanged) {

                $sale->first_entry = true;
                $sale->save();

                PaymentEntry::create([
                    'part_sale_id'     => $sale->id,
                    'customer_id'      => $sale->customer_id,
                    'bill_no'          => $sale->bill_no,
                    'payment_date'     => now(),
                    'amount'           => $sale->amount,
                    'cd'               => $sale->cd,
                    'product_return'   => $sale->product_return,
                    'online_payment'   => $sale->online_payment,
                    'amount_received'  => $sale->amount_received,
                    'balance'          => $sale->balance,
                    'remarks'          => $sale->remarks,
                    'status'           => $sale->balance == 0 ? 'complete' : 'pending',
                ]);
            }
        }
        return redirect()->back()->with('success', 'Sales updated successfully');
    }
}


