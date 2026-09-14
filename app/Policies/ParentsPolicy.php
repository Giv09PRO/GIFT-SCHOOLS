<?php

namespace App\Policies;

use App\Models\Parents;
use App\Models\Staff; // Staff is the user performing actions
use Illuminate\Auth\Access\HandlesAuthorization;

class ParentsPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the staff user can view any parent models.
     *
     * @param Staff $staff
     * @return bool
     */
    public function viewAny(Staff $staff): bool
    {
        // Check if the staff has the permission to view parents
        return $staff->can('view parents');
    }

    /**
     * Determine whether the staff user can view the specific parent model.
     *
     * @param Staff $staff
     * @param Parents $parent
     * @return bool
     */
    public function view(Staff $staff, Parents $parent): bool
    {
        // Allow if the staff can generally view parents.
        // Add more specific logic if needed (e.g., view only parents of students in their school)
        return $staff->can('view parents');
    }

    /**
     * Determine whether the staff user can create parent models.
     *
     * @param Staff $staff
     * @return bool
     */
    public function create(Staff $staff): bool
    {
        // Check if the staff has the permission to manage parents
        return $staff->can('manage parents');
    }

    /**
     * Determine whether the staff user can update the specific parent model.
     *
     * @param Staff $staff
     * @param Parents $parent
     * @return bool
     */
    public function update(Staff $staff, Parents $parent): bool
    {
        // Check if the staff has the permission to manage parents
        // Add specific logic if needed (e.g., only admins can change certain fields)
        return $staff->can('manage parents');
    }

    /**
     * Determine whether the staff user can delete the specific parent model.
     *
     * @param Staff $staff
     * @param Parents $parent
     * @return bool
     */
    public function delete(Staff $staff, Parents $parent): bool
    {
        // Check if the staff has the permission to manage parents
        return $staff->can('manage parents');
    }

    /**
     * Determine whether the staff user can reset the parent's password.
     * This might be included in 'update' or be a separate check.
     *
     * @param Staff $staff
     * @param Parents $parent
     * @return bool
     */
    public function resetPassword(Staff $staff, Parents $parent): bool
    {
        // Allow if the staff can manage parents (which implies update ability)
        return $staff->can('manage parents');
    }

    /**
     * Determine whether the user can restore the model.
     * (Add if using Soft Deletes)
     *
     * @param Staff $staff
     * @param Parents $parent
     * @return bool
     */
     public function restore(Staff $staff, Parents $parent): bool
     {
         return $staff->can('manage parents');
     }

    /**
     * Determine whether the user can permanently delete the model.
     * (Add if using Soft Deletes)
     *
     * @param Staff $staff
     * @param Parents $parent
     * @return bool
     */
     public function forceDelete(Staff $staff, Parents $parent): bool
     {
         // Usually restricted to higher roles
         return $staff->hasRole('admin') && $staff->can('manage parents');
     }
}
