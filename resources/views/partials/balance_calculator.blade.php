{{-- Sticky bar for Balance Calculator (controlled by public/js/balance-calculator.js) --}}
<div id="balanceCalcStickyBar"
     class="balance-calc-sticky-bar d-none shadow-lg"
     role="region"
     aria-label="Selected rows totals"
     aria-hidden="true">
    <div class="container-fluid py-2 px-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex flex-wrap align-items-center gap-3 small">
                <span class="text-white-50">Selected:</span>
                <strong data-balance-calc-count>0</strong>
                <span class="text-white-50">rows</span>
                <span class="vr text-white-50 d-none d-md-block"></span>
                <span>Amount: <strong data-balance-calc-total="amount">0.00</strong></span>
                <span>CD: <strong data-balance-calc-total="cd">0.00</strong></span>
                <span>Return: <strong data-balance-calc-total="product_return">0.00</strong></span>
                <span>Online: <strong data-balance-calc-total="online_payment">0.00</strong></span>
                <span>Received: <strong data-balance-calc-total="amount_received">0.00</strong></span>
                <span>Balance: <strong data-balance-calc-total="balance">0.00</strong></span>
            </div>
            <button type="button"
                    id="balanceCalcStickyClose"
                    class="btn btn-sm btn-outline-light"
                    title="Exit selection mode">
                Close
            </button>
        </div>
    </div>
</div>
