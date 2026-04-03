<div class="modal fade" id="saveTripModal" tabindex="-1" aria-labelledby="saveTripModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="saveTripModalLabel">Save Trip Sheet</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="saveTripForm">
            @csrf
            <div class="mb-2">
                <label class="form-label">Trip Date</label>
                <input type="date" class="form-control" name="trip_date" value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}" required>
            </div>
            <div class="mb-2">
                <label class="form-label">Route Name</label>
                <select class="form-select" name="route_id" required>
                    <option value="">-- Select Route --</option>
                    @foreach($routes as $route)
                        <option value="{{ $route->id }}">{{ $route->name }}</option>
                    @endforeach
                </select>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="confirmSaveTrip">Save</button>
      </div>
    </div>
  </div>
</div>