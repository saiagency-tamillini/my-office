<?php

namespace App\Services;

use App\Models\PartySale;
use App\Models\PaymentEntry;
use Illuminate\Support\Facades\DB;

class PaymentEntryService
{
    /**
     * Recalculate amount + balance for all payment entries of a party sale
     * using running total of amount_received.
     */
    public function syncBalancesForPartySale(int $partySaleId): void
    {

        $partySale = PartySale::query()->findOrFail($partySaleId);
        $saleAmount = (float) ($partySale->amount ?? 0);

        $entries = PaymentEntry::query()
            ->where('part_sale_id', $partySaleId)
            ->orderBy('id') 
            ->get();

        $runningDeduction = 0.0;

        foreach ($entries as $entry) {

            $received       = (float) ($entry->amount_received ?? 0);
            $onlinePayment  = (float) ($entry->online_payment ?? 0);
            $cd             = (float) ($entry->cd ?? 0);
            $productReturn  = (float) ($entry->product_return ?? 0);

            // cumulative reduction of outstanding
            $runningDeduction += ($received + $onlinePayment + $cd + $productReturn);

            $balance = max(0, $saleAmount - $runningDeduction);

            $entry->update([
                'amount'  => $saleAmount,
                'balance' => $balance,
            ]);
        }
    }
}
