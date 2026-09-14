{{-- resources/views/layouts/partials/breadcrumbs.blade.php (Example Path) --}}
@php
    // Get URL segments, filter out empty ones if any
global $loop;

use App\Helpers\Qs;use Illuminate\Support\Facades\Route; // Import Qs helper if not already globally available
    $segments = array_filter(Request::segments());
    $cumulativePath = ''; // To build the URL step-by-step
    $fullUrl = ''; // To store the full URL up to the previous segment for linking
    $previousDisplayText = null; // *** Variable to track the previous segment's display text ***
@endphp

<ol class="breadcrumb float-sm-right">
    {{-- Always add Home link --}}
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>

    @foreach($segments as $index => $segment)
        @php

            if ($segment === 'manage') {
            continue;
        }
           // Build the URL for the current segment level
           $previousUrl = url($cumulativePath); // URL before adding current segment
           $cumulativePath .= '/' . $segment;
           $url = url($cumulativePath);
           $isLast = $loop->last;
           $isFirst = $loop->first;

           // --- Determine Display Text ---
           if (is_numeric($segment)) {
               // Use the static method from Qs class for IDs
               $currentDisplayText = e(Qs::encode_id($segment) ?? $segment);
           } else {
               // Capitalize non-numeric segments and replace hyphens
               $currentDisplayText = e(ucfirst(str_replace('-', ' ', $segment)));
           }

           if ($index > 0 && $currentDisplayText === $previousDisplayText) {
                    $previousDisplayText = $currentDisplayText;
                    continue;
                }


           // --- Determine if it should be a link ---
           $nonLinkSegments = ['edit', 'create', 'structure', 'settings', 'report', 'assign-students', 'import', 'mapping', 'process', 'process_mapped', 'bulk-enroll', 'unassigned-students', 'reset-password', 'assign-bulk', 'waive', 'print-invoice'];
           // Don't link the last segment, numeric IDs, action words, or the very first segment (usually the prefix)
           $isLink = !$isLast && !$isFirst && !in_array($segment, $nonLinkSegments) && !is_numeric($segment);

           // --- Special Case: Link resource names to their index ---
           $resourceRoute = null;
           if ($isLink) {
               // Attempt to generate index route based on segment name, using the prefix (first segment)
               $prefix = $segments[0] ?? 'staff'; // Default prefix if needed, or get first segment
               $potentialRouteName = $prefix . '.' . $segment . '.index';
                if (Route::has($potentialRouteName)) {
                   $resourceRoute = route($potentialRouteName);
                } else {
                    $resourceRoute = $url; // Fallback to cumulative URL
                }
           }

           // *** Store current display text for next iteration's comparison ***
            $previousDisplayText = $currentDisplayText;

        @endphp

        &nbsp; &gt;&nbsp;

        @if ($isLast)
            {{-- Last segment is always active, not a link --}}
            <li class="breadcrumb-item active">{{ $currentDisplayText }}</li>
        @elseif ($isLink && $resourceRoute)
            {{-- Link resource segments (not the first/prefix) to their index route if found --}}
            <li class="breadcrumb-item"><a href="{{ $resourceRoute }}">{{ $currentDisplayText }}</a></li>
        @else
            {{-- Non-linkable segments (first/prefix, actions, IDs, or last segment) displayed as text --}}
            {{-- Link the prefix segment back to its base URL (e.g., /staff) --}}
            @if ($isFirst && count($segments) > 1)
                {{-- Link the first segment only if it's not the only segment --}}
                <li class="breadcrumb-item"><a href="{{ $url }}">{{ $currentDisplayText }}</a></li>
            @else
                <li class="breadcrumb-item">{{ $currentDisplayText }}</li>
            @endif
        @endif
    @endforeach
</ol>

<style>
    .breadcrumb-item + .breadcrumb-item::before {
        content: none !important;
    }
</style>

