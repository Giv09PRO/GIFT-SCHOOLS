{{-- resources/views/partials/alerts.blade.php --}}
{{-- This partial displays session flash messages using AdminLTE alert styles --}}

{{-- Check for standard success message --}}
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h5><i class="icon fas fa-check"></i> Success!</h5>
        {{ session('success') }}
    </div>
@endif

{{-- Check for standard error message --}}
@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h5><i class="icon fas fa-ban"></i> Error!</h5>
        {{ session('error') }}
    </div>
@endif

{{-- Check for standard warning message --}}
@if (session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h5><i class="icon fas fa-exclamation-triangle"></i> Warning!</h5>
        {{ session('warning') }}
    </div>
@endif

{{-- Check for standard info message --}}
@if (session('info'))
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h5><i class="icon fas fa-info"></i> Info</h5>
        {{ session('info') }}
    </div>
@endif

{{-- Check for alternative flash keys (often used in older projects or specific packages) --}}
@if (session('flash_success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h5><i class="icon fas fa-check"></i> Success!</h5>
        {{ session('flash_success') }}
    </div>
@endif

@if (session('flash_danger') || session('flash_error')) {{-- Allow both keys --}}
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    <h5><i class="icon fas fa-ban"></i> Error!</h5>
    {{ session('flash_danger') ?? session('flash_error') }} {{-- Display whichever is set --}}
</div>
@endif

@if (session('flash_warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h5><i class="icon fas fa-exclamation-triangle"></i> Warning!</h5>
        {{ session('flash_warning') }}
    </div>
@endif

@if (session('flash_info'))
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h5><i class="icon fas fa-info"></i> Info</h5>
        {{ session('flash_info') }}
    </div>
@endif

{{-- Check for validation errors stored in the session (usually handled by @error directives, but can be included here for general display) --}}
@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h5><i class="icon fas fa-ban"></i> Validation Errors!</h5>
        Please check the form below for errors:
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
