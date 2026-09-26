@extends('layouts.app')
@section('title', 'Create Meeting')
@section('page-title', 'Create New Meeting')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="fw-semibold mb-0">
                    <i class="bi bi-calendar-plus text-success me-2"></i>Meeting Details
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.meetings.store') }}" id="meetingForm">
                    @csrf
                    {{-- Hidden fields populated by the map picker once the location is confirmed --}}
                    <input type="hidden" name="venue_lat"      id="venueLat"      value="{{ old('venue_lat') }}">
                    <input type="hidden" name="venue_lng"      id="venueLng"      value="{{ old('venue_lng') }}">
                    <input type="hidden" name="venue_postcode" id="venuePostcode" value="{{ old('venue_postcode') }}">

                    <div class="mb-3">
                        <label class="form-label fw-medium">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title"
                               class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title', 'ACM General Meeting – ' . now()->format('F Y')) }}"
                               placeholder="e.g. ACM General Meeting – March 2026"
                               required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-medium">Date <span class="text-danger">*</span></label>
                            <input type="date" name="meeting_date"
                                   class="form-control @error('meeting_date') is-invalid @enderror"
                                   value="{{ old('meeting_date') }}" required>
                            @error('meeting_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-medium">Start Time <span class="text-danger">*</span></label>
                            <input type="time" name="meeting_time"
                                   class="form-control @error('meeting_time') is-invalid @enderror"
                                   value="{{ old('meeting_time', '18:00') }}" required>
                            @error('meeting_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-medium">Late After <span class="text-danger">*</span></label>
                            <input type="time" name="late_after_time"
                                   class="form-control @error('late_after_time') is-invalid @enderror"
                                   value="{{ old('late_after_time', '18:30') }}" required>
                            <div class="form-text">Check-ins after this time are flagged as late.</div>
                            @error('late_after_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-medium">End Time <span class="text-danger">*</span></label>
                            <input type="time" name="meeting_end_time"
                                   class="form-control @error('meeting_end_time') is-invalid @enderror"
                                   value="{{ old('meeting_end_time', '20:00') }}" required>
                            <div class="form-text">QR code expires at this time.</div>
                            @error('meeting_end_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">Description <span class="text-muted small">(optional)</span></label>
                        <textarea name="description" rows="3"
                                  class="form-control @error('description') is-invalid @enderror"
                                  placeholder="Agenda or notes about this meeting">{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Venue Address <span class="text-danger">*</span></label>
                        <input type="text" name="venue" id="venueInput"
                               class="form-control @error('venue') is-invalid @enderror"
                               value="{{ old('venue') }}"
                               placeholder="Start typing an address..."
                               autocomplete="off" required>
                        <div class="form-text">Search for the venue, then confirm its exact pin location on the map below.</div>
                        @error('venue')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Location & GPS --}}
                    <div class="card border-0 bg-light mb-4">
                        <div class="card-body pb-2">
                            <h6 class="fw-semibold mb-3">
                                <i class="bi bi-geo-alt text-success me-1"></i> Confirm Location on Map
                            </h6>

                            <p class="form-text mb-2">
                                Search for the venue address above. Once it appears on the map, drag the pin to the
                                exact building, adjust the check-in radius if needed, then confirm below.
                            </p>

                            <div id="mapWrap" class="mb-3 d-none">
                                <div id="venueMap" style="width:100%; height:360px; border-radius:8px;"></div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label class="form-label fw-medium">GPS Radius (metres) <span class="text-danger">*</span></label>
                                    <input type="number" name="venue_radius" id="radiusInput"
                                           class="form-control @error('venue_radius') is-invalid @enderror"
                                           value="{{ old('venue_radius', 50) }}"
                                           min="5" max="1000" required>
                                    <div class="form-text">Members must be within this distance to check in.</div>
                                    @error('venue_radius')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-medium">If Outside Range <span class="text-danger">*</span></label>
                                    <select name="gps_failure_action"
                                            class="form-select @error('gps_failure_action') is-invalid @enderror" required>
                                        <option value="reject" {{ old('gps_failure_action', 'reject') === 'reject' ? 'selected' : '' }}>
                                            Block &amp; contact admin
                                        </option>
                                        <option value="flag" {{ old('gps_failure_action') === 'flag' ? 'selected' : '' }}>
                                            Allow but flag for review
                                        </option>
                                    </select>
                                    @error('gps_failure_action')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <button type="button" class="btn btn-outline-success btn-sm d-none" id="confirmLocationBtn">
                                <i class="bi bi-check-circle me-1"></i>Confirm Location
                            </button>

                            <div id="locationStatus" class="mt-2">
                                <span class="text-muted small">Search for the venue address above to place it on the map.</span>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success" id="submitBtn">
                            <i class="bi bi-check-circle me-1"></i>Create Meeting
                        </button>
                        <a href="{{ route('admin.meetings.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const venueInput    = document.getElementById('venueInput');
    const latField      = document.getElementById('venueLat');
    const lngField      = document.getElementById('venueLng');
    const postcodeField = document.getElementById('venuePostcode');
    const radiusInput   = document.getElementById('radiusInput');
    const mapWrap       = document.getElementById('mapWrap');
    const confirmBtn    = document.getElementById('confirmLocationBtn');
    const statusDiv     = document.getElementById('locationStatus');
    const form          = document.getElementById('meetingForm');
    const submitBtn     = document.getElementById('submitBtn');

    let map, marker, circle, autocomplete;
    let pendingAddress = venueInput.value || '';
    submitBtn.disabled = !isConfirmed();

    function isConfirmed() {
        return !!(latField.value && lngField.value);
    }

    window.initMeetingMap = function () {
        map = new google.maps.Map(document.getElementById('venueMap'), {
            center: { lat: 53.4808, lng: -2.2426 },
            zoom: 15,
            mapTypeId: 'satellite',
        });

        marker = new google.maps.Marker({ map: map, draggable: true, visible: false });

        circle = new google.maps.Circle({
            map: map,
            radius: Number(radiusInput.value) || 50,
            fillColor: '#198754',
            fillOpacity: 0.15,
            strokeColor: '#198754',
            strokeWeight: 2,
        });
        circle.bindTo('center', marker, 'position');

        autocomplete = new google.maps.places.Autocomplete(venueInput, {
            componentRestrictions: { country: 'gb' },
            fields: ['geometry', 'formatted_address', 'name', 'address_components'],
        });
        autocomplete.bindTo('bounds', map);

        autocomplete.addListener('place_changed', function () {
            const place = autocomplete.getPlace();
            if (!place.geometry || !place.geometry.location) {
                setUnconfirmed('Please choose an address from the suggestions list.');
                return;
            }

            pendingAddress = place.formatted_address || place.name || venueInput.value;
            venueInput.value    = pendingAddress;
            postcodeField.value = extractPostcode(place.address_components);

            mapWrap.classList.remove('d-none');
            map.setCenter(place.geometry.location);
            map.setZoom(18);
            marker.setPosition(place.geometry.location);
            marker.setVisible(true);

            setUnconfirmed('Drag the pin to the exact building, then confirm the location.');
        });

        marker.addListener('dragend', function () {
            setUnconfirmed('Pin moved - click Confirm Location to lock it in.');
        });

        // Restore an already-placed pin (e.g. validation error redisplay)
        if (latField.value && lngField.value) {
            const pos = { lat: parseFloat(latField.value), lng: parseFloat(lngField.value) };
            map.setCenter(pos);
            map.setZoom(18);
            marker.setPosition(pos);
            marker.setVisible(true);
            mapWrap.classList.remove('d-none');
            if (isConfirmed()) {
                statusDiv.innerHTML = '<span class="text-success small"><i class="bi bi-geo-alt-fill me-1"></i>Location confirmed: ' + escHtml(pendingAddress) + '</span>';
            }
        }
    };

    radiusInput.addEventListener('input', function () {
        if (circle) circle.setRadius(Number(radiusInput.value) || 50);
        if (marker && marker.getVisible()) setUnconfirmed('Radius changed - click Confirm Location to lock it in.');
    });

    venueInput.addEventListener('input', function () {
        if (marker && marker.getVisible()) setUnconfirmed('Address changed - search again and confirm the new location.');
    });

    confirmBtn.addEventListener('click', function () {
        const pos = marker.getPosition();
        latField.value = pos.lat();
        lngField.value = pos.lng();
        submitBtn.disabled = false;
        confirmBtn.classList.add('d-none');
        statusDiv.innerHTML = '<span class="text-success small"><i class="bi bi-geo-alt-fill me-1"></i>Location confirmed: ' + escHtml(pendingAddress) + '</span>';
    });

    // Block submit if the location hasn't been confirmed
    form.addEventListener('submit', function (e) {
        if (!isConfirmed()) {
            e.preventDefault();
            statusDiv.innerHTML = '<span class="text-danger small"><i class="bi bi-exclamation-circle me-1"></i>Please confirm the venue location on the map before saving.</span>';
        }
    });

    function setUnconfirmed(message) {
        submitBtn.disabled = true;
        latField.value = '';
        lngField.value = '';
        confirmBtn.classList.remove('d-none');
        statusDiv.innerHTML = '<span class="text-warning small"><i class="bi bi-exclamation-circle me-1"></i>' + escHtml(message) + '</span>';
    }

    function extractPostcode(components) {
        if (!components) return '';
        const comp = components.find(function (c) { return c.types.includes('postal_code'); });
        return comp ? comp.long_name : '';
    }

    function escHtml(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
})();
</script>
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&libraries=places&loading=async&callback=initMeetingMap" async defer></script>
@endpush
@endsection
