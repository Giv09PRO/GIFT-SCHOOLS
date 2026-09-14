<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $fee->invoice_no ?? 'N/A' }}</title>
    {{-- Ensure 'Roboto' font is available to PDF engine (self-host or ensure internet access for Google Fonts) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">

    <style>
        /* ---=== Root Variables (CSS Custom Properties) ===--- */
        :root {
            --font-roboto: 'Roboto', Helvetica, Arial, sans-serif;
            --color-primary: #4285F4; /* Google Blue */
            --color-text-primary: #212121;
            --color-text-secondary: #757575;
            --color-text-hint: #9E9E9E;
            --color-on-primary: #FFFFFF;
            --color-surface: #FFFFFF;
            --color-background: #FFFFFF;
            --color-border: #E0E0E0;
            --color-table-header-bg: #F5F5F5;
            --color-waived-text: #dc3545;
            --color-waived-border: #dc3545;
            --color-waived-bg: #fdf2f2;
            --spacing-base: 6pt;
            --spacing-xsmall: 3pt;
            --spacing-small: 6pt;
            --spacing-medium: 9pt;
            --spacing-large: 12pt;
            --spacing-xlarge: 18pt;
            --spacing-xxlarge: 24pt;
        }

        /* ---=== Page Setup (CSS Paged Media) ===--- */
        @page {
            size: A4 portrait;
            margin: 18pt;

            @bottom-right {
                content: "Page " counter(page) " of " counter(pages);
                font-family: var(--font-roboto), serif;
                font-size: 9pt;
                color: var(--color-text-secondary);
                padding-top: var(--spacing-base);
            }

            @bottom-left {
                content: "Generated: {{ isset($issueDate) ? $issueDate->format('M d, Y') : now()->format('M d, Y') }}";
                font-family: var(--font-roboto);
                font-size: 9pt;
                color: var(--color-text-secondary);
                padding-top: var(--spacing-base);
            }
        }

        /* ---=== Basic Body & Container ===--- */
        body {
            font-family: var(--font-roboto), system-ui;
            font-size: 10.5pt;
            line-height: 1.5;
            color: var(--color-text-primary);
            background-color: var(--color-background);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        .container {
            width: 100%;
        }

        /* ---=== Material Type Scale Classes ===--- */
        .md-headline-5 { font-size: 18pt; font-weight: 400; letter-spacing: 0; line-height: 1.2; }
        .md-headline-6 { font-size: 15pt; font-weight: 500; letter-spacing: 0.11pt; line-height: 1.2; }
        .md-subtitle-1 { font-size: 12pt; font-weight: 400; letter-spacing: 0.11pt; line-height: 1.4; }
        .md-subtitle-2 { font-size: 10.5pt; font-weight: 500; letter-spacing: 0.07pt; line-height: 1.4; }
        .md-body-1 { font-size: 12pt; font-weight: 400; letter-spacing: 0.37pt; line-height: 1.5; }
        .md-body-2 { font-size: 10.5pt; font-weight: 400; letter-spacing: 0.18pt; line-height: 1.5; }
        .md-body-2-medium { font-size: 10.5pt; font-weight: 500; letter-spacing: 0.18pt; line-height: 1.5; }
        .md-button-text { font-size: 10.5pt; font-weight: 500; letter-spacing: 0.9pt; line-height: 1.5; text-transform: uppercase; }
        .md-caption { font-size: 9pt; font-weight: 400; letter-spacing: 0.3pt; line-height: 1.5; }
        .md-overline { font-size: 7.5pt; font-weight: 400; letter-spacing: 1.1pt; line-height: 1.5; text-transform: uppercase; }

        /* ---=== Layout Spacing & Structure ===--- */
        .invoice-header {
            margin-bottom: var(--spacing-xlarge);
            padding-bottom: var(--spacing-large);
            position: relative;
            min-height: 48pt;
            overflow: hidden;
        }
        .invoice-header .logo {
            max-height: 36pt;
            max-width: 150pt;
            display: block;
            float: left;
        }
        .invoice-header .logo-placeholder {
            display: block;
            float: left;
            font-size: 15pt;
            font-weight: 500;
            color: var(--color-primary);
            line-height: 36pt;
        }
        .invoice-header .invoice-title {
            float: right;
            margin: 0;
            line-height: 36pt;
            color: var(--color-primary);
        }

        .info-section-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: var(--spacing-xlarge);
            table-layout: fixed;
        }
        .info-left-column {
            width: 60%;
            vertical-align: top;
            padding-right: var(--spacing-xlarge);
        }
        .info-right-column {
            width: 40%;
            vertical-align: top;
            text-align: right;
        }

        .school-details,
        .bill-to,
        .invoice-details {
            margin-bottom: var(--spacing-large);
        }
        .bill-to {
            margin-top: var(--spacing-xlarge);
        }

        .school-details p,
        .bill-to p,
        .invoice-details p {
            margin: 0 0 var(--spacing-small) 0;
            line-height: 1.4;
        }
        .bill-to h3, .invoice-details h3 {
            margin-bottom: var(--spacing-medium);
        }

        .label {
            color: var(--color-text-secondary);
            font-weight: 500;
            margin-right: var(--spacing-small);
        }

        .section-title {
            margin-top: var(--spacing-xlarge);
            margin-bottom: var(--spacing-medium);
            color: var(--color-primary);
            border-bottom: 1pt solid var(--color-border);
            padding-bottom: var(--spacing-small);
        }

        /* ---=== Tables (Fee Details, Payment History) ===--- */
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: var(--spacing-xlarge);
            table-layout: fixed;
        }
        .details-table th,
        .details-table td {
            border: 1pt solid var(--color-border);
            padding: var(--spacing-medium) var(--spacing-large);
            text-align: left;
            vertical-align: top;
            word-wrap: break-word;
        }
        .details-table thead th {
            background-color: var(--color-table-header-bg);
            color: var(--color-primary);
            border-bottom-width: 2pt;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.9pt;
            font-size: 10.5pt;
        }
        .details-table tbody tr {
            break-inside: avoid;
        }
        .details-table .comment-row td {
            font-style: italic;
            color: var(--color-text-secondary);
            border-top: 1pt dashed var(--color-border);
        }
        .details-table .parent-fee-row td {
            font-size: 9pt; /* Smaller for parent fee context */
            color: var(--color-text-secondary);
            padding-top: var(--spacing-xsmall);
            padding-bottom: var(--spacing-xsmall);
            border-bottom: none; /* Remove bottom border for this row */
        }
        .details-table .installment-row td {
            border-top: none; /* Remove top border to connect with parent fee row */
        }


        /* ---=== Summary Section ===--- */
        .summary-section-wrapper {
            overflow: hidden;
            margin-top: var(--spacing-xlarge);
        }
        .summary-section {
            float: right;
            width: 45%;
            max-width: 200pt;
        }
        .summary-section dl {
            margin: 0;
            width: 100%;
            overflow: hidden;
        }
        .summary-section dt {
            clear: left;
            float: left;
            width: 60%;
            margin-bottom: var(--spacing-small);
            padding-right: var(--spacing-medium);
            color: var(--color-text-secondary);
            text-align: right;
            font-weight: 500;
        }
        .summary-section dd {
            float: right;
            width: 40%;
            margin-left: 0;
            margin-bottom: var(--spacing-small);
            text-align: right;
        }
        .summary-total-label,
        .summary-total-value {
            font-weight: 500;
            padding-top: var(--spacing-medium);
            border-top: 2pt solid var(--color-primary);
            margin-top: var(--spacing-small);
        }
        .summary-total-label {
            color: var(--color-text-primary);
        }
        .summary-total-value {
            color: var(--color-primary);
        }

        /* ---=== Footer ===--- */
        .invoice-footer {
            margin-top: var(--spacing-xxlarge);
            padding-top: var(--spacing-large);
            border-top: 1pt solid var(--color-border);
            text-align: center;
            color: var(--color-text-hint);
        }
        .invoice-footer p {
            margin-bottom: var(--spacing-xsmall);
        }
        .invoice-footer a {
            color: var(--color-primary);
            text-decoration: none;
        }
        .invoice-footer a:hover {
            text-decoration: underline;
        }

        /* ---=== Utilities ===--- */
        .text-right { text-align: right !important; }
        .text-left { text-align: left !important; }
        .text-center { text-align: center !important; }

        .waived-notice {
            color: var(--color-waived-text);
            font-weight: bold;
            text-align: center;
            margin: var(--spacing-large) 0;
            border: 1pt dashed var(--color-waived-border);
            padding: var(--spacing-base);
            background-color: var(--color-waived-bg);
            break-inside: avoid;
        }

        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
    </style>
</head>
<body>
<div class="container">

    {{-- ==================== INVOICE HEADER ==================== --}}
    <div class="invoice-header clearfix">
        @if(isset($school) && $school->logo)
            <img src="{{ public_path($school->logo) }}" alt="{{ $school->title ?? 'School Logo' }}" class="logo">
            {{-- Note: For PDF, public_path() or absolute path to image is often needed if not base64 encoded --}}
        @else
            <span class="logo-placeholder">{{ $school->title ?? 'School Name' }}</span>
        @endif
        <h1 class="invoice-title md-headline-5">Invoice</h1>
    </div>

    {{-- ==================== INFO SECTION (School/Bill To/Invoice Details) ==================== --}}
    <table class="info-section-table">
        <tr>
            <td class="info-left-column">
                <div class="school-details">
                    <h2 class="md-subtitle-1">{{ $school->title ?? 'School Name' }}</h2>
                    <p class="md-body-2">{{ $school->address ?? 'School Address' }}</p>
                    <p class="md-body-2">Phone: {{ $school->school_phone ?? $school->phone ?? 'School Phone' }}</p>
                    <p class="md-body-2">Email: {{ $school->email ?? 'school@example.com' }}</p>
                </div>

                <div class="bill-to">
                    <h3 class="md-subtitle-1">Bill To:</h3>
                    @if(isset($student))
                        <p><span class="label md-body-2-medium">Student Name:</span> <span class="md-body-1">{{ $student->last_name ?? '' }}, {{ $student->first_name ?? '' }} {{ $student->middle_name ?? '' }}</span></p>
                        <p><span class="label md-body-2-medium">Student ID:</span> <span class="md-body-1">{{ $student->username ?? $student->prem_number ?? 'N/A' }}</span></p>
                        <p><span class="label md-body-2-medium">Address:</span> <span class="md-body-1">{{ $student->address ?? 'N/A' }}</span></p>
                        <p><span class="label md-body-2-medium">Grade:</span> <span class="md-body-1">{{ $student->enrollments->firstWhere('syear', $fee->syear ?? Qs::getCurrentSchoolYear())?->grade->title ?? 'N/A' }}</span></p>
                    @else
                        <p class="md-body-2">Student Information Not Available</p>
                    @endif
                </div>
            </td>

            <td class="info-right-column">
                <div class="invoice-details">
                    <p><span class="label md-body-2-medium">Invoice #:</span> <span class="md-body-1">{{ $fee->invoice_no ?? 'N/A' }}</span></p>
                    <p><span class="label md-body-2-medium">Issue Date:</span> <span class="md-body-1">{{ isset($issueDate) ? $issueDate->format('M d, Y') : now()->format('M d, Y') }}</span></p>
                    <p><span class="label md-body-2-medium">Fee Assigned:</span> <span class="md-body-1">{{ isset($fee->assigned_date) ? $fee->assigned_date->format('M d, Y') : 'N/A' }}</span></p>
                    <p><span class="label md-body-2-medium">Due Date:</span> <span class="md-body-1">{{ isset($fee->due_date) ? $fee->due_date->format('M d, Y') : 'N/A' }}</span></p>
                </div>
            </td>
        </tr>
    </table>

    {{-- ==================== WAIVED FEE NOTICE (Conditional) ==================== --}}
    @if(isset($fee) && $fee->is_waived) {{-- Use the accessor --}}
    <div class="waived-notice md-body-2">
        This fee installment has been waived. No payment is due.
    </div>
    @endif

    {{-- ==================== FEE DETAILS TABLE ==================== --}}
    <h3 class="md-subtitle-1 section-title">Fee Details</h3>
    <table class="details-table">
        <thead>
        <tr>
            <th class="md-button-text text-left">Description</th>
            <th class="md-button-text text-right">Amount</th>
        </tr>
        </thead>
        <tbody>
        @if($fee->feeDefinition)
            <tr class="parent-fee-row">
                <td colspan="2">
                    Parent Fee: {{ $fee->feeDefinition->fee_name }}
                    (Total: {{ number_format($fee->feeDefinition->total_amount, 2) }},
                    {{ $fee->feeDefinition->number_of_installments }} Installment{{ $fee->feeDefinition->number_of_installments > 1 ? 's' : '' }})
                </td>
            </tr>
            <tr class="installment-row"> {{-- Main Fee Installment Item --}}
                <td class="md-body-2">{{ $fee->title ?? 'N/A' }} (Installment {{ $fee->installment_number ?? 'N/A' }})</td>
                <td class="md-body-2 text-right">{{ isset($fee->amount) ? number_format($fee->amount, 2) : '0.00' }}</td>
            </tr>
        @else
            <tr> {{-- Main Fee Item (Standalone) --}}
                <td class="md-body-2">{{ $fee->title ?? 'N/A' }}</td>
                <td class="md-body-2 text-right">{{ isset($fee->amount) ? number_format($fee->amount, 2) : '0.00' }}</td>
            </tr>
        @endif

        @if(isset($fee) && !empty($fee->comments))
            <tr class="comment-row">
                <td colspan="2">
                    <strong class="md-body-2-medium">Comments:</strong>
                    <span class="md-body-2">{{ $fee->comments }}</span>
                </td>
            </tr>
        @endif
        </tbody>
    </table>

    {{-- ==================== PAYMENT HISTORY TABLE (Conditional) ==================== --}}
    @if(isset($fee) && $fee->payments->isNotEmpty())
        <h3 class="md-subtitle-1 section-title">Payment History for this Installment</h3>
        <table class="details-table">
            <thead>
            <tr>
                <th class="md-button-text text-left">Date Paid</th>
                <th class="md-button-text text-left">Method/Comment</th>
                <th class="md-button-text text-right">Amount Applied</th>
            </tr>
            </thead>
            <tbody>
            @foreach($fee->payments as $payment)
                <tr>
                    {{-- Prefer payment_date of the Payment record itself, or pivot created_at if more specific to allocation time --}}
                    <td class="md-body-2">{{ $payment->payment_date ? $payment->payment_date->format('M d, Y') : ($payment->pivot->created_at ? $payment->pivot->created_at->format('M d, Y') : 'N/A') }}</td>
                    <td class="md-body-2">{{ $payment->payment_method ?? ($payment->comments ? Str::limit($payment->comments, 50) : 'N/A') }}</td>
                    <td class="md-body-2 text-right">{{ isset($payment->pivot->amount_applied) ? number_format($payment->pivot->amount_applied, 2) : 'N/A' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    {{-- ==================== SUMMARY SECTION ==================== --}}
    <div class="summary-section-wrapper">
        <div class="summary-section">
            <dl>
                <dt class="md-body-2">Installment Subtotal:</dt>
                <dd class="md-body-2 text-right">{{ isset($fee->amount) ? number_format($fee->amount, 2) : '0.00' }}</dd>

                <dt class="md-body-2">Total Paid (This Installment):</dt>
                <dd class="md-body-2 text-right">{{ isset($totalPaid) ? number_format($totalPaid, 2) : '0.00' }}</dd>

                <dt class="md-body-2 summary-total-label">Balance Due (This Installment):</dt>
                <dd class="md-headline-6 summary-total-value text-right">
                    @php
                        // $totalPaid is passed from controller, specific to this installment
                        $calculatedBalance = (isset($fee) && $fee->is_waived) ? 0.00 : ($fee->amount ?? 0) - ($totalPaid ?? 0);
                    @endphp
                    {{ number_format($calculatedBalance, 2) }}
                </dd>
            </dl>
        </div>
    </div>

    {{-- ==================== FOOTER SECTION ==================== --}}
    <div class="invoice-footer">
        <p class="md-caption">Thank you for your prompt payment.</p>
        <p class="md-caption">Payment can be made via the online portal or by bank transfer. Please contact the finance office for details.</p>
        <p class="md-caption">For any inquiries, please contact the finance department at <a href="mailto:{{ $school->email ?? 'finance@example.com' }}">{{ $school->email ?? 'finance@example.com' }}</a>.</p>
    </div>

</div> {{-- End .container --}}
</body>
</html>
