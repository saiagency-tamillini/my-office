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
        return view('pages.trip_report', compact('sales', 'salesmen', 'customers','beats','selectedBeat','is_today_report'));
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
                'latest_payment.status as latest_status'
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

}
