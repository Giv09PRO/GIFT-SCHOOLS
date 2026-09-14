


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
        /* Ensure Roboto font files are in public/fonts/ or adjust path */
        /* DomPDF needs absolute paths or paths relative to chroot (public_path by default) */
        @font-face {
            font-family: 'Roboto';
            font-style: normal;
            font-weight: 400;
            src: url("<?php echo e(public_path('fonts/Roboto-Regular.ttf')); ?>") format('truetype');
        }
        @font-face {
            font-family: 'Roboto';
            font-style: normal;
            font-weight: 500; /* Medium */
            src: url("<?php echo e(public_path('fonts/Roboto-Medium.ttf')); ?>") format('truetype');
        }
        @font-face {
            font-family: 'Roboto';
            font-style: normal;
            font-weight: 700; /* Bold */
            src: url("<?php echo e(public_path('fonts/Roboto-Bold.ttf')); ?>") format('truetype');
        }

        /* ---=== Root Variables (CSS Custom Properties) ===--- */
        :root {
            --font-main: 'Roboto', Helvetica, Arial, sans-serif;
            --color-primary: #007bff; /* Bootstrap Primary Blue */
            --color-secondary: #6c757d; /* Bootstrap Secondary Grey */
            --color-text: #212529; /* Bootstrap Dark Grey */
            --color-text-muted: #6c757d;
            --color-border: #dee2e6; /* Bootstrap Border Grey */
            --color-table-header-bg: #f8f9fa; /* Bootstrap Light Grey */
            --spacing-base: 8pt;
            --spacing-small: 4pt;
            --spacing-medium: 12pt;
            --spacing-large: 16pt;
        }

        /* ---=== Page Setup ===--- */
        @page {
            size: A4 portrait;
            margin: 25pt 30pt; /* top/bottom left/right */
        }

        /* ---=== Basic Body & Container ===--- */
        body {
            font-family: var(--font-main), sans-serif;
            font-size: 9.5pt; /* Slightly smaller for more content */
            line-height: 1.4;
            color: var(--color-text);
            background-color: #fff;
        }
        .container {
            width: 100%;
        }

        /* ---=== Typography ===--- */
        h1 { font-size: 20pt; font-weight: 500; margin-bottom: var(--spacing-medium); color: var(--color-primary); }
        h2 { font-size: 16pt; font-weight: 500; margin-bottom: var(--spacing-small); }
        h3 { font-size: 13pt; font-weight: 500; margin-bottom: var(--spacing-small); color: var(--color-primary); }
        p { margin: 0 0 var(--spacing-small) 0; }
        strong { font-weight: 700; } /* Bold */
        small { font-size: 8pt; color: var(--color-text-muted); }

        /* ---=== Header Section ===--- */
        .statement-header {
            margin-bottom: var(--spacing-large);
            padding-bottom: var(--spacing-medium);
            border-bottom: 1.5pt solid var(--color-primary);
            overflow: hidden; /* clearfix */
        }
        .statement-header .logo {
            max-height: 50pt; /* Adjust as needed */
            max-width: 180pt; /* Adjust as needed */
            display: block;
            float: left;
            margin-right: var(--spacing-medium);
        }
        .statement-header .logo-placeholder { /* Fallback if logo image fails */
            display: block;
            float: left;
            font-size: 16pt;
            font-weight: 700;
            color: var(--color-primary);
            line-height: 50pt; /* Align with logo height */
            margin-right: var(--spacing-medium);
        }
        .statement-header .statement-title {
            float: right;
            margin: 0;
            line-height: 50pt; /* Align with logo height */
            color: var(--color-primary);
            font-size: 20pt;
            font-weight: 500;
            text-align: right;
        }

        /* ---=== Info Section (School/Student/Statement Details) ===--- */
        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: var(--spacing-large);
            table-layout: fixed;
        }
        .info-grid td {
            vertical-align: top;
            padding: var(--spacing-small) 0;
        }
        .info-grid .left-column { width: 55%; padding-right: var(--spacing-medium); }
        .info-grid .right-column { width: 45%; text-align: left; }

        .info-block { margin-bottom: var(--spacing-medium); }
        .info-block h3 { font-size: 11pt; margin-bottom: var(--spacing-small); border-bottom: 1px solid var(--color-border); padding-bottom: var(--spacing-small); }
        .info-block p { margin-bottom: var(--spacing-small); line-height: 1.3; }
        .info-label { font-weight: 500; color: var(--color-text-muted); display: inline-block; min-width: 80pt; }

        /* ---=== Transactions Table ===--- */
        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: var(--spacing-large);
            table-layout: auto; /* Allow columns to size based on content */
        }
        .transactions-table th,
        .transactions-table td {
            border: 1pt solid var(--color-border);
            padding: var(--spacing-small) var(--spacing-base);
            text-align: left;
            vertical-align: top;
            word-wrap: break-word;
        }
        .transactions-table thead th {
            background-color: var(--color-table-header-bg);
            color: var(--color-primary);
            font-weight: 700; /* Bold */
            font-size: 9pt;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
        }
        .transactions-table tbody tr { page-break-inside: avoid; }
        .transactions-table .text-right { text-align: right !important; }
        .transactions-table .text-center { text-align: center !important; }

        /* ---=== Summary Section ===--- */
        .summary-wrapper {
            overflow: hidden; /* clearfix */
            margin-top: var(--spacing-medium);
            padding-top: var(--spacing-medium);
            border-top: 1.5pt solid var(--color-primary);
        }
        .summary-details {
            float: right;
            width: 50%;
            max-width: 250pt;
        }
        .summary-details table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-details td {
            padding: var(--spacing-small) 0;
            font-size: 10pt;
        }
        .summary-details .label {
            text-align: right;
            padding-right: var(--spacing-base);
            color: var(--color-text-muted);
            font-weight: 500;
        }
        .summary-details .value {
            text-align: right;
            font-weight: 500;
        }
        .summary-details .total .label,
        .summary-details .total .value {
            font-weight: 700; /* Bold */
            border-top: 1pt solid var(--color-text-muted);
            padding-top: var(--spacing-small);
            margin-top: var(--spacing-small);
        }
        .summary-details .total .value { color: var(--color-primary); font-size: 11pt; }

        /* ---=== Footer ===--- */
        .statement-footer {
            margin-top: var(--spacing-large);
            padding-top: var(--spacing-medium);
            border-top: 1pt solid var(--color-border);
            text-align: center;
            color: var(--color-text-muted);
            font-size: 8pt;
        }
        .statement-footer p { margin-bottom: var(--spacing-small); }

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
                    <h3><?php echo e($pdfSchoolDisplayName); ?></h3>
                    <?php if($pdfSchoolEmail): ?><p><?php echo e($pdfSchoolEmail); ?></p><?php endif; ?>
                    <?php if($pdfSchoolAddress): ?><p><span class="info-label">Email:</span> <?php echo e($pdfSchoolAddress); ?></p><?php endif; ?>
                    <?php if($pdfSchoolPhone): ?><p><span class="info-label">Phone:</span> <?php echo e($pdfSchoolPhone); ?></p><?php endif; ?>
                    
                </div>
            </td>
            <td class="right-column">
                <div class="info-block">
                    <h3>Bill To:</h3>
                    <p><span class="info-label">Student:</span> <strong><?php echo e($pdfStudentFullName); ?></strong></p>
                    <p><span class="info-label">ID:</span> <?php echo e($student->username ?? $student->id); ?></p>
                    
                </div>
                <div class="info-block">
                    <h3>Statement Details:</h3>
                    <p><span class="info-label">Period:</span> <?php echo e($pdfStatementYear); ?></p>
                    <p><span class="info-label">Issued:</span> <?php echo e(($issueDate ?? now())->format('M d, Y')); ?></p>
                </div>
            </td>
        </tr>
    </table>

    <h3 class="section-title" style="margin-bottom: var(--spacing-base); color: var(--color-text); font-size: 13pt;">Transactions for <?php echo e($pdfStatementYear); ?></h3>
    <table class="transactions-table">
        <thead>
            <tr>
                
                <?php $__currentLoopData = $heads ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $headConfig): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $label = is_array($headConfig) ? ($headConfig['label'] ?? 'N/A') : $headConfig;
                        $width = is_array($headConfig) && isset($headConfig['width']) ? "width: {$headConfig['width']}%;" : '';
                        $class = is_array($headConfig) && isset($headConfig['class']) ? $headConfig['class'] : '';
                    ?>
                    <th style="<?php echo e($width); ?>" class="<?php echo e($class); ?>"><?php echo e($label); ?></th>
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
                    <td>
                        <?php echo $transaction['description_plain']; ?> 
                        
                    </td>
                    <td class="text-right"><?php echo e($transaction['charge'] ?? ''); ?></td>
                    <td class="text-right"><?php echo e($transaction['credit'] ?? ''); ?></td>
                    <td class="text-right"><?php echo e($transaction['running_balance'] ?? ''); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="<?php echo e(count($heads ?? [1])); ?>" class="text-center" style="padding: var(--spacing-medium) 0;">
                        No transactions found for this period.
                    </td>
                </tr>
            <?php endif; ?>
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
        $font = $fontMetrics->getFont("Roboto", "normal"); // Ensure this font is loaded
        $width = $fontMetrics->getTextWidth($text, $font, $size);
        $x = $pdf->get_width() - $width - 30; // Right align with margin
        $y = $pdf->get_height() - 20; // Position near bottom
        $pdf->page_text($x, $y, $text, $font, $size);
    }
</script>

</body>
</html><?php /**PATH /home/giftscho/school-system/resources/views/pdf/finance/statement.blade.php ENDPATH**/ ?>