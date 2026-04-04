<?php

namespace App\Services;

use App\Models\PartySale;
use App\Models\PaymentEntry;

class PartySaleBulkUpdateService
{
    public function applyRow(int $partySaleId, array $data): void
    {
        $sale = PartySale::find($partySaleId);
        if (! $sale) {
            return;
        }

        $sale->fill([
            'customer_id' => $data['customer_id'] ?? $sale->customer_id,
            'aging' => $data['aging'] ?? $sale->aging,
            'cd' => $data['cd'] ?? $sale->cd,
            'product_return' => $data['product_return'] ?? $sale->product_return,
            'online_payment' => $data['online_payment'] ?? $sale->online_payment,
            'amount_received' => $data['amount_received'] ?? $sale->amount_received,
            'balance' => $data['balance'] ?? $sale->balance,
        ]);

        $customerChanged = $sale->isDirty('customer_id');
        $paymentChanged = $sale->isDirty([
            'amount_received',
            'cd',
            'product_return',
            'online_payment',
            'balance',
        ]);

        if ($sale->isDirty()) {
            $sale->modified = $customerChanged;
            $sale->save();
        }

        if ($customerChanged && ! $paymentChanged) {
            PaymentEntry::where('bill_no', $sale->bill_no)
                ->update(['customer_id' => $sale->customer_id]);

            return;
        }

        if ($paymentChanged) {
            $sale->first_entry = true;
            $sale->save();

            PaymentEntry::create([
                'part_sale_id' => $sale->id,
                'customer_id' => $sale->customer_id,
                'bill_no' => $sale->bill_no,
                'payment_date' => now(),
                'amount' => $sale->amount,
                'cd' => $sale->cd,
                'product_return' => $sale->product_return,
                'online_payment' => $sale->online_payment,
                'amount_received' => $sale->amount_received,
                'balance' => $sale->balance,
                'remarks' => $sale->remarks,
                'status' => $sale->balance == 0 ? 'complete' : 'pending',
                'party_sale_payment' => true,
            ]);
        }
    }
}
