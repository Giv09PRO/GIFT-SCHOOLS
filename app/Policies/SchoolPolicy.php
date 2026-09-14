<?php

namespace App\Policies;

use App\Models\School;
use App\Models\Staff; // Staff performs actions
use Illuminate\Auth\Access\HandlesAuthorization;

class SchoolPolicy
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
        // Check if user has permission to view schools
        return $staff->can('view schools');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param Staff $staff
     * @param School $school
     * @return bool
     */
    public function view(Staff $staff, School $school): bool
    {
        // Allow viewing details if user can view the list generally
        return $staff->can('view schools');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param Staff $staff
     * @return bool
     */
    public function create(Staff $staff): bool
    {
        // Check if user has permission to manage schools
        return $staff->can('manage schools');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param Staff $staff
     * @param School $school
     * @return bool
     */
    public function update(Staff $staff, School $school): bool
    {
        // Check if user has permission to manage schools
        return $staff->can('manage schools');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param Staff $staff
     * @param School $school
     * @return bool
     */
    public function delete(Staff $staff, School $school): bool
    {
        // Check if user has permission to manage schools
        // Add safety checks? (e.g., cannot delete if staff/students are assigned?)
        return $staff->can('manage schools');
    }

    /**
     * Determine whether the user can manage settings for the school.
     *
     * @param Staff $staff
     * @param School $school
     * @return bool
     */
    public function manageSettings(Staff $staff, School $school): bool // *** ADDED METHOD ***
    {
        // Maybe only allow managing settings for one's own assigned school, unless admin/super admin?
        // Example:
         if (!$staff->hasAnyRole(['super admin', 'admin', 'god mode']) && $staff->current_school_id !== $school->id) {
             return false;
         }
        // Check the general permission
        return $staff->can('manage school settings');
    }


    /**
     * Determine whether the user can restore the model.
     * (Add if using Soft Deletes)
     * @param Staff $staff
     * @param School $school
     * @return bool
     */
     public function restore(Staff $staff, School $school): bool
     {
         return $staff->can('manage schools');
     }

    /**
     * Determine whether the user can permanently delete the model.
     * (Add if using Soft Deletes)
     * @param Staff $staff
     * @param School $school
     * @return bool
     */
     public function forceDelete(Staff $staff, School $school): bool
     {
         // Usually restricted to higher roles
         return $staff->hasRole('super admin') && $staff->can('manage schools');
     }
}
