{{-- resources/views/pages/staff/fees/structure/_shared_assignment_fields.blade.php --}}

@php
    // $form_prefix will be 'grades' or 'students' to make field IDs unique for labels and JS
    // $form_type is passed to check old('assignment_type') for repopulating fields correctly
    // and for linking errors to the correct form if using named error bags.
@endphp

<div class="form-group mb-3">
    <label for="assignment_date_{{ $form_prefix }}" class="form-label">Assignment Date <span class="text-danger">*</span></label>
    <input type="date" name="assignment_date" id="assignment_date_{{ $form_prefix }}"
           class="form-control @error('assignment_date', $form_type.'_form') is-invalid @enderror @error('assignment_date') {{-- General error if not using named bags --}} is-invalid @enderror"
           value="{{ old('assignment_type') == $form_type ? old('assignment_date', now()->toDateString()) : now()->toDateString() }}" required>
    <small class="form-text text-muted">This date will be used as the 'assigned_date' for all created installments and can be a base for due dates.</small>
    @error('assignment_date', $form_type.'_form') <span class="invalid-feedback">{{ $message }}</span> @enderror
    @error('assignment_date') {{-- General error display --}}
        @if (!$errors->hasBag($form_type.'_form') && $loop->first) {{-- Avoid double display if named bag also caught it --}}
            <span class="invalid-feedback">{{ $message }}</span>
        @endif
    @enderror
</div>

{{-- Container for Dynamically Added Due Date Fields --}}
{{-- The ID includes $form_prefix to be unique for each tab's dynamic content generation --}}
<div id="term-due-dates-container-{{ $form_prefix }}" class="mb-3 p-3 border rounded bg-light" style="display: none;">
    {{-- Due date fields will be dynamically added here by JavaScript --}}
    {{-- Error handling for these dynamic fields is managed by the JavaScript that generates them,
         using the laravelData.errors object passed from the main view. --}}
</div>

<div class="form-group mb-4">
    <div class="form-check">
        <input class="form-check-input @error('allow_duplicates', $form_type.'_form') is-invalid @enderror @error('allow_duplicates') is-invalid @enderror"
               type="checkbox" name="allow_duplicates" id="allow_duplicates_{{ $form_prefix }}"
               value="1" {{ old('assignment_type') == $form_type && old('allow_duplicates') ? 'checked' : '' }}>
        <label class="form-check-label" for="allow_duplicates_{{ $form_prefix }}">
            Allow assigning this fee structure even if students already have existing installments for it?
        </label>
        <small class="form-text text-muted d-block">If unchecked, students who already have any installment of this fee structure for the current year will be skipped.</small>
    </div>
    @error('allow_duplicates', $form_type.'_form') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
    @error('allow_duplicates')
        @if (!$errors->hasBag($form_type.'_form') && $loop->first)
            <span class="invalid-feedback d-block">{{ $message }}</span>
        @endif
    @enderror
</div>
