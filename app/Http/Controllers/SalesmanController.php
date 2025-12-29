<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\PartySale;
use App\Models\Beat;
use App\Models\Customer;
use App\Models\PaymentEntry;
use Illuminate\Support\Facades\DB;

class SalesmanController extends Controller
{
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
        $date = $request->filled('bill_date')
            ? Carbon::parse($request->bill_date)->format('Y-m-d') 
            : null;

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
        })
        ->pluck('id');
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
            ->when($date, function ($q) use ($date) {
                $q->whereDate('party_sales.bill_date', $date);
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
        return view('pages.sales_report', compact('sales', 'salesmen', 'customers','beats','selectedBeat'));
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

        // Use Eloquent to get payment entries
        $paymentEntries = PaymentEntry::with('customer')
            ->where('customer_id', $customer->id)
            ->orderBy('bill_no')
            ->orderBy('created_at')
            ->get()
            ->groupBy('bill_no')
            ->map(function ($entries, $billNo) {
                // Check if any entry is fully paid or status complete
                $isPaid = $entries->contains(fn($entry) => $entry->balance == 0 || $entry->status === 'complete');

                // Add the is_paid flag to **each entry** (optional)
                $entries = $entries->map(function ($entry) use ($isPaid) {
                    $entry->is_paid = $isPaid;
                    return $entry;
                });

                return $entries;
            });

        return view('pages.salesman.details', compact('customer', 'paymentEntries'));
    }
}


