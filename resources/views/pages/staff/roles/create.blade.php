{{-- resources/views/pages/staff/roles/create.blade.php --}}

@extends('layouts.app')

{{-- Page Title (Browser Tab) --}}
@section('title', 'Add New Role')

{{-- Main Page Content --}}
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                {{-- Create Form Card --}}
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">Enter Role Details and Assign Permissions</h3>
                    </div>
                    {{-- Form Start --}}
                    <form method="POST" action="{{ route('staff.roles.store') }}">
                        @csrf {{-- CSRF Protection --}}

                        <div class="card-body">
                            {{-- Display Alerts --}}
                            @include('layouts.partials.alerts') {{-- Use the correct path --}}

                            {{-- Role Name --}}
                            <div class="form-group">
                                <label for="name">Role Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required placeholder="e.g., finance manager, guidance counselor">
                                @error('name')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                                <small class="form-text text-muted">Use lowercase letters and underscores if needed (e.g., office_staff).</small>
                            </div>

                            <hr>

                            {{-- Permissions --}}
                            <div class="form-group">
                                <label>Assign Permissions</label>
                                @error('permissions') {{-- Error for the whole array --}}
                                <div class="text-danger mb-2"><strong>{{ $message }}</strong></div>
                                @enderror
                                <div class="row">
                                    {{-- Group permissions for better readability (optional) --}}
                                    @php
                                        $groupedPermissions = $permissions->groupBy(function($item) {
                                            // Simple grouping based on first word
                                            $parts = explode(' ', $item->name);
                                            return ucfirst($parts[0]); // e.g., View, Manage, Edit, Generate
                                        });
                                    @endphp

                                    @foreach($groupedPermissions as $group => $perms)
                                        <div class="col-md-4 col-sm-6 mb-3">
                                            <h5>{{ $group }}</h5>
                                            @foreach($perms as $permission)
                                                <div class="custom-control custom-checkbox">
                                                    <input class="custom-control-input" type="checkbox" id="permission_{{ $permission->id }}" name="permissions[]" value="{{ $permission->name }}"
                                                        {{ is_array(old('permissions')) && in_array($permission->name, old('permissions')) ? 'checked' : '' }}>
                                                    <label for="permission_{{ $permission->id }}" class="custom-control-label">{{ $permission->name }}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                                @error('permissions.*') {{-- Error for individual permissions --}}
                                <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                        </div>
                        <div class="card-footer text-right">
                            <a href="{{ route('staff.roles.index') }}" class="btn btn-default">Cancel</a>
                            <button type="submit" class="btn btn-success">Save Role</button>
                        </div>
                    </form>
                    {{-- Form End --}}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    {{-- Add custom CSS if needed --}}
@endpush
