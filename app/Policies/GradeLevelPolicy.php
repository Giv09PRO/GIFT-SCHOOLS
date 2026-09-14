<?php

namespace App\Policies;

use App\Models\GradeLevel;
use App\Models\Staff; // Staff performs actions
use Illuminate\Auth\Access\HandlesAuthorization;

class GradeLevelPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param Staff $staff
     * @return bool
     */
    public function viewAny(Staff $staff): bool
    {
        // Check if user has permission to view grades
        return $staff->can('view grades');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param Staff $staff
     * @param GradeLevel $gradeLevel
     * @return bool
     */
    public function view(Staff $staff, GradeLevel $gradeLevel): bool
    {
        // Allow viewing details if user can view the list generally
        // Add more specific logic if needed (e.g., only view grades for own school)
        return $staff->can('view grades');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param Staff $staff
     * @return bool
     */
    public function create(Staff $staff): bool
    {
        // Check if user has permission to manage grades
        return $staff->can('manage grades');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param Staff $staff
     * @param GradeLevel $gradeLevel
     * @return bool
     */
    public function update(Staff $staff, GradeLevel $gradeLevel): bool
    {
        // Check if user has permission to manage grades
        return $staff->can('manage grades');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param Staff $staff
     * @param GradeLevel $gradeLevel
     * @return bool
     */
    public function delete(Staff $staff, GradeLevel $gradeLevel): bool
    {
        // Check if user has permission to manage grades
        // Add safety checks? (e.g., cannot delete if students are enrolled?)
        // Consider soft deletes if needed.
        return $staff->can('manage grades');
    }

    /**
     * Determine whether the user can restore the model.
     * (Add if using Soft Deletes)
     * @param Staff $staff
     * @param GradeLevel $gradeLevel
     * @return bool
     */
     public function restore(Staff $staff, GradeLevel $gradeLevel): bool
     {
         return $staff->can('manage grades');
     }

    /**
     * Determine whether the user can permanently delete the model.
     * (Add if using Soft Deletes)
     * @param Staff $staff
     * @param GradeLevel $gradeLevel
     * @return bool
     */
     public function forceDelete(Staff $staff, GradeLevel $gradeLevel): bool
     {
         // Usually restricted to higher roles
         return $staff->hasRole('super admin') && $staff->can('manage grades');
     }
}
