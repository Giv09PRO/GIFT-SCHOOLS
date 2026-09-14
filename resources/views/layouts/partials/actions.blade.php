@can('view', $exam)
    <a href="{{ route('staff.exams.show', $exam) }}" class="btn btn-info btn-sm">
        <i class="fas fa-eye"></i>
    </a>
@endcan
@can('manage exams', $exam)
    <a href="{{ route('staff.exams.edit', $exam) }}" class="btn btn-warning btn-sm">
        <i class="fas fa-edit"></i>
    </a>
@endcan
@can('manage exams', $exam)
    <form action="{{ route('staff.exams.destroy', $exam) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this exam?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger btn-sm">
            <i class="fas fa-trash"></i>
        </button>
    </form>
@endcan
