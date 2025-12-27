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
use Carbon\Carbon;




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

}
