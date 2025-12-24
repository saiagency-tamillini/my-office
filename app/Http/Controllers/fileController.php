<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Illuminate\Support\Facades\DB;
use App\Models\Beat;
use App\Models\PartySale;
use Carbon\Carbon;




class fileController extends Controller
{
     public function file_upload (){return view('files.file_upload');}

    public function uploadExcel(Request $request)
    {
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

    // private function downloadExcel(array $data, string $fileName)
    // {
    //     $spreadsheet = new Spreadsheet();
    //     $sheet = $spreadsheet->getActiveSheet();

    //     // Column headers (add S.No as first column)
    //     $headers = [
    //         'S.No',
    //         'Customer Name',
    //         'Bill No',
    //         'Bill Date',
    //         'Aging',
    //         'Amount',
    //         'CD',
    //         'Product Return',
    //         'Online Payment',
    //         'Amount Received',
    //         'Balance',
    //     ];

    //     $sheet->fromArray($headers, null, 'A1');
    //     // $sheet->getStyle('A1:K1')->getFont()->setBold(true)->setWrapText(true)->getColumnDimension('A')->setWidth(15);
    //     $sheet->getStyle('A1:K1')->getFont()->setBold(true);
    //     $sheet->getStyle('A1:K1')->getAlignment()->setWrapText(true);

    //     $standardWidth = 8;
    //     foreach (['C', 'D', 'F', 'G', 'H', 'I', 'J', 'K'] as $col) {
    //         $sheet->getColumnDimension($col)->setWidth($standardWidth);
    //     }
    //     $rowNo = 2;
    //     $serial = 1;

    //     $beatStartRow = null;
    //     $currentBeat = null;
    //     foreach ($data as $index => $row) {
    //         if ($row['type'] === 'beat') {
    //             if ($beatStartRow !== null && $rowNo - $beatStartRow > 1) {
    //                 // Handle merges here if needed
    //             }

    //             // Insert beat header row
    //             $sheet->mergeCells("A{$rowNo}:K{$rowNo}");
    //             $sheet->setCellValue("A{$rowNo}", $row['beat']);

    //             $sheet->getStyle("A{$rowNo}:K{$rowNo}")->applyFromArray([
    //                 'font' => ['bold' => true, 'size' => 12],
    //                 'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
    //                 'fill' => [
    //                     'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
    //                     'startColor' => ['rgb' => 'BDD7EE'],
    //                 ],
    //             ]);

    //             $currentBeat = $row['beat'];
    //             $beatStartRow = $rowNo + 1;
    //             $rowNo++;
    //             $serial = 1;
    //             continue;
    //         }

    //         // Insert serial number manually here
    //         $row['S.No'] = $serial++;

    //         $sheet->fromArray([
    //             $row['S.No'],
    //             $row['Customer Name'],
    //             $row['Bill No'],
    //             $row['Bill Date'],
    //             $row['Aging'],
    //             $row['Amount'],
    //             $row['CD'],
    //             $row['Product Return'],
    //             $row['Online Payment'],
    //             $row['Amount Received'],
    //             $row['Balance'],
    //         ], null, "A{$rowNo}");

    //         // Wrap text for Customer Name column (B)
    //         $sheet->getStyle("B{$rowNo}")->getAlignment()->setWrapText(true);

    //         $rowNo++;
    //     }

    //     // Set columns width
    //     // Narrow columns: S.No (A), Aging (E)
    //     $sheet->getColumnDimension('A')->setWidth(3);
    //     $sheet->getColumnDimension('E')->setWidth(3);

    //     // Customer Name (B) - wider and wrap text
    //     $sheet->getColumnDimension('B')->setWidth(8);

    //     // Other columns standard size, adjust widths so total fits A4
    //     // $standardWidth = 15; // you can adjust this for fitting
    //     foreach (['C', 'D', 'F', 'G', 'H', 'I', 'J', 'K'] as $col) {
    //         $sheet->getColumnDimension($col)->setWidth($standardWidth);
    //     }

    //     // Setup A4 page size and margins for printing
    //     $pageSetup = $sheet->getPageSetup();
    //     $pageSetup->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
    //     $pageSetup->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);

    //     // Margins (inches)
    //     $sheet->getPageMargins()->setTop(0);
    //     $sheet->getPageMargins()->setBottom(0);
    //     $sheet->getPageMargins()->setLeft(0);
    //     $sheet->getPageMargins()->setRight(0);

    //     // Optionally freeze header row
    //     $sheet->freezePane('A2');

    //     // Optional: enable autofilter on header row
    //     // $sheet->setAutoFilter("A1:K1");

    //     $writer = new Xlsx($spreadsheet);

    //     return response()->streamDownload(function () use ($writer) {
    //         $writer->save('php://output');
    //     }, $fileName);
    // }

    private function storeExcelData(array $data): void
    {
        // dd($data);
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

                PartySale::create([
                    'beat_id'        => $currentBeatId,
                    'customer_name'  => $row['Customer Name'],
                    'bill_no'        => $row['Bill No'],
                    'bill_date'      => $billDate,
                    'amount'         => $row['Amount'],
                ]);
            }
        });
    }





}
