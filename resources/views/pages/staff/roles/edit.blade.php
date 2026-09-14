{{-- resources/views/pages/staff/roles/edit.blade.php --}}

@extends('layouts.app')

{{-- Page Title (Browser Tab) --}}
@section('title', 'Edit Role - ' . e($role->name))

{{-- Main Page Content --}}
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                {{-- Edit Form Card --}}
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">Edit Role Details and Permissions</h3>
                    </div>
                    {{-- Form Start --}}
                    {{-- Use $role->id as Spatie uses standard integer IDs --}}
                    <form method="POST" action="{{ route('staff.roles.update', $role->id) }}">
                        @csrf
                        @method('PUT') {{-- Use PUT method for updates --}}

                        <div class="card-body">
                            {{-- Display Alerts --}}
                            @include('layouts.partials.alerts') {{-- Use the correct path --}}

                            {{-- Role Name --}}
                            @php
                                $coreRoles = ['god mode', 'super admin', 'admin']; // Define core roles
                                $isCoreRole = in_array($role->name, $coreRoles);
                            @endphp
                            <div class="form-group">
                                <label for="name">Role Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $role->name) }}" required
                                       placeholder="e.g., finance manager, guidance counselor"
                                    {{ $isCoreRole ? 'readonly' : '' }}> {{-- Make core roles readonly --}}
                                @if($isCoreRole)
                                    <small class="form-text text-warning">Core role names cannot be changed.</small>
                                @else
                                    <small class="form-text text-muted">Use lowercase letters and underscores if needed (e.g., office_staff).</small>
                                @endif
                                @error('name')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
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
                                                    <input class="custom-control-input" type="checkbox"
                                                           id="permission_{{ $permission->id }}" name="permissions[]"
                                                           value="{{ $permission->name }}"
                                                        {{-- Check if permission was previously selected OR is currently assigned --}}
                                                        {{ (is_array(old('permissions')) && in_array($permission->name, old('permissions'))) || (!old('permissions') && in_array($permission->name, $rolePermissions)) ? 'checked' : '' }}>
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
                            <button type="submit" class="btn btn-info">Update Role</button>
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
