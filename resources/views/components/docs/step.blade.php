@props(['number', 'image' => null, 'alt' => null])

<div class="docs-step d-flex gap-3 mb-4">
    <div class="flex-shrink-0 d-flex align-items-center justify-content-center fw-bold text-white"
         style="width:36px;height:36px;border-radius:50%;background:#1a6b3c">{{ $number }}</div>
    <div class="flex-grow-1">
        <div class="mb-2">{{ $slot }}</div>
        @if($image)
        <img src="{{ asset('docs-assets/images/'.$image) }}" alt="{{ $alt ?? 'Screenshot' }}"
             class="img-fluid rounded border shadow-sm" loading="lazy">
        @endif
    </div>
</div>
