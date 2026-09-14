@extends('layouts.app')

@php
    use App\Helpers\Qs;
    // $feeDefinitions is expected to be a paginated collection of FeeDefinition models
    // passed from FeeController@indexDefinitions
@endphp

@section('title', 'Manage Fee Definitions')

@section('content')
<div class="container-fluid">
    @include('layouts.partials.alerts') {{-- For displaying success/error messages --}}

    <div class="card card-purple card-outline shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title"><i class="fas fa-cogs me-2"></i>Manage Fee Definitions (Fee Structures)</h3>
            <div>
                @can('manage finances') {{-- Or a more specific permission like 'create fee_definitions' --}}
                    <a href="{{ route('staff.fees.definitions.create') }}" class="btn btn-sm btn-purple">
                        <i class="fas fa-plus-circle me-1"></i> Create New Fee Definition
                    </a>
                @endcan
                 <a href="{{ route('staff.fees.index') }}" class="btn btn-sm btn-outline-secondary ms-2">
                    <i class="fas fa-money-check-alt me-1"></i> Back to Fee Installments
                </a>
            </div>
        </div>

        {{-- Optional: Add a filter section here if needed --}}
        {{--
        <div class="card-body pb-0">
            <form method="GET" action="{{ route('staff.fees.definitions.index') }}">
                <div class="row">
                    <div class="col-md-4">
                        <input type="text" name="search_name" class="form-control form-control-sm" placeholder="Search by Fee Name" value="{{ request('search_name') }}">
                    </div>
                    <div class="col-md-3">
                        <select name="filter_syear" class="form-control form-control-sm select2">
                            <option value="">All School Years</option>
                            @for ($y = date('Y') - 5; $y <= date('Y') + 2; $y++)
                                @php $yearFormatted = $y . '-' . ($y + 1); @endphp
                                <option value="{{ $yearFormatted }}" {{ request('filter_syear') == $yearFormatted ? 'selected' : '' }}>
                                    {{ $yearFormatted }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    </div>
                </div>
            </form>
        </div>
        --}}

        <div class="card-body">
            @if($feeDefinitions->isEmpty())
                <div class="alert alert-info text-center">
                    <p><i class="fas fa-info-circle me-2"></i>No fee definitions found.</p>
                    @can('manage finances')
                        <p>You can start by <a href="{{ route('staff.fees.definitions.create') }}">creating a new fee definition</a>.</p>
                    @endcan
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover table-sm">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Fee Name / Title</th>
                                <th>School Year</th>
                                <th class="text-end">Total Amount</th>
                                <th class="text-center">Installments</th>
                                <th>Description</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($feeDefinitions as $index => $definition)
                                <tr>
                                    <td>{{ $feeDefinitions->firstItem() + $index }}</td>
                                    <td>{{ $definition->fee_name }}</td>
                                    <td>{{ $definition->syear }}</td>
                                    <td class="text-end">{{ Qs::formatCurrency($definition->total_amount) }}</td>
                                    <td class="text-center">{{ $definition->number_of_installments }}</td>
                                    <td>{{ Str::limit($definition->description, 50) ?: 'N/A' }}</td>
                                    <td class="text-center">
                                        <nobr>
                                            @can('manage finances') {{-- Or 'edit fee_definitions' --}}
                                                <a href="{{ route('staff.fees.definitions.edit', $definition->id) }}" class="btn btn-xs btn-info" title="Edit Definition">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endcan

                                            {{-- Assign Structure Button - if you want a direct link from here --}}
                                            @if($definition->number_of_installments > 0)
                                                <a href="{{ route('staff.fees.structure.assign_create', ['fee_definition_id' => $definition->id]) }}" class="btn btn-xs btn-success" title="Assign this Structure">
                                                    <i class="fas fa-sitemap"></i>
                                                </a>
                                            @endif

                                            @can('manage finances') {{-- Or 'delete fee_definitions' --}}
                                                @if(!$definition->installments()->exists()) {{-- Only allow delete if not in use --}}
                                                    <form action="{{ route('staff.fees.definitions.destroy', $definition->id) }}" method="POST" class="d-inline delete-form"
                                                          onsubmit="return confirm('Are you sure you want to delete this fee definition? This action cannot be undone.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-xs btn-danger" title="Delete Definition">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    <button type="button" class="btn btn-xs btn-danger disabled" title="Cannot delete: In use by fee installments" disabled>
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                @endif
                                            @endcan
                                        </nobr>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 d-flex justify-content-center">
                    {{ $feeDefinitions->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
    {{-- Select2 CSS if using filters and not globally available --}}
    {{-- <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" /> --}}
    {{-- <link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css" rel="stylesheet" /> --}}
    <style>
        .btn-purple {
            color: #fff;
            background-color: #6f42c1; /* Bootstrap purple */
            border-color: #6f42c1;
        }
        .btn-purple:hover {
            color: #fff;
            background-color: #5a32a3;
            border-color: #532f91;
        }
        .table th, .table td {
            vertical-align: middle;
        }
    </style>
@endpush

@push('scripts')
    {{-- Select2 JS if using filters and not globally available --}}
    {{-- <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script> --}}
    {{-- <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> --}}
    <script>
        $(document).ready(function () {
            // Initialize Select2 for filters if uncommented
            // $('.select2').select2({ theme: 'bootstrap4' });

            console.log('Fee Definitions index page JS loaded.');
        });
    </script>
@endpush
