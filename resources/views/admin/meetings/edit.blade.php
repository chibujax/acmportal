@extends('layouts.app')
@section('title', 'Edit Meeting')
@section('page-title', 'Edit Meeting')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="fw-semibold mb-0">
                    <i class="bi bi-pencil text-primary me-2"></i>Edit: {{ $meeting->title }}
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.meetings.update', $meeting) }}" id="meetingForm">
                    @csrf @method('PUT')
                    {{-- Hidden geocode fields populated by JS after address lookup --}}
                    <input type="hidden" name="venue_lat"      id="venueLat"      value="{{ old('venue_lat', $meeting->venue_lat) }}">
                    <input type="hidden" name="venue_lng"      id="venueLng"      value="{{ old('venue_lng', $meeting->venue_lng) }}">
                    <input type="hidden" name="geocode_source" id="geocodeSource" value="{{ old('geocode_source', $meeting->venue_lat ? 'ideal_postcodes' : '') }}">

                    <div class="mb-3">
                        <label class="form-label fw-medium">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title"
                               class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title', $meeting->title) }}" required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-medium">Date <span class="text-danger">*</span></label>
                            <input type="date" name="meeting_date"
                                   class="form-control @error('meeting_date') is-invalid @enderror"
                                   value="{{ old('meeting_date', $meeting->meeting_date->format('Y-m-d')) }}" required>
                            @error('meeting_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-medium">Start Time <span class="text-danger">*</span></label>
                            <input type="time" name="meeting_time"
                                   class="form-control @error('meeting_time') is-invalid @enderror"
                                   value="{{ old('meeting_time', \Carbon\Carbon::parse($meeting->meeting_time)->format('H:i')) }}" required>
                            @error('meeting_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-medium">Late After <span class="text-danger">*</span></label>
                            <input type="time" name="late_after_time"
                                   class="form-control @error('late_after_time') is-invalid @enderror"
                                   value="{{ old('late_after_time', $meeting->late_after_time ? \Carbon\Carbon::parse($meeting->late_after_time)->format('H:i') : '18:15') }}" required>
                            <div class="form-text">Check-ins after this time are flagged as late.</div>
                            @error('late_after_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-medium">End Time <span class="text-danger">*</span></label>
                            <input type="time" name="meeting_end_time"
                                   class="form-control @error('meeting_end_time') is-invalid @enderror"
                                   value="{{ old('meeting_end_time', $meeting->meeting_end_time ? \Carbon\Carbon::parse($meeting->meeting_end_time)->format('H:i') : '20:00') }}" required>
                            <div class="form-text">QR code expires at this time.</div>
                            @error('meeting_end_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Venue Address <span class="text-danger">*</span></label>
                        <input type="text" name="venue" id="venueInput"
                               class="form-control @error('venue') is-invalid @enderror"
                               value="{{ old('venue', $meeting->venue) }}"
                               placeholder="Auto-filled when you select an address below"
                               readonly required>
                        <div class="form-text">To change the venue, enter a new postcode in the Location section and select an address.</div>
                        @error('venue')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">Description</label>
                        <textarea name="description" rows="3"
                                  class="form-control @error('description') is-invalid @enderror">{{ old('description', $meeting->description) }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- ── Location & GPS ───────────────────────────── --}}
                    <div class="card border-0 bg-light mb-4">
                        <div class="card-body pb-2">
                            <h6 class="fw-semibold mb-3">
                                <i class="bi bi-geo-alt text-success me-1"></i> Location & GPS Check-In
                            </h6>

                            <div class="mb-3">
                                <label class="form-label fw-medium">Venue Postcode <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" name="venue_postcode" id="venuePostcode"
                                           class="form-control @error('venue_postcode') is-invalid @enderror"
                                           value="{{ old('venue_postcode', $meeting->venue_postcode) }}"
                                           placeholder="e.g. M21 9WQ"
                                           required
                                           style="text-transform:uppercase">
                                    <button type="button" class="btn btn-outline-success" id="lookupBtn">
                                        <i class="bi bi-search me-1"></i>Look Up
                                    </button>
                                </div>
                                @error('venue_postcode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div id="lookupStatus" class="mt-2">
                                    @if($meeting->venue_lat)
                                        <span class="text-success small"><i class="bi bi-geo-alt-fill me-1"></i>Address confirmed: {{ $meeting->venue }} — change postcode and Look Up to update</span>
                                    @else
                                        <span class="text-muted small">Enter the postcode and click Look Up to find addresses.</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Address dropdown (shown after lookup) --}}
                            <div id="addressSelectWrap" class="mb-3 d-none">
                                <label class="form-label fw-medium">Select Address <span class="text-danger">*</span></label>
                                <select id="addressSelect" class="form-select">
                                    <option value="">— select an address —</option>
                                </select>
                                <div class="form-text">Select the specific address for GPS check-in accuracy.</div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label class="form-label fw-medium">GPS Radius (metres) <span class="text-danger">*</span></label>
                                    <input type="number" name="venue_radius"
                                           class="form-control @error('venue_radius') is-invalid @enderror"
                                           value="{{ old('venue_radius', $meeting->venue_radius ?? 25) }}"
                                           min="10" max="1000" required>
                                    <div class="form-text">Members must be within this distance to check in.</div>
                                    @error('venue_radius')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-medium">If Outside Range <span class="text-danger">*</span></label>
                                    <select name="gps_failure_action"
                                            class="form-select @error('gps_failure_action') is-invalid @enderror" required>
                                        <option value="reject" {{ old('gps_failure_action', $meeting->gps_failure_action) === 'reject' ? 'selected' : '' }}>
                                            Block &amp; contact admin
                                        </option>
                                        <option value="flag" {{ old('gps_failure_action', $meeting->gps_failure_action) === 'flag' ? 'selected' : '' }}>
                                            Allow but flag for review
                                        </option>
                                    </select>
                                    @error('gps_failure_action')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success" id="submitBtn">
                            <i class="bi bi-check-circle me-1"></i>Save Changes
                        </button>
                        <a href="{{ route('admin.meetings.show', $meeting) }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const lookupUrl   = '{{ route('admin.meetings.verify-address') }}';
    const csrfToken   = document.querySelector('meta[name="csrf-token"]').content;

    const venueInput    = document.getElementById('venueInput');
    const postcodeInput = document.getElementById('venuePostcode');
    const lookupBtn     = document.getElementById('lookupBtn');
    const statusDiv     = document.getElementById('lookupStatus');
    const latField      = document.getElementById('venueLat');
    const lngField      = document.getElementById('venueLng');
    const sourceField   = document.getElementById('geocodeSource');
    const addressSelect = document.getElementById('addressSelect');
    const addressWrap   = document.getElementById('addressSelectWrap');
    const form          = document.getElementById('meetingForm');
    const submitBtn     = document.getElementById('submitBtn');

    // Pre-confirmed if meeting already has coordinates
    let addressConfirmed = !!(latField.value && lngField.value);
    submitBtn.disabled = !addressConfirmed;

    // Postcode change → require fresh lookup
    postcodeInput.addEventListener('input', function () {
        latField.value    = '';
        lngField.value    = '';
        sourceField.value = '';
        venueInput.value  = '';
        addressSelect.innerHTML = '<option value="">— select an address —</option>';
        addressWrap.classList.add('d-none');
        addressConfirmed  = false;
        submitBtn.disabled = true;
        statusDiv.innerHTML = '<span class="text-warning small"><i class="bi bi-arrow-clockwise me-1"></i>Postcode changed — click Look Up and select an address.</span>';
    });

    lookupBtn.addEventListener('click', doLookup);

    async function doLookup() {
        const postcode = postcodeInput.value.trim();

        if (!postcode) {
            statusDiv.innerHTML = '<span class="text-danger small"><i class="bi bi-exclamation-circle me-1"></i>Please enter a postcode first.</span>';
            return;
        }

        lookupBtn.disabled = true;
        lookupBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Looking up…';
        statusDiv.innerHTML = '';
        addressWrap.classList.add('d-none');
        addressConfirmed  = false;
        submitBtn.disabled = true;

        try {
            const res  = await fetch(lookupUrl, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body:    JSON.stringify({ postcode }),
            });
            const data = await res.json();

            if (data.success && data.addresses.length > 0) {
                addressSelect.innerHTML = '<option value="">— select an address —</option>';
                data.addresses.forEach((addr, i) => {
                    const opt = document.createElement('option');
                    opt.value = i;
                    opt.textContent = addr.address;
                    opt.dataset.lat     = addr.lat;
                    opt.dataset.lng     = addr.lng;
                    opt.dataset.address = addr.address;
                    addressSelect.appendChild(opt);
                });
                addressWrap.classList.remove('d-none');
                statusDiv.innerHTML = `<span class="text-success small"><i class="bi bi-list-ul me-1"></i>${data.addresses.length} address(es) found — please select one below.</span>`;
            } else {
                statusDiv.innerHTML = `<span class="text-danger small"><i class="bi bi-x-circle me-1"></i>${escHtml(data.message || 'No addresses found for this postcode.')}</span>`;
            }
        } catch (e) {
            statusDiv.innerHTML = '<span class="text-danger small"><i class="bi bi-x-circle me-1"></i>Network error. Please try again.</span>';
        } finally {
            lookupBtn.disabled = false;
            lookupBtn.innerHTML = '<i class="bi bi-search me-1"></i>Look Up';
        }
    }

    addressSelect.addEventListener('change', function () {
        if (this.value === '') {
            venueInput.value  = '';
            latField.value    = '';
            lngField.value    = '';
            sourceField.value = '';
            addressConfirmed  = false;
            submitBtn.disabled = true;
            return;
        }
        const opt = this.options[this.selectedIndex];
        venueInput.value  = opt.dataset.address;
        latField.value    = opt.dataset.lat;
        lngField.value    = opt.dataset.lng;
        sourceField.value = 'ideal_postcodes';
        addressConfirmed  = true;
        submitBtn.disabled = false;
        statusDiv.innerHTML = `<span class="text-success small"><i class="bi bi-geo-alt-fill me-1"></i>Address confirmed: ${escHtml(opt.dataset.address)}</span>`;
    });

    // Block submit if no address confirmed
    form.addEventListener('submit', function (e) {
        if (!addressConfirmed) {
            e.preventDefault();
            statusDiv.innerHTML = '<span class="text-danger small"><i class="bi bi-exclamation-circle me-1"></i>Please look up the postcode and select an address before saving.</span>';
            postcodeInput.focus();
        }
    });

    function escHtml(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
})();
</script>
@endpush
@endsection
