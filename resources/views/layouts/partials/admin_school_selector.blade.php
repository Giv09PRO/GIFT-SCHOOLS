{{--In the blade view:--}}
<select name="selected_school_id" onchange="this.form.submit()">
    <option value="">-- View All Schools --</option> // Or default to admin's current school
    @foreach($schools as $school)
        <option value="{{ $school->id }}" {{ session('admin_viewing_school_id') == $school->id ? 'selected' : '' }}>
            {{ $school->title }}
        </option>
    @endforeach
</select>
<noscript><button type="submit">Set School</button></noscript>
