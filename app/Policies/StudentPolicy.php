<?php

namespace App\Policies;

use App\Helpers\Qs;
use App\Models\Student;
use App\Models\Staff; // Use your Staff model as the User performing actions
use Illuminate\Auth\Access\HandlesAuthorization; // Use the correct trait

class StudentPolicy
{
    use HandlesAuthorization; // Use the correct trait

    /**
     * Determine whether the staff user can view any models.
     * Used for index pages.
     *
     * @param Staff $staff
     * @return bool
     */
    public function viewAny(Staff $staff): bool
    {
        // Check if the staff has the general permission to view students
        return $staff->can('view students');
    }

    /**
     * Determine whether the staff user can view the specific student model.
     *
     * @param Staff $staff
     * @param Student $student
     * @return bool
     */
    public function view(Staff $staff, Student $student): bool
    {
         if ($staff->hasRole('teacher') && !$staff->can('view all schools data')) {
             $currentSchoolId = $student->enrollments()->where('syear', Qs::getCurrentSchoolYear())->value('school_id');
             return $staff->current_school_id === $currentSchoolId && $staff->can('view students');
         }
        return $staff->can('view students');
    }

    /**
     * Determine whether the staff user can create student models.
     *
     * @param Staff $staff
     * @return bool
     */
    public function create(Staff $staff): bool
    {
        // Check if the staff has the permission to edit/create students
        return $staff->can('edit students');
    }

    /**
     * Determine whether the staff user can update the specific student model.
     *
     * @param Staff $staff
     * @param Student $student
     * @return bool
     */
    public function update(Staff $staff, Student $student): bool
    {
        if ($staff->hasRole('teacher') && !$staff->can('view all schools data')) {
             $currentSchoolId = $student->enrollments()->where('syear', Qs::getCurrentSchoolYear())->value('school_id');
             return $staff->current_school_id === $currentSchoolId && $staff->can('edit students');
        }
        return $staff->can('edit students');
    }

    /**
     * Determine whether the staff user can delete the specific student model.
     *
     * @param Staff $staff
     * @param Student $student
     * @return bool
     */
    public function delete(Staff $staff, Student $student): bool
    {
        // Check if the staff has the permission to edit/delete students.
        // Add safety checks here if needed (e.g., cannot delete if payments exist - though controller handles this now)
        return $staff->can('edit students');
    }

    /**
     * Determine whether the staff user can reset the student's password.
     *
     * @param Staff $staff
     * @param Student $student
     * @return bool
     */
    public function resetPassword(Staff $staff, Student $student): bool
    {
        // Allow if the staff can generally edit students
        return $staff->can('edit students');
    }

    /**
     * Determine whether the user can restore the model.
     * (Add if using Soft Deletes and need restore functionality)
     *
     * @param Staff $staff
     * @param Student $student
     * @return bool
     */
    // public function restore(Staff $staff, Student $student): bool
    // {
    //     return $staff->can('edit students');
    // }

    /**
     * Determine whether the user can permanently delete the model.
     * (Add if using Soft Deletes and need force delete functionality)
     *
     * @param Staff $staff
     * @param Student $student
     * @return bool
     */
    // public function forceDelete(Staff $staff, Student $student): bool
    // {
    //     // Usually restricted to higher roles like admin
    //     return $staff->hasRole('admin') && $staff->can('edit students');
    // }

    /**
     * Determine whether the staff user can manage student enrollments (bulk, etc).
     *
     * @param Staff $staff
     * @return bool
     */
    public function manageEnrollment(Staff $staff): bool
    {
        // Allow if the staff can generally edit students (covers create/update)
        return $staff->can('edit students');
    }

    /**
     * Determine whether the staff user can import students.
     *
     * @param Staff $staff
     * @return bool
     */
    public function importStudents(Staff $staff): bool
    {
        // Allow if the staff can generally edit students
        return $staff->can('edit students');
    }
}
