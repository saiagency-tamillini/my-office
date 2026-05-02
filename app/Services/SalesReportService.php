<?php

namespace App\Services;

use App\Models\PartySale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesReportService
{
    public function buildReportQuery(Request $request)
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

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $from = Carbon::parse($request->from_date)->startOfDay();
            $to = Carbon::parse($request->to_date)->endOfDay();

            $query->whereBetween('party_sales.bill_date', [$from, $to]);
        } elseif ($request->filled('from_date')) {
            $query->where('party_sales.bill_date', '>=', Carbon::parse($request->from_date)->startOfDay());
        } elseif ($request->filled('to_date')) {
            $query->where('party_sales.bill_date', '<=', Carbon::parse($request->to_date)->endOfDay());
        }

        if ($request->filled('salesmen')) {
            $query->whereIn('beats.salesman', $request->salesmen);
        }

        if ($request->filled('beat_ids')) {
            $query->whereIn('party_sales.beat_id', $request->beat_ids);
        }

        $query->orderBy('customer_name', 'asc');

        return $query;
    }
}