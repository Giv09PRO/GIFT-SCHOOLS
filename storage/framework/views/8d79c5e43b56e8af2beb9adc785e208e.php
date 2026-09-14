


<?php
    // Prepare variables for PDF display
    $pdfStudentFullName = $student->full_name ?? ($student->first_name . ' ' . $student->last_name);
    $pdfStatementYear = $syear ?? 'N/A';
    $pdfSchoolDisplayName = $school->display_name ?? ($school->name ?? $school->title ?? 'School Name');
    $pdfSchoolAddress = $school->address ?? null;
    $pdfSchoolPhone = $school->phone ?? null;
    $pdfSchoolEmail = $school->www_address ?? null; // Email is in www_address

    // These keys come directly from the $statementData array passed by FinancialStatementService
    // FinancialStatementService ensures 'totalFeesAmount' holds the net fees for the period.
    $pdfOpeningBalance = $openingBalance ?? 0.00;
    $pdfClosingBalance = $closingBalance ?? 0.00;
    $pdfNetFeesChargedThisPeriod = $totalFeesAmount ?? 0.00; // This is netFeesForYear as set by FinancialStatementService
    $pdfPaymentsAppliedToPeriodFees = $totalAmountAppliedToFeesThisYear ?? 0.00;
    $pdfTotalPaymentsMadeThisPeriod = $totalPaymentsMadeThisYear ?? 0.00;

    // The $transactions_for_pdf variable should be passed from FinancialStatementService
    // It contains items with 'date', 'description_plain', 'charge', 'credit', 'running_balance'
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Financial Statement - <?php echo e($pdfStudentFullName); ?> - <?php echo e($pdfStatementYear); ?></title>

    <style>
        /* ---=== Font Face (Example - Adjust Path if needed) ===--- */
        @font-face {
            font-family: 'Roboto';
            font-style: normal;
            font-weight: 400;
            src: url("<?php echo e(public_path('fonts/Roboto-Regular.ttf')); ?>") format('truetype');
        }
        @font-face {
            font-family: 'Roboto';
            font-style: normal;
            font-weight: 500;
            src: url("<?php echo e(public_path('fonts/Roboto-Medium.ttf')); ?>") format('truetype');
        }
        @font-face {
            font-family: 'Roboto';
            font-style: normal;
            font-weight: 700;
            src: url("<?php echo e(public_path('fonts/Roboto-Bold.ttf')); ?>") format('truetype');
        }

        :root {
            --font-main: 'Roboto', Helvetica, Arial, sans-serif;
            --color-primary: #007bff;
            --color-secondary: #6c757d;
            --color-text: #212529;
            --color-text-muted: #6c757d;
            --color-border: #dee2e6;
            --color-table-header-bg: #f8f9fa;
            --spacing-base: 8pt;
            --spacing-small: 4pt;
            --spacing-medium: 12pt;
            --spacing-large: 16pt;
        }

        @page {
            size: A4 portrait;
            margin: 25pt 30pt;
        }

        body {
            font-family: var(--font-main), Arial, Helvetica, sans-serif;
            font-size: 9.5pt;
            line-height: 1.4;
            color: var(--color-text);
            background-color: #fff;
        }
        .container {
            width: 100%;
            /* font-size: 12pt; Removed as body sets base, specific elements override */
            color: #222;
            /* margin: 20px; Removed as @page handles margins */
            font-family: var(--font-main), Arial, sans-serif;
        }
        h1 { /* This was for .statement-title, now modified directly */
            font-size: 20pt; /* Default H1, but .statement-title overrides */
            font-weight: 500;
            margin-bottom: var(--spacing-medium);
            color: var(--color-primary);
        }
        h2 {
            font-size: 16pt;
            font-weight: 500;
            margin-bottom: var(--spacing-small);
        }
        h3 { /* Default H3, overridden by .info-block h3 and .section-title */
            font-size: 13pt;
            font-weight: 700;
            margin-bottom: var(--spacing-small);
            color: #222;
        }
        p {
            margin: 0 0 var(--spacing-small) 0;
            font-size: 11pt;
            color: #444;
        }
        strong { font-weight: 700; }
        small { font-size: 8pt; color: var(--color-text-muted); }

        .statement-header {
            display: flex; /* Using flex for better alignment might be an option if float becomes problematic */
            align-items: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            overflow: hidden; /* For float containment */
        }
        .logo {
            max-height: 60px;
            max-width: 180pt;
            display: block;
            float: left;
            margin-right: 15px;
        }
        .logo-placeholder {
            display: block;
            float: left;
            font-size: 20pt; /* This is for the placeholder if logo is absent */
            font-weight: 700;
            color: #666;
            line-height: 50pt; /* Aligns with original logo height expectation */
            margin-right: 15px;
        }
        .statement-title { /* Financial Statement title */
            font-size: 11pt; /* MODIFIED: Was 24pt, now 11pt (original school H3 size) */
            font-weight: bold;
            color: #111;
            margin: 0;
            float: right;
            line-height: 1.3; /* MODIFIED: Adjusted for new font size */
            text-align: right;
            /* If vertical alignment is off, might need padding-top, e.g., padding-top: 5pt; */
        }

        .info-grid {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed; /* Ensures column widths are respected */
        }
        .info-grid td {
            vertical-align: top;
            padding: var(--spacing-small) 0;
        }
        .info-grid .left-column, .left-column {
            width: 55%;
            padding-right: var(--spacing-medium);
            vertical-align: top;
            /* padding: 5px 15px 15px 0;  Simplified, using var */
        }
        .info-grid .right-column, .right-column {
            width: 45%;
            text-align: left; /* Content within will align left */
            vertical-align: top;
            /* padding: 5px 15px 15px 0; Simplified, using var */
        }
        .info-block {
            margin-bottom: var(--spacing-medium);
        }
        /* Styles for H3s like "Bill To:", "Statement Details:" */
        .info-block h3 {
            font-size: 11pt; /* Default for these subheadings */
            margin-top: 0;
            margin-bottom: 0.4rem;
            font-weight: 700;
            color: #222;
            border-bottom: 1px solid var(--color-border);
            padding-bottom: var(--spacing-small);
        }
        /* Specific style for the School Display Name */
        .info-block h3.school-display-name {
            font-size: 16pt; /* MODIFIED: Was 24pt, now 16pt */
            font-weight: 700; /* Or bold, adjust as preferred */
            color: #111; /* Darker color for main title */
            border-bottom: none; /* Remove border for this larger title */
            padding-bottom: 0;
            margin-bottom: 0.6rem; /* Adjusted margin for larger font */
        }
        .info-block p {
            margin: 0.2rem 0 var(--spacing-small) 0;
            font-size: 11pt;
            color: #444;
            line-height: 1.3;
        }
        .info-label {
            font-weight: 600;
            color: #333;
            display: inline-block;
            min-width: 80pt; /* Ensures alignment */
        }
        .section-title { /* "Transactions for..." */
            margin-bottom: 0.8rem; /* MODIFIED: Slightly reduced from 1rem for tighter layout */
            font-weight: 700;
            font-size: 14pt;
            color: #111;
            border-bottom: 1px solid #ccc;
            padding-bottom: 4px;
        }

        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0.5rem; /* MODIFIED: Slightly reduced from 2rem */
            font-size: 11pt; /* Base font size for table content, including money */
            table-layout: auto; /* Allows column widths to adjust, use 'fixed' if specific widths in $heads are critical */
        }
        .transactions-table thead tr {
            background-color: #f5f5f5;
        }
        .transactions-table th,
        .transactions-table td {
            border: 1px solid #ddd;
            padding: 3px 4px; /* MODIFIED: Reduced from 8px 10px */
            text-align: left;
            vertical-align: middle;
            word-wrap: break-word; /* Important for description column */
            line-height: 0.8; /* MODIFIED: Reduced for density */
        }
        .transactions-table thead th {
            background-color: var(--color-table-header-bg);
            color: var(--color-primary);
            font-weight: 700;
            font-size: 9pt; /* Header font size */
            text-transform: uppercase;
            letter-spacing: 0.5pt;
            /* padding: 5px 6px; Already covered by the general th, td rule but explicit doesn't hurt */
        }
        .transactions-table tbody tr { page-break-inside: avoid; }
        .transactions-table .text-right {
            text-align: right !important;
            font-variant-numeric: tabular-nums; /* Keeps numbers aligned */
        }
        .transactions-table .text-center { text-align: center !important; }
        .transactions-table tbody tr:nth-child(odd) {
            background-color: #fafafa;
        }
        .transactions-table tbody tr.total-row {
            font-weight: 700;
            background-color: #e0e0e0;
        }

        .summary-wrapper {
            margin-top: var(--spacing-medium);
            margin-bottom: 1.5rem; /* MODIFIED: Slightly reduced */
            clear: both;
            overflow: hidden; /* For float containment */
            padding-top: var(--spacing-medium);
            border-top: 1.5pt solid var(--color-primary);
            page-break-inside: avoid !important; /* MODIFIED: Added to prevent breaking across pages */
        }
        .summary-details {
            float: right;
            width: 50%; /* Or adjust as needed */
            max-width: 260pt; /* Slight increase if needed due to content */
            page-break-inside: avoid !important; /* MODIFIED: Added for good measure, though parent should handle */
        }
        .summary-details table {
            width: 100%; /* Changed from fixed 320px to be responsive to its container */
            border-collapse: collapse;
            font-size: 11pt;
            page-break-inside: avoid !important; /* MODIFIED: Added for good measure */
        }
        .summary-details td.label {
            font-weight: 600;
            padding: 5px 6px; /* MODIFIED: Reduced padding */
            color: #222;
            text-align: right;
            padding-right: var(--spacing-base);
        }
        .summary-details td.value {
            text-align: right;
            padding: 5px 6px; /* MODIFIED: Reduced padding */
            font-variant-numeric: tabular-nums;
            color: #111;
            font-weight: 500;
        }
        .summary-details .total .label,
        .summary-details .total .value,
        .summary-details tr.total td {
            font-weight: 700;
            border-top: 2px solid #333;
            padding-top: var(--spacing-small);
            margin-top: var(--spacing-small);
            color: var(--color-primary);
            font-size: 11pt; /* Ensure total font size matches */
        }

        .statement-footer {
            font-size: 10.5pt;
            color: #444;
            border-top: 1px solid #ccc;
            padding-top: 10px; /* MODIFIED: Slightly reduced */
            margin-top: var(--spacing-medium); /* MODIFIED: Slightly reduced */
            text-align: center;
            page-break-inside: avoid !important; /* MODIFIED: Attempt to keep footer on same page as summary if possible */
        }
        .statement-footer p {
            margin: 4px 0 var(--spacing-small) 0; /* MODIFIED: Reduced margin */
        }

        .clearfix::after { content: ""; clear: both; display: table; }
    </style>
</head>
<body>
    <div class="container">

        <div class="statement-header clearfix">
            <?php if(isset($site_logo_path) && $site_logo_path): ?>
                <img src="<?php echo e($site_logo_path); ?>" alt="School Logo" class="logo">
            <?php else: ?>
                <span class="logo-placeholder"><?php echo e($pdfSchoolDisplayName); ?></span>
            <?php endif; ?>
            <h1 class="statement-title">Financial Statement</h1>
        </div>

        <table class="info-grid">
            <tr>
                <td class="left-column">
                    <div class="info-block">
                        
                        <h3 class="school-display-name"><?php echo e($pdfSchoolDisplayName); ?></h3>
                        <?php if($pdfSchoolEmail): ?><p><span class="info-label">Email:</span> <?php echo e($pdfSchoolEmail); ?></p><?php endif; ?>
                        <?php if($pdfSchoolAddress): ?><p><span class="info-label">Address:</span> <?php echo e($pdfSchoolAddress); ?></p><?php endif; ?>
                        <?php if($pdfSchoolPhone): ?><p><span class="info-label">Phone:</span> <?php echo e($pdfSchoolPhone); ?></p><?php endif; ?>
                    </div>
                </td>
                <td class="right-column">
                    <div class="info-block">
                        <h3>Bill To:</h3>
                        <p><span class="info-label">Student: <strong><?php echo e($pdfStudentFullName); ?></strong></p>
                        <p><span class="info-label">ID: <?php echo e($student->username ?? $student->id); ?></p>
                        
                    </div>
                    <div class="info-block" style="margin-top: 1rem;">
                        <h3>Statement Details:</h3>
                        <p><span class="info-label">Period:</span> <?php echo e($pdfStatementYear); ?></p>
                        <p><span class="info-label">Issued:</span> <?php echo e(($issueDate ?? now())->format('M d, Y')); ?></p>
                    </div>
                </td>
            </tr>
        </table>

        <h3 class="section-title">Transactions for <?php echo e($pdfStatementYear); ?></h3>
        <table class="transactions-table">
            <thead>
                <tr>
                    <?php $__currentLoopData = $heads ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $headConfig): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $label = is_array($headConfig) ? ($headConfig['label'] ?? 'N/A') : $headConfig;
                            $widthStyle = is_array($headConfig) && isset($headConfig['width']) ? "width: {$headConfig['width']}%;" : '';
                            $class = is_array($headConfig) && isset($headConfig['class']) ? $headConfig['class'] : '';
                        ?>
                        <th style="<?php echo e($widthStyle); ?>" class="<?php echo e($class); ?>"><?php echo e($label); ?></th>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td></td> 
                    <td><strong>Opening Balance</strong></td> 
                    <td class="text-right"></td> 
                    <td class="text-right"></td> 
                    <td class="text-right"><strong><?php echo e(number_format($pdfOpeningBalance, 2)); ?></strong></td> 
                </tr>

                <?php $__empty_1 = true; $__currentLoopData = $transactions_for_pdf ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($transaction['date'] ?? 'N/A'); ?></td>
                        <td><?php echo $transaction['description_plain']; ?></td> 
                        <td class="text-right"><?php echo e($transaction['charge'] ?? ''); ?></td>
                        <td class="text-right"><?php echo e($transaction['credit'] ?? ''); ?></td>
                        <td class="text-right"><?php echo e($transaction['running_balance'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="<?php echo e(count($heads ?? [1])); ?>" class="text-center" style="padding: 20px 0;">
                            No transactions found for this period.
                        </td>
                    </tr>
                <?php endif; ?>

                
                <tr class="total-row">
                    <td colspan="2" class="text-right">Totals:</td>
                    <td class="text-right">
                        <?php echo e(number_format(
                            array_sum(array_map(function($value) {
                                return is_numeric(str_replace(',', '', $value)) ? (float) str_replace(',', '', $value) : 0; // Handle potential commas in numbers
                            }, array_column($transactions_for_pdf ?? [], 'charge'))), 2)); ?>

                    </td>
                    <td class="text-right">
                        <?php echo e(number_format(
                            array_sum(array_map(function($value) {
                                return is_numeric(str_replace(',', '', $value)) ? (float) str_replace(',', '', $value) : 0; // Handle potential commas in numbers
                            }, array_column($transactions_for_pdf ?? [], 'credit'))), 2)); ?>

                    </td>
                    <td class="text-right">
                    <?php echo e(number_format($pdfClosingBalance, 2)); ?>

                    </td>
                </tr>
            </tbody>
        </table>

        <div class="summary-wrapper clearfix">
            <div class="summary-details">
                <table>
                    <tr>
                        <td class="label">Net Fees Charged (<?php echo e($pdfStatementYear); ?>):</td>
                        <td class="value"><?php echo e(number_format($pdfNetFeesChargedThisPeriod, 2)); ?></td>
                    </tr>
                    <tr>
                        <td class="label">Total Payments Received (<?php echo e($pdfStatementYear); ?>):</td>
                        <td class="value"><?php echo e(number_format($pdfTotalPaymentsMadeThisPeriod, 2)); ?></td>
                    </tr>
                    <tr>
                        <td class="label">Applied to <?php echo e($pdfStatementYear); ?> Fees:</td>
                        <td class="value"><?php echo e(number_format($pdfPaymentsAppliedToPeriodFees, 2)); ?></td>
                    </tr>
                    <tr class="total">
                        <td class="label">Opening Balance:</td>
                        <td class="value"><?php echo e(number_format($pdfOpeningBalance, 2)); ?></td>
                    </tr>
                    <tr class="total">
                        <td class="label">Closing Balance:</td>
                        <td class="value"><?php echo e(number_format($pdfClosingBalance, 2)); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="statement-footer">
            <p>Thank you for your prompt attention to your account.</p>
            <p>If you have any questions concerning this statement, please contact the school office.</p>
            <?php if($pdfSchoolPhone): ?> <p>Phone: <?php echo e($pdfSchoolPhone); ?></p> <?php endif; ?>
        </div>

    </div>

    
    <script type="text/php">
        if (isset($pdf)) {
            $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
            $size = 8; // Font size for page number
            $font = null; // Let dompdf use the default or a specified one
            try {
                $font = $fontMetrics->getFont("Roboto", "normal"); // Try to load Roboto
            } catch (\Exception $e) {
                // Fallback if Roboto is not found or path is incorrect
            }
            $width = $fontMetrics->getTextWidth($text, $font, $size);
            $x = $pdf->get_width() - $width - 30; // Right align with 30pt margin from edge
            $y = $pdf->get_height() - 20; // Position 20pt from bottom
            $pdf->page_text($x, $y, $text, $font, $size);
        }
    </script>

</body>
</html>
<?php /**PATH C:\laragon\www\GIFT\resources\views/pdf/finance/statement.blade.php ENDPATH**/ ?>