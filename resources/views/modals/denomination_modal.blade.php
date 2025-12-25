<!-- Denomination Modal -->
<div class="modal fade" id="denominationModal" tabindex="-1" aria-labelledby="denominationModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="denominationModalLabel">Enter Denominations</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="denominationForm">
          <div class="d-flex justify-content-between mb-2">
            <div class="me-2">
              <label class="fw-bold">10 ×</label>
              <input type="number" class="form-control denom-input" data-value="10" id="den10" min="0" value="">
            </div>
            <div class="me-2">
              <label class="fw-bold">20 ×</label>
              <input type="number" class="form-control denom-input" data-value="20" id="den20" min="0" value="">
            </div>
            <div class="me-2">
              <label class="fw-bold">50 ×</label>
              <input type="number" class="form-control denom-input" data-value="50" id="den50" min="0" value="">
            </div>
            <div>
              <label class="fw-bold">100 ×</label>
              <input type="number" class="form-control denom-input" data-value="100" id="den100" min="0" value="">
            </div>
          </div>

          <div class="d-flex justify-content-between mb-2">
            <div class="me-2">
              <label class="fw-bold">200 ×</label>
              <input type="number" class="form-control denom-input" data-value="200" id="den200" min="0" value="">
            </div>
            <div class="me-2">
              <label class="fw-bold">500 ×</label>
              <input type="number" class="form-control denom-input" data-value="500" id="den500" min="0" value="">
            </div>
            <div class="me-2">
              <label class="fw-bold">2000 ×</label>
              <input type="number" class="form-control denom-input" data-value="2000" id="den2000" min="0" value="">
            </div>
            <div>
              <label class="fw-bold">Coins</label>
              <input type="number" class="form-control denom-input" data-value="1" id="den_coins" min="0" value="">
            </div>
          </div>

          <div class="mb-2">
            <label class="fw-bold">Others</label>
            <input type="number" class="form-control denom-input" data-value="1" id="den_others" min="0" value="">
          </div>

          <!-- Total Display -->
          <div class="mt-2">
            <label class="fw-bold">Total:</label>
            <input type="text" class="form-control" id="denominationTotal" value="0" readonly>
          </div>

          <div class="alert alert-danger d-none mt-2" id="denominationError">
            Total of denominations does not match the amount received!
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="submitWithDenomination">Submit</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.denom-input');
    const totalInput = document.getElementById('denominationTotal');

    function calculateTotal() {
        let total = 0;
        inputs.forEach(input => {
            const value = parseInt(input.value) || 0;
            const multiplier = parseInt(input.dataset.value);
            total += value * multiplier;
        });
        totalInput.value = total;
    }

    inputs.forEach(input => {
        input.addEventListener('input', calculateTotal);
    });

    calculateTotal();
});
</script>
