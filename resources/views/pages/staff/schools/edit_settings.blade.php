{{-- resources/views/pages/staff/schools/edit_settings.blade.php --}}

@extends('layouts.app')

{{-- Page Title (Browser Tab) --}}
@section('title', 'Edit Settings - ' . e($school->title))

{{-- Main Page Content --}}
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                {{-- Edit Settings Form Card --}}
                <div class="card card-purple card-outline"> {{-- Changed color --}}
                    <div class="card-header">
                        <h3 class="card-title">Configure School Settings</h3>
                    </div>
                    {{-- Form Start --}}
                    {{-- Use $school->id as Route Model Binding expects the raw ID --}}
                    <form method="POST" action="{{ route('staff.schools.settings.update', $school->id) }}">
                        @csrf
                        @method('PUT') {{-- Use PUT method for updates --}}

                        <div class="card-body">
                            {{-- Display Alerts --}}
                            @include('layouts.partials.alerts') {{-- Use the correct path --}}

                            {{-- Use the getSetting helper from the School model --}}
                            {{-- Remember to use the 'settings[key]' naming convention for inputs --}}

                            {{-- Example: Portal Settings --}}
                            <fieldset class="mb-3 p-3 border rounded">
                                <legend class="w-auto px-2 h6">Portal Access</legend>
                                <div class="form-group row">
                                    <label for="setting_parent_portal" class="col-sm-4 col-form-label">Enable Parent Portal</label>
                                    <div class="col-sm-8">
                                        {{-- Use '1' and '0' for boolean values in selects for easier handling --}}
                                        <select name="settings[portal][parent_access_enabled]" id="setting_parent_portal" class="form-control @error('settings.portal.parent_access_enabled') is-invalid @enderror">
                                            <option value="1" {{ old('settings.portal.parent_access_enabled', $school->getSetting('portal.parent_access_enabled', false)) == true ? 'selected' : '' }}>Yes</option>
                                            <option value="0" {{ old('settings.portal.parent_access_enabled', $school->getSetting('portal.parent_access_enabled', false)) == false ? 'selected' : '' }}>No</option>
                                        </select>
                                        @error('settings.portal.parent_access_enabled') <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span> @enderror
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="setting_student_portal" class="col-sm-4 col-form-label">Enable Student Portal</label>
                                    <div class="col-sm-8">
                                        <select name="settings[portal][student_access_enabled]" id="setting_student_portal" class="form-control @error('settings.portal.student_access_enabled') is-invalid @enderror">
                                            <option value="1" {{ old('settings.portal.student_access_enabled', $school->getSetting('portal.student_access_enabled', false)) == true ? 'selected' : '' }}>Yes</option>
                                            <option value="0" {{ old('settings.portal.student_access_enabled', $school->getSetting('portal.student_access_enabled', false)) == false ? 'selected' : '' }}>No</option>
                                        </select>
                                        @error('settings.portal.student_access_enabled') <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span> @enderror
                                    </div>
                                </div>
                            </fieldset>

                            {{-- Example: Communication Settings --}}
                            <fieldset class="mb-3 p-3 border rounded">
                                <legend class="w-auto px-2 h6">Communication</legend>
                                <div class="form-group row">
                                    <label for="setting_comm_email" class="col-sm-4 col-form-label">Default Sender Email</label>
                                    <div class="col-sm-8">
                                        <input type="email" name="settings[communication][default_sender_email]" id="setting_comm_email" class="form-control @error('settings.communication.default_sender_email') is-invalid @enderror" value="{{ old('settings.communication.default_sender_email', $school->getSetting('communication.default_sender_email')) }}">
                                        @error('settings.communication.default_sender_email') <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span> @enderror
                                    </div>
                                </div>
                            </fieldset>

                            {{-- Example: Theme Settings --}}
                            <fieldset class="mb-3 p-3 border rounded">
                                <legend class="w-auto px-2 h6">Theme</legend>
                                <div class="form-group row">
                                    <label for="setting_theme_color" class="col-sm-4 col-form-label">Primary Color</label>
                                    <div class="col-sm-8">
                                        <input type="color" name="settings[theme][primary_color]" id="setting_theme_color" class="form-control @error('settings.theme.primary_color') is-invalid @enderror" value="{{ old('settings.theme.primary_color', $school->getSetting('theme.primary_color', '#ffffff')) }}">
                                        @error('settings.theme.primary_color') <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span> @enderror
                                    </div>
                                </div>
                                {{-- Add field for logo upload if needed --}}
                            </fieldset>

                            {{-- Example: Attendance Settings --}}
                            <fieldset class="mb-3 p-3 border rounded">
                                <legend class="w-auto px-2 h6">Attendance</legend>
                                <div class="form-group row">
                                    <label for="setting_tardy_threshold" class="col-sm-4 col-form-label">Tardy Threshold (Minutes)</label>
                                    <div class="col-sm-8">
                                        <input type="number" min="0" step="1" name="settings[attendance][tardy_threshold_minutes]" id="setting_tardy_threshold" class="form-control @error('settings.attendance.tardy_threshold_minutes') is-invalid @enderror" value="{{ old('settings.attendance.tardy_threshold_minutes', $school->getSetting('attendance.tardy_threshold_minutes', 15)) }}">
                                        @error('settings.attendance.tardy_threshold_minutes') <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span> @enderror
                                    </div>
                                </div>
                                {{-- Add interface for managing attendance codes array if needed (more complex) --}}
                            </fieldset>

                            {{-- Add more fieldsets for other setting categories --}}

                        </div>
                        <div class="card-footer text-right">
                            <a href="{{ route('staff.schools.show', $school->id) }}" class="btn btn-default">Cancel</a>
                            <button type="submit" class="btn btn-purple">Save Settings</button> {{-- Changed button color --}}
                        </div>
                    </form>
                    {{-- Form End --}}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Add JS if needed for complex settings like adding/removing attendance codes --}}
@endpush

@push('styles')
    {{-- Add custom CSS if needed --}}
@endpush
