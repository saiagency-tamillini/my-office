<div class="modal fade" id="creditModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Credit Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="mb-2">
                <input type="text"
                    id="creditSearch"
                    class="form-control"
                    placeholder="Search by customer name...">
            </div>
            <div class="modal-body">
                <div id="creditModalBody">
                    <div class="text-center py-5">
                        Loading...
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-success" id="addSelectedCredits">
                    Add Selected
                </button>
                <button class="btn btn-secondary" data-bs-dismiss="modal">
                    Close
                </button>
            </div>

        </div>
    </div>
</div>
