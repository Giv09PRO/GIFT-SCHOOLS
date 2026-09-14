@extends('layouts.app') {{-- Assuming this is your main application layout --}}

@section('title', 'Subject Details')
@section('subtitle', $subject->title ?? 'Subject Information')

@section('content_body')
    <div class="container-fluid">

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Details for: <strong>{{ $subject->title }}</strong></h3>
                        <div class="card-tools">
                            @can('edit subjects') {{-- General permission to edit subjects --}}
                            @if(Auth::user()->can('edit subjects across all schools') || (isset(Auth::user()->current_school_id) && Auth::user()->current_school_id == $subject->school_id))
                                <a href="{{ route('staff.subjects.edit', $subject->subject_id) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i> Edit Subject
                                </a>
                            @endif
                            @endcan
                            <a href="{{ route('staff.subjects.index') }}" class="btn btn-sm btn-outline-secondary ml-2">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-striped">
                            <tbody>
                            <tr>
                                <th style="width: 30%;">Subject ID</th>
                                <td>{{ $subject->subject_id }}</td>
                            </tr>
                            <tr>
                                <th>Title</th>
                                <td>{{ $subject->title }}</td>
                            </tr>
                            <tr>
                                <th>Short Name</th>
                                <td>{{ $subject->short_name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>School</th>
                                <td>
                                    @if($schoolContext)
                                        {{ $schoolContext->title }} (ID: {{ $subject->school_id }})
                                    @else
                                        School ID: {{ $subject->school_id }} (Context not found)
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Academic Year (SYEAR)</th>
                                <td>{{ $subject->syear }}</td>
                            </tr>
                            <tr>
                                <th>Sort Order</th>
                                <td>{{ $subject->sort_order ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Created At</th>
                                <td>{{ $subject->created_at ? $subject->created_at->format('M d, Y H:i A') : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Last Updated At</th>
                                <td>{{ $subject->updated_at ? $subject->updated_at->format('M d, Y H:i A') : 'N/A' }}</td>
                            </tr>
                            @if($subject->rollover_id)
                                <tr>
                                    <th>Rollover ID</th>
                                    <td>{{ $subject->rollover_id }}</td>
                                </tr>
                            @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                {{-- Related Information Section --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Related Information</h3>
                    </div>
                    <div class="card-body">
                        @if($subject->gradeLevels && $subject->gradeLevels->count() > 0)
                            <h5>Taught in Grade Levels:</h5>
                            <ul>
                                @foreach($subject->gradeLevels as $gradeLevel)
                                    <li>{{ $gradeLevel->title }} (School Year: {{ $gradeLevel->pivot->syear }})</li>
                                @endforeach
                            </ul>
                        @else
                            <p>Not currently assigned to any grade levels.</p>
                        @endif
                        <hr>
                        @if($subject->teacherAssignments && $subject->teacherAssignments->count() > 0)
                            <h5>Teacher Assignments:</h5>
                            <ul>
                                @foreach($subject->teacherAssignments as $assignment)
                                    <li>
                                        Teacher: {{ $assignment->staff->first_name ?? 'N/A' }} {{ $assignment->staff->last_name ?? '' }}
                                        (Grade: {{ $assignment->gradeLevel->title ?? 'N/A' }})
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p>No teachers currently assigned to this subject.</p>
                        @endif
                        <hr>
                        @if($subject->exams && $subject->exams->count() > 0)
                            <h5>Associated Exams:</h5>
                            <ul>
                                @foreach($subject->exams as $exam)
                                    <li>{{ $exam->description ?: $exam->type }} ({{ $exam->exam_start_date }})</li>
                                @endforeach
                            </ul>
                        @else
                            <p>No exams currently associated with this subject.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@push('styles')
    <style>
        .table th {
            font-weight: 600;
        }
    </style>
@endpush

@push('scripts')
    {{-- Add any page-specific JavaScript here if needed --}}
@endpush
