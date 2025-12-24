<?php

namespace App\Http\Controllers;

use App\Models\PartySale;
use App\Models\Beat;
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
            $query = PartySale::with('beat')
                ->join('beats', 'party_sales.beat_id', '=', 'beats.id')
                ->orderBy('beats.salesman')
                ->orderBy('party_sales.bill_date')
                ->select('party_sales.*'); 

            if ($request->filled('salesmen')) {
                $query->whereIn('beats.salesman', $request->salesmen);
            }

        if ($request->has('sort') && in_array($request->sort, ['asc', 'desc'])) {
            $query->orderBy('customer_name', $request->sort);
        }

        $sales = $query->get();

        return view('party_sales.index', compact('sales', 'salesmen'));
    }

    public function create()
    {
        $beats = Beat::all();
        return view('party_sales.create', compact('beats'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'beat_id' => 'required|exists:beats,id',
            'customer_name' => 'required|string|max:255',
            'bill_no' => 'nullable|string|max:100',
            'bill_date' => 'nullable|date',
            'aging' => 'nullable|string',
            'amount' => 'nullable|numeric',
            'cd' => 'nullable|string',
            'product_return' => 'nullable|string',
            'online_payment' => 'nullable|string',
            'amount_received' => 'nullable|numeric',
            'balance' => 'nullable|numeric',
        ]);

        PartySale::create($request->all());

        return redirect()->route('party-sales.index')->with('success', 'Record added successfully.');
    }

    public function edit(PartySale $partySale)
    {
        $beats = Beat::all();
        return view('party_sales.edit', compact('partySale', 'beats'));
    }

    public function update(Request $request, PartySale $partySale)
    {
        $request->validate([
            'beat_id' => 'required|exists:beats,id',
            'customer_name' => 'required|string|max:255',
            'bill_no' => 'nullable|string|max:100',
            'bill_date' => 'nullable|date',
            'aging' => 'nullable|string',
            'amount' => 'nullable|numeric',
            'cd' => 'nullable|string',
            'product_return' => 'nullable|string',
            'online_payment' => 'nullable|string',
            'amount_received' => 'nullable|numeric',
            'balance' => 'nullable|numeric',
        ]);

        $partySale->update($request->all());

        return redirect()->route('party-sales.index')->with('success', 'Record updated successfully.');
    }

    public function destroy($id)
    {
        try {
            $sale = PartySale::findOrFail($id);
            // dd($sale);
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

    public function download()
    {
        $sales = PartySale::with('beat')->orderBy('beat_id')->orderBy('bill_date')->get();

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
// dd($grouped);
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
                // dd($sale);
                $sheet->fromArray([
                    $serial++,
                    $sale->customer_name,
                    $sale->bill_no,
                    $sale->bill_date ? \Carbon\Carbon::parse($sale->bill_date)->format('d-m-Y') : '',
                    $sale->aging,
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
        // dd($request->all());
        foreach ($request->sales as $id => $data) {
            PartySale::where('id', $id)->update([
                'aging' => $data['aging'] ?? null,
                'cd' => $data['cd'] ?? null,
                'product_return' => $data['product_return'] ?? null,
                'online_payment' => $data['online_payment'] ?? null,
                'amount_received' => $data['amount_received'] ?? null,
            ]);
        }

        return redirect()->back()->with('success', 'Sales updated successfully');
    }

}


