@extends('layouts.app')

@section('title', 'Staff Dashboard')

@section('content')
    <div class="content">
        <div class="container-fluid">

            {{-- Alerts --}}
            @include('layouts.partials.alerts')

            {{-- Dashboard Header --}}
            <div class="card card-outline card-secondary mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <div>
                                @if($currentUser ?? null)
                                    <h4 class="mb-2">
                                        👋 Welcome back, <strong class="text-primary">{{ e($currentUser->first_name) }} {{ e($currentUser->last_name) }}!</strong>
                                    </h4>
                                    <p class="text-muted mb-0">
                                        You’re logged in as
                                        <strong>
                                            {!! ($canViewAll ?? false) ? 'Administrator (All Schools)' : (($currentUser->school ?? null) ? e($currentUser->school->title) : 'Staff') !!}
                                        </strong>.
                                    </p>
                                @else
                                    <h4 class="mb-2">👋 Welcome!</h4>
                                    <p class="text-muted">Please log in to view your school dashboard.</p>
                                @endif
                            </div>
                        </div>
                        
                        <div>
                            <span class="badge badge-primary p-2">Academic Year: {{ $currentYear ?? 'N/A' }}</span>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Row 1: Key Stats Boxes (Existing) --}}
            <div class="row">
                @can('view students')
                    <div class="col-lg-3 col-6">
                        <x-adminlte-small-box title="{{ $stats['student_count'] ?? 0 }}" text="Active Students"
                                            icon="fas fa-user-graduate text-primary"
                                            theme="gradient-primary" url="{{ route('staff.students.index') }}"
                                            url-text="View Students"/>
                    </div>
                @endcan
                @can('view staff')
                    <div class="col-lg-3 col-6">
                        <x-adminlte-small-box title="{{ $stats['staff_count'] ?? 0 }}" text="Active Staff"
                                            icon="fas fa-users text-info"
                                            theme="gradient-info" url="{{ route('staff.manage.staff.index') }}"
                                            url-text="View Staff"/>
                    </div>
                @endcan
                @can('view parents')
                    <div class="col-lg-3 col-6">
                        <x-adminlte-small-box title="{{ $stats['parent_count'] ?? 0 }}" text="Parent Accounts"
                                            icon="fas fa-user-friends text-warning"
                                            theme="gradient-warning" url="{{ route('staff.manage.parents.index') }}"
                                            url-text="View Parents"/>
                    </div>
                @endcan
                @can('view grades')
                    <div class="col-lg-3 col-6">
                        <x-adminlte-small-box title="{{ $stats['grade_count'] ?? 0 }}" text="Classes"
                                            icon="fas fa-layer-group text-secondary"
                                            theme="gradient-secondary" url="{{ route('staff.grades.index') }}"
                                            url-text="View Grades"/>
                    </div>
                @endcan
            </div>


            {{-- Row 2: Quick Actions (Existing - with collapse button) --}}
            <div class="row">
                <div class="col-12">
                    <div class="card card-outline card-secondary">
                        <div class="card-header">
                            <h3 class="card-title">Quick Actions</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i
                                        class="fas fa-minus"></i></button>
                            </div>
                        </div>
                        <div class="card-body text-center"> {{-- Kept as text-center per latest artifact --}}
                            @can('create', App\Models\Student::class)
                                <a href="{{ route('staff.students.create') }}" class="btn btn-app bg-success"><i
                                        class="fas fa-user-plus"></i> Add Student</a>
                            @endcan
                            @can('create', App\Models\Staff::class)
                                <a href="{{ route('staff.manage.staff.create') }}" class="btn btn-app bg-info"><i
                                        class="fas fa-user-tie"></i> Add Staff</a>
                            @endcan
                            @can('create', App\Models\Parents::class)
                                <a href="{{ route('staff.manage.parents.create') }}" class="btn btn-app bg-warning"><i
                                        class="fas fa-address-book"></i> Add Parent</a>
                            @endcan
                            @can('manage finances')
                                <a href="{{ route('staff.fees.create') }}" class="btn btn-app bg-danger"><i
                                        class="fas fa-receipt"></i> Add Fee</a>
                            @endcan
                            @can('create', App\Models\GradeLevel::class)
                                <a href="{{ route('staff.grades.create') }}" class="btn btn-app bg-secondary"><i
                                        class="fas fa-chalkboard-teacher"></i> Add Grade</a>
                            @endcan
                            @can('create', App\Models\School::class)
                                <a href="{{ route('staff.schools.create') }}" class="btn btn-app bg-purple"><i
                                        class="fas fa-school"></i> Add School</a>
                            @endcan
                            @can('view students')
                                <a href="{{ route('staff.students.index') }}" class="btn btn-app bg-primary"><i
                                        class="fas fa-user-graduate"></i> View Students</a>
                            @endcan
                            @can('view staff')
                                <a href="{{ route('staff.manage.staff.index') }}" class="btn btn-app bg-teal"><i
                                        class="fas fa-users"></i> View Staff</a>
                            @endcan
                            @can('view parents')
                                <a href="{{ route('staff.manage.parents.index') }}" class="btn btn-app bg-orange"><i
                                        class="fas fa-user-friends"></i> View Parents</a>
                            @endcan
                            @can('view grades')
                                <a href="{{ route('staff.grades.index') }}" class="btn btn-app bg-indigo"><i
                                        class="fas fa-layer-group"></i> View Grades</a>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>

            {{-- Row 3: Financial Overview --}}
            @can('view finances')
            <div class="row">
                <div class="col-12"><h4 class="text-muted my-3">💰 Financial Deep Dive</h4></div>
                <div class="col-md-4"> 
                    <div class="card card-widget widget-user-2 shadow-sm mb-3 card-outline card-danger"> 
                        <div class="card-header"><h3 class="card-title">Finances</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i
                                        class="fas fa-minus"></i></button>
                            </div>
                        </div>
                    
                        <div class="card-body" style="height: 300px;">
                            <div>
                                <h5>Current Year Summary</h5>
                            </div>
                            <ul class="nav flex-column">
                                <li class="nav-item"><span class="nav-link">Fees Due (Active) <span
                                            class="float-right badge bg-danger">$ {{ number_format($stats['total_fees_due'] ?? 0, 2) }}</span></span>
                                </li>
                                <li class="nav-item"><span class="nav-link">Payments Received <span
                                            class="float-right badge bg-success">$ {{ number_format($stats['total_payments'] ?? 0, 2) }}</span></span>
                                </li>
                                <li class="nav-item"><span class="nav-link">Total Outstanding <span
                                            class="float-right badge bg-warning">$ {{ number_format($stats['total_outstanding'] ?? 0, 2) }}</span></span>
                                </li>
                                <li class="nav-item"><a href="{{ route('staff.fees.index') }}" class="nav-link">View
                                        All Fees <i class="fas fa-arrow-circle-right float-right mt-1"></i></a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                {{-- Fee Status Chart --}}
                <div class="col-md-4"> {{-- Adjusted to col-md-4 --}}
                    <div class="card card-outline card-warning">
                        <div class="card-header"><h3 class="card-title">Fee Status Overview</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i
                                        class="fas fa-minus"></i></button>
                            </div>
                        </div>
                        <div class="card-body" style="height: 300px;">
                            <canvas id="feeStatusChart"></canvas>
                        </div>
                    </div>
                </div>
                {{-- Outstanding Balances by Grade --}}
                <div class="col-md-4"> {{-- Adjusted to col-md-4 --}}
                    <div class="card card-outline card-warning">
                        <div class="card-header"><h3 class="card-title">Outstanding Balances by Grade</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i
                                        class="fas fa-minus"></i></button>
                            </div>
                        </div>
                        <div class="card-body" style="height: 300px;">
                            <canvas id="outstandingByGradeChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            {{-- New row for additional financial charts --}}
            <div class="row">
                 {{-- Revenue by Fee Type --}}
                 <div class="col-md-4">
                    <div class="card card-outline card-success">
                        <div class="card-header"><h3 class="card-title">Revenue by Fee Type</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                            </div>
                        </div>
                        <div class="card-body" style="height: 300px;">
                            <canvas id="revenueByFeeTypeChart"></canvas>
                        </div>
                    </div>
                </div>
                {{-- Payment Methods --}}
                <div class="col-md-4">
                    <div class="card card-outline card-info">
                        <div class="card-header"><h3 class="card-title">Payment Methods</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                            </div>
                        </div>
                        <div class="card-body" style="height: 300px;">
                            <canvas id="paymentMethodsChart"></canvas>
                        </div>
                    </div>
                </div>
                {{-- Discounts & Waivers --}}
                 <div class="col-md-4">
                    <div class="card card-outline card-purple">
                        <div class="card-header"><h3 class="card-title">Discounts & Waivers</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                            </div>
                        </div>
                        <div class="card-body" style="height: 300px;">
                            <canvas id="discountsWaiversChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            @endcan


            {{-- SECTION: Student Insights --}}
            @can('view students')
            <div class="row">
                <div class="col-12"><h4 class="text-muted my-3">📊 Student Insights</h4></div>

                {{-- Student Gender Distribution --}}
                <div class="col-md-4"> {{-- Adjusted for 3 charts per row --}}
                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h3 class="card-title">Student Gender Distribution</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body" style="height: 300px;">
                            <canvas id="studentGenderDistributionChart"></canvas>
                        </div>
                    </div>
                </div>

                {{-- Student Enrollment Trend --}}
                <div class="col-md-4"> {{-- Adjusted for 3 charts per row --}}
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Enrollment Trend (Monthly)</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                            </div>
                        </div>
                        <div class="card-body" style="height: 300px;">
                            <canvas id="studentEnrollmentTrendChart"></canvas> {{-- Canvas ID from JS --}}
                        </div>
                    </div>
                </div>

                {{-- Student Status Overview --}}
                <div class="col-md-4"> {{-- Adjusted for 3 charts per row --}}
                    <div class="card card-outline card-secondary">
                        <div class="card-header">
                            <h3 class="card-title">Student Status Overview</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                            </div>
                        </div>
                        <div class="card-body" style="height: 300px;">
                            <canvas id="studentStatusOverviewChart"></canvas> {{-- Canvas ID from JS --}}
                        </div>
                    </div>
                </div>
            </div>

            <div class="row"> {{-- New row for remaining student charts --}}
                {{-- Active Students by Grade Level (Original Chart) --}}
                <div class="col-md-{{ ($canViewAll ?? false) ? '6' : '12' }}"> {{-- Takes full width if not admin, half if admin --}}
                    <div class="card card-outline card-primary">
                        <div class="card-header"><h3 class="card-title">Active Students by Grade Level</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i
                                        class="fas fa-minus"></i></button>
                            </div>
                        </div>
                        <div class="card-body"
                             style="height: {{ ($currentUser->can('view finances') ?? false) ? '350px' : '300px' }};"> {{-- Adjusted height slightly --}}
                            <canvas id="studentsByGradeChart"></canvas>
                        </div>
                    </div>
                </div>

                {{-- Students per School (if applicable) --}}
                @can('view all schools data')
                    <div class="col-md-6">
                        <div class="card card-outline card-info">
                            <div class="card-header">
                                <h3 class="card-title">Students per School</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body" style="height: 350px;"> {{-- Adjusted height slightly --}}
                                <canvas id="studentsPerSchoolChart"></canvas>
                            </div>
                        </div>
                    </div>
                @endcan
            </div>
            @endcan


            {{-- SECTION: Parent Overview --}}
            @can('view parents')
            <div class="row">
                <div class="col-12"><h4 class="text-muted my-3">👥 Parent Overview</h4></div>
                <div class="col-md-12"> {{-- Full width for this chart --}}
                    <div class="card card-outline card-orange">
                        <div class="card-header"><h3 class="card-title">Parent Account Activation Status</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i
                                        class="fas fa-minus"></i></button>
                            </div>
                        </div>
                        <div class="card-body" style="height: 300px;">
                            <canvas id="parentAccountStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            @endcan

            {{-- SECTION: Academic Performance --}}
            @can('view results')
            <div class="row">
                <div class="col-12"><h4 class="text-muted my-3">🎓 Academic Performance</h4></div>
                <div class="col-md-6">
                    <div class="card card-outline card-teal">
                        <div class="card-header"><h3 class="card-title">Average Scores by Subject (Recent Exam)</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i
                                        class="fas fa-minus"></i></button>
                            </div>
                        </div>
                        <div class="card-body" style="height: 300px;">
                            <canvas id="averageScoresBySubjectChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card card-outline card-teal">
                        <div class="card-header"><h3 class="card-title">Pass/Fail Rates by Grade (Recent Exam)</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i
                                        class="fas fa-minus"></i></button>
                            </div>
                        </div>
                        <div class="card-body" style="height: 300px;">
                            <canvas id="passFailByGradeChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            @endcan

            {{-- SECTION: School Operations & Admin --}}
            <div class="row">
                <div class="col-12"><h4 class="text-muted my-3">⚙️ School Operations & Admin</h4></div>
                @can('manage system settings')
                    <div class="col-md-6">
                        <div class="card card-outline card-secondary">
                            <div class="card-header"><h3 class="card-title">System Log Activity (Recent
                                    Errors/Warnings)</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i
                                            class="fas fa-minus"></i></button>
                                </div>
                            </div>
                            <div class="card-body" style="height: 300px; overflow-y: auto;">
                                <p class="text-muted">System log data would be displayed here. (Requires backend implementation for HTML content)</p>
                                <div id="systemLogChartPlaceholder"></div>
                            </div>
                        </div>
                    </div>
                @endcan

                @can('perform school rollover')
                    <div class="col-md-6">
                        <div class="card card-outline card-primary">
                            <div class="card-header"><h3 class="card-title">School Year Rollover Status</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i
                                            class="fas fa-minus"></i></button>
                                </div>
                            </div>
                            <div class="card-body" style="height: 300px;">
                                <p class="text-muted">School year rollover information and actions. (Requires backend implementation for HTML content)</p>
                                <div id="rolloverStatusChartPlaceholder"></div>
                            </div>
                        </div>
                    </div>
                @endcan
            </div>

            {{-- Row: Recent Activity / Other Widgets (Existing) --}}
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Recent Activity</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i
                                        class="fas fa-minus"></i></button>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Placeholder for recent user activities or system events. (Requires backend implementation)</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Upcoming Events/Tasks</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i
                                        class="fas fa-minus"></i></button>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Placeholder for upcoming school events or assigned tasks. (Requires backend implementation)</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection


@section('css')
    <style>
        .btn-app {
            min-width: 100px;
            margin: 5px;
        }

        .card-body canvas {
            width: 100% !important;
        }

        /* Ensure canvas respects container width */
    </style>
    @stack('styles')
@stop

@section('js')
    {{-- Ensure Chart.js is loaded --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        $(function () {
            console.log('Comprehensive staff dashboard JS loaded with color refinements and new chart initializations!');

            const chartData = @json($chartData ?? []);

            // --- Enhanced Color Palette & Helper Functions ---
            const appColors = { // Defined a structured color object
                primary: '#007bff', indigo: '#6610f2', purple: '#6f42c1',
                pink: '#e83e8c', red: '#dc3545', orange: '#fd7e14',
                yellow: '#ffc107', green: '#28a745', teal: '#20c997',
                cyan: '#17a2b8', gray: '#6c757d', grayDark: '#343a40',
                success: '#28a745', // Added for consistency
                info: '#17a2b8',   // Added for consistency
                warning: '#ffc107' // Added for consistency
            };

            const defaultChartPalette = [ // Using the appColors object
                appColors.primary, appColors.green, appColors.yellow, appColors.red,
                appColors.teal, appColors.purple, appColors.orange, appColors.cyan,
                appColors.pink, appColors.indigo, appColors.gray
            ];

            function hexToRgba(hex, alpha = 1) {
                hex = hex.replace('#', '');
                const r = parseInt(hex.length === 3 ? hex.slice(0, 1).repeat(2) : hex.slice(0, 2), 16);
                const g = parseInt(hex.length === 3 ? hex.slice(1, 2).repeat(2) : hex.slice(2, 4), 16);
                const b = parseInt(hex.length === 3 ? hex.slice(2, 3).repeat(2) : hex.slice(4, 6), 16);
                return `rgba(${r}, ${g}, ${b}, ${alpha})`;
            }

            const generateChartColors = (count, alpha = 0.75) => {
                const colors = [];
                for (let i = 0; i < count; i++) {
                    colors.push(hexToRgba(defaultChartPalette[i % defaultChartPalette.length], alpha));
                }
                return colors;
            };
            
            const getChartColor = (index, alpha = 0.75) => {
                return hexToRgba(defaultChartPalette[index % defaultChartPalette.length], alpha);
            };


            // --- Generic Chart Initializer Function ---
            function initializeChart(canvasId, chartType, data, options, noDataMessage = "No data available for this chart.") {
                const ctx = document.getElementById(canvasId)?.getContext('2d');
                const hasData = data &&
                    ((data.labels && data.labels.length > 0) ||
                        (data.datasets && data.datasets.some(ds => ds.data && ds.data.length > 0 && ds.data.some(val => val !== null && val !== undefined)))); // Check for actual data points

                if (ctx && hasData) {
                    // Destroy existing chart instance if it exists to prevent conflicts on re-renders
                    if (Chart.getChart(canvasId)) {
                        Chart.getChart(canvasId).destroy();
                    }
                    new Chart(ctx, {type: chartType, data: data, options: options});
                } else if (ctx) {
                     // Clear previous no-data message if any, then add new one
                    $(ctx.canvas).parent().empty().html(`<p class="text-center text-muted p-5" style="line-height: ${$(ctx.canvas).parent().height()}px;">${noDataMessage}</p>`);
                } else {
                    console.warn(`Canvas with ID '${canvasId}' not found.`);
                }
            }

            // --- Standard Chart Options ---
            const standardBarLineOptions = {
                responsive: true, maintainAspectRatio: false,
                scales: {
                    y: {beginAtZero: true, ticks: {precision: 0, color: appColors.grayDark}, grid: {color: hexToRgba(appColors.gray, 0.2)}},
                    x: {
                        ticks: {autoSkip: false, maxRotation: 70, minRotation: 30, font: {size: 10}, color: appColors.grayDark},
                        grid: {display: false}
                    }
                },
                plugins: {
                    legend: {display: true, position: 'top', labels: {color: appColors.grayDark, font: {size: 12}}},
                    title: {display: false},
                    tooltip: {
                        backgroundColor: hexToRgba(appColors.grayDark, 0.9),
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        borderColor: appColors.primary,
                        borderWidth: 1,
                        callbacks: {
                            label: function (context) {
                                let label = context.dataset.label || context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += context.parsed.y.toLocaleString();
                                }
                                return label;
                            }
                        }
                    }
                }
            };
            const standardPieDoughnutOptions = {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: {position: 'bottom', labels: {color: appColors.grayDark, font: {size: 12}}},
                    title: {display: false},
                    tooltip: {
                        backgroundColor: hexToRgba(appColors.grayDark, 0.9),
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        callbacks: {
                            label: function (context) {
                                let label = context.label || '';
                                let value = context.parsed || 0;
                                let sum = context.dataset.data.reduce((a, b) => a + b, 0);
                                let percentage = sum > 0 ? ((value / sum) * 100).toFixed(1) + '%' : '0%';
                                return `${label}: ${value.toLocaleString()} (${percentage})`;
                            }
                        }
                    }
                }
            };

            // --- Initialize Existing Charts with new color scheme ---
            const studentsByGradeDataRaw = chartData.students_by_grade || [];
            initializeChart('studentsByGradeChart', 'bar', {
                labels: studentsByGradeDataRaw.map(item => item.label),
                datasets: [{
                    label: 'Number of Students',
                    data: studentsByGradeDataRaw.map(item => item.count),
                    backgroundColor: getChartColor(0, 0.7), 
                    borderColor: getChartColor(0, 1),     
                    borderWidth: 1.5,
                    hoverBackgroundColor: getChartColor(0, 0.9),
                    hoverBorderColor: getChartColor(0, 1),
                }]
            }, standardBarLineOptions, 'No student by grade data.');


            const feeStatusDataRaw = chartData.fee_status || [];
            initializeChart('feeStatusChart', 'doughnut', {
                labels: feeStatusDataRaw.map(item => item.label),
                datasets: [{
                    data: feeStatusDataRaw.map(item => item.count),
                    backgroundColor: generateChartColors(feeStatusDataRaw.length || 3, 0.8), 
                    borderColor: '#fff', 
                    borderWidth: 2,
                    hoverOffset: 8
                }]
            }, standardPieDoughnutOptions, 'No fee status data.');


            // --- Initialize NEW Charts ---

            // Student Insights
            const enrollmentTrendData = chartData.enrollment_trend || { labels: [], datasets: [{label: 'Enrollments', data: []}] };
            initializeChart('studentEnrollmentTrendChart', 'line', {
                labels: enrollmentTrendData.labels,
                datasets: [{
                    label: enrollmentTrendData.datasets[0]?.label || 'Enrollments',
                    data: enrollmentTrendData.datasets[0]?.data || [],
                    borderColor: getChartColor(1, 1), // green
                    backgroundColor: getChartColor(1, 0.3),
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: getChartColor(1, 1),
                    pointBorderColor: '#fff',
                    pointHoverRadius: 6,
                    pointRadius: 4,
                }]
            }, standardBarLineOptions, 'No enrollment trend data.');

            const genderData = chartData.gender_distribution || {labels: [], datasets: [{data: []}]};
            initializeChart('studentGenderDistributionChart', 'pie', {
                labels: genderData.labels,
                datasets: [{
                    data: genderData.datasets[0]?.data || [],
                    backgroundColor: generateChartColors(genderData.labels?.length || 2, 0.85),
                    borderColor: '#fff',
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            }, standardPieDoughnutOptions, 'No gender distribution data.');

            const studentStatusData = chartData.student_status || {labels: [], datasets: [{data: []}]};
            initializeChart('studentStatusOverviewChart', 'doughnut', {
                labels: studentStatusData.labels,
                datasets: [{
                    data: studentStatusData.datasets[0]?.data || [],
                    backgroundColor: generateChartColors(studentStatusData.labels?.length || 2, 0.85), // Using 2 as min for colors
                    borderColor: '#fff',
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            }, standardPieDoughnutOptions, 'No student status data.');

            const studentsPerSchoolData = chartData.students_per_school || { labels: [], datasets: [{label: 'Students', data: []}] };
            if (document.getElementById('studentsPerSchoolChart')) { // Check if element exists (it's conditional)
                initializeChart('studentsPerSchoolChart', 'bar', {
                    labels: studentsPerSchoolData.labels,
                    datasets: [{
                        label: studentsPerSchoolData.datasets[0]?.label || 'Students',
                        data: studentsPerSchoolData.datasets[0]?.data || [],
                        backgroundColor: getChartColor(2, 0.7), // yellow
                        borderColor: getChartColor(2, 1),
                        borderWidth: 1.5
                    }]
                }, standardBarLineOptions, 'No students per school data.');
            }


            // Financial Deep Dive
            const revenueFeeTypeData = chartData.revenue_by_fee_type || { labels: [], datasets: [{label: 'Revenue', data: []}] };
            initializeChart('revenueByFeeTypeChart', 'bar', {
                labels: revenueFeeTypeData.labels,
                datasets: [{
                    label: revenueFeeTypeData.datasets[0]?.label || 'Revenue',
                    data: revenueFeeTypeData.datasets[0]?.data || [],
                    backgroundColor: getChartColor(3, 0.7), // red
                    borderColor: getChartColor(3, 1),
                    borderWidth: 1.5
                }]
            }, standardBarLineOptions, 'No revenue by fee type data.');

            const paymentMethodsData = chartData.payment_methods || {labels: [], datasets: [{data: []}]};
            initializeChart('paymentMethodsChart', 'pie', {
                labels: paymentMethodsData.labels,
                datasets: [{
                    data: paymentMethodsData.datasets[0]?.data || [],
                    backgroundColor: generateChartColors(paymentMethodsData.labels?.length || 3, 0.85), // Using 3 as min
                    borderColor: '#fff',
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            }, standardPieDoughnutOptions, 'No payment method data.');

            const outstandingByGradeData = chartData.outstanding_by_grade || { labels: [], datasets: [{label: 'Outstanding ($)', data: []}] };
            initializeChart('outstandingByGradeChart', 'bar', {
                labels: outstandingByGradeData.labels,
                datasets: [{
                    label: outstandingByGradeData.datasets[0]?.label || 'Outstanding ($)',
                    data: outstandingByGradeData.datasets[0]?.data || [],
                    backgroundColor: getChartColor(4, 0.7), // teal
                    borderColor: getChartColor(4, 1),
                    borderWidth: 1.5
                }]
            }, standardBarLineOptions, 'No outstanding balances by grade data.');

            const discountsWaiversData = chartData.discounts_waivers || { labels: [], datasets: [{label: 'Amount ($)', data: []}] };
            initializeChart('discountsWaiversChart', 'bar', {
                labels: discountsWaiversData.labels,
                datasets: [{
                    label: discountsWaiversData.datasets[0]?.label || 'Amount ($)',
                    data: discountsWaiversData.datasets[0]?.data || [],
                    backgroundColor: getChartColor(5, 0.7), // purple
                    borderColor: getChartColor(5, 1),
                    borderWidth: 1.5
                }]
            }, standardBarLineOptions, 'No discount/waiver data.');

            // Parent Overview
            const parentAccountStatusData = chartData.parent_account_status || {labels: [], datasets: [{data: []}]};
            initializeChart('parentAccountStatusChart', 'pie', {
                labels: parentAccountStatusData.labels,
                datasets: [{
                    data: parentAccountStatusData.datasets[0]?.data || [],
                    backgroundColor: generateChartColors(parentAccountStatusData.labels?.length || 3, 0.85), // Using 3 as min
                    borderColor: '#fff',
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            }, standardPieDoughnutOptions, 'No parent account status data.');

            // Academic Performance
            const avgScoresSubjectData = chartData.avg_scores_subject || { labels: [], datasets: [{label: 'Average Score', data: []}] };
            initializeChart('averageScoresBySubjectChart', 'bar', {
                labels: avgScoresSubjectData.labels,
                datasets: [{
                    label: avgScoresSubjectData.datasets[0]?.label || 'Average Score',
                    data: avgScoresSubjectData.datasets[0]?.data || [],
                    backgroundColor: getChartColor(6, 0.7), // orange
                    borderColor: getChartColor(6, 1),
                    borderWidth: 1.5
                }]
            }, standardBarLineOptions, 'No average score data.');

            const passFailGradeData = chartData.pass_fail_grade || { labels: [], datasets: [{label: 'Pass', data: []}, {label: 'Fail', data: []}] };
            initializeChart('passFailByGradeChart', 'bar', {
                labels: passFailGradeData.labels,
                datasets: [
                    {
                        label: passFailGradeData.datasets[0]?.label || 'Pass',
                        data: passFailGradeData.datasets[0]?.data || [],
                        backgroundColor: getChartColor(1, 0.7), // Green for Pass
                        borderColor: getChartColor(1, 1),
                        borderWidth: 1.5
                    },
                    {
                        label: passFailGradeData.datasets[1]?.label || 'Fail',
                        data: passFailGradeData.datasets[1]?.data || [],
                        backgroundColor: getChartColor(3, 0.7), // Red for Fail
                        borderColor: getChartColor(3, 1),
                        borderWidth: 1.5
                    }
                ]
            }, standardBarLineOptions, 'No pass/fail rate data.');

            // For 'systemLogChartPlaceholder' and 'rolloverStatusChartPlaceholder',
            // you would typically populate these with HTML tables or lists from controller data, not Chart.js.
            // Example:
            // if (chartData.system_logs_html) {
            //     $('#systemLogChartPlaceholder').html(chartData.system_logs_html);
            // } else {
            //     $('#systemLogChartPlaceholder').html('<p class="text-center text-muted p-5">No system log data available.</p>');
            // }
        });
    </script>
    @stack('scripts')
@stop
