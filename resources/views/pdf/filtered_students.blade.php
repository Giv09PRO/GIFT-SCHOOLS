<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filtered Student List</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif; /* Common font for PDF generation that supports many characters */
            margin: 20px;
            font-size: 12px;
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 16px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        ul {
            list-style-type: none;
            padding: 0;
        }
        li {
            padding: 8px 0;
            border-bottom: 1px solid #f4f4f4;
        }
        li:last-child {
            border-bottom: none;
        }
        .no-students {
            text-align: center;
            color: #777;
            font-style: italic;
        }
        .report-info {
            margin-bottom: 20px;
            font-size: 10px;
            color: #555;
        }
        .report-info p {
            margin: 2px 0;
        }
    </style>
</head>
<body>
<div class="report-info">
    <p>Report Generated: {{ now()->format('Y-m-d H:i:s') }}</p>
    {{-- You can add more dynamic info here if needed, e.g., who generated it, applied filters (if passed to view) --}}
</div>

<h1>Filtered Student List</h1>

@if(isset($students) && $students->count() > 0)
    <ul>
        @foreach($students as $index => $studentName)
            <li>{{ $index + 1 }}. {{ $studentName }}</li>
        @endforeach
    </ul>
@else
    <p class="no-students">No students found matching the selected criteria.</p>
@endif

</body>
</html>
