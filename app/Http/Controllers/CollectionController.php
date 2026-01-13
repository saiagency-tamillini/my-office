<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Beat;
use App\Models\PaymentEntry;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Facades\Response;



class CollectionController extends Controller
{
    public function index(Request $request)
    {
        $beats = Beat::orderBy('name')->get();
        $salesmen = Beat::whereNotNull('salesman')
            ->select('salesman')
            ->distinct()
            ->orderBy('salesman')
            ->pluck('salesman');

        $filtersApplied = $request->filled('salesman') || $request->filled('date') || $request->filled('beat_id');

        $entries = null;

        // per page dropdown values
        $perPage = (int) $request->get('per_page', 25);
        $allowed = [10, 25, 50, 100, 200, 500];
        if (!in_array($perPage, $allowed)) $perPage = 25;

        if ($filtersApplied) {
            $query = PaymentEntry::with(['customer', 'partySale.beat', 'partySale.customer']);

            if ($request->filled('salesman')) {
                $query->whereHas('partySale.beat', function ($q) use ($request) {
                    $q->where('salesman', $request->salesman);
                });
            }

            if ($request->filled('date')) {
                $query->whereDate('payment_date', $request->date);
            }

            if ($request->filled('beat_id')) {
                $query->whereHas('partySale.beat', function ($q) use ($request) {
                    $q->where('id', $request->beat_id);
                });
            }

            $entries = $query->latest('payment_date')
                ->paginate($perPage)
                ->withQueryString();
        }

        return view('collections.index', compact('salesmen', 'entries', 'filtersApplied', 'perPage', 'beats'));
    }

    public function download(Request $request)
    {
        $query = PaymentEntry::with(['customer', 'partySale.beat', 'partySale.customer']);

        // Filter: salesman
        if ($request->filled('salesman')) {
            $query->whereHas('partySale.beat', function ($q) use ($request) {
                $q->where('salesman', $request->salesman);
            });
        }

        // Filter: payment date
        if ($request->filled('date')) {
            $query->whereDate('payment_date', $request->date);
        }

        $entries = $query->orderBy('payment_date', 'desc')->get();

        // Group by salesman (fallback "No Salesman")
        $grouped = $entries->groupBy(function ($item) {
            return optional(optional($item->partySale)->beat)->salesman ?? 'No Salesman';
        });

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Column headers
        $headers = [
            'S.No',
            'Payment Date',
            'Salesman',
            'Customer Name',
            'Bill No',
            'Amount',
            'CD',
            'Product Return',
            'Online Payment',
            'Amount Received',
            'Balance',
            'Remarks',
            'Status',
        ];
        $sheet->fromArray($headers, null, 'A1');

        // Optional: style header row
        $sheet->getStyle("A1:M1")->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E9ECEF'],
            ],
        ]);

        $rowNo = 2;

        foreach ($grouped as $salesman => $items) {

            // Salesman group title row
            $sheet->mergeCells("A{$rowNo}:M{$rowNo}");
            $sheet->setCellValue("A{$rowNo}", $salesman);

            $sheet->getStyle("A{$rowNo}:M{$rowNo}")->applyFromArray([
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'A3A1A1'],
                ],
            ]);

            $rowNo++;
            $serial = 1;

            foreach ($items as $entry) {

                $customerName = optional($entry->customer)->name
                    ?? optional(optional($entry->partySale)->customer)->name
                    ?? '';

                $sheet->fromArray([
                    $serial++,
                    $entry->payment_date ? $entry->payment_date->format('d-m-Y') : '',
                    $salesman,
                    $customerName,
                    $entry->bill_no ?? '',
                    $entry->amount ?? 0,
                    $entry->cd ?? 0,
                    $entry->product_return ?? 0,
                    $entry->online_payment ?? 0,
                    $entry->amount_received ?? 0,
                    $entry->balance ?? 0,
                    $entry->remarks ?? '',
                    $entry->status ?? '',
                ], null, "A{$rowNo}");

                $rowNo++;
            }
        }

        // Optional: auto width
        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);

        $fileName = 'Collections_' . ($request->filled('date') ? $request->date : 'All') . '.xlsx';

        return Response::streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName);
    }
}

