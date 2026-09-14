<?php

namespace App\Policies;

use App\Models\Staff;
use Illuminate\Auth\Access\HandlesAuthorization; // Use the correct trait

class StaffPolicy
{
    use HandlesAuthorization; // Use the correct trait

    /**
     * Determine whether the user can view any models (staff list).
     *
     * @param Staff $staff The authenticated staff member
     * @return bool
     */
    public function viewAny(Staff $staff): bool
    {
        // Check the specific permission defined in the seeder
        return $staff->can('view staff');
    }


    /**
     * Determine whether the user can view the specific staff model.
     *
     * @param Staff $staff The authenticated staff member
     * @param Staff $targetStaff The staff profile being viewed
     * @return bool
     */
    public function view(Staff $staff, Staff $targetStaff): bool
    {
        // User can view their own profile OR if they have 'view staff' permission
        // Use getKey() for reliable comparison
        return $staff->getKey() === $targetStaff->getKey() || $staff->can('view staff');
    }

    /**
     * Determine whether the user can create staff models.
     *
     * @param Staff $staff The authenticated staff member
     * @return bool
     */
    public function create(Staff $staff): bool
    {
        // Requires the permission to manage staff
        return $staff->can('manage staff');
    }

    /**
     * Determine whether the user can update the specific staff model.
     *
     * @param Staff $staff The authenticated staff member
     * @param Staff $targetStaff The staff profile being updated
     * @return bool
     */
    public function update(Staff $staff, Staff $targetStaff): bool
    {
        // User can update their own profile if they have 'edit own profile' permission
        if ($staff->getKey() === $targetStaff->getKey()) {
            return $staff->can('edit own profile');
        }

        // Otherwise, user needs 'manage staff' permission to update others
        // Note: This does NOT cover password changes or role changes here by default
        return $staff->can('manage staff');
    }

    /**
     * Determine whether the user can delete the specific staff model.
     *
     * @param Staff $staff The authenticated staff member
     * @param Staff $targetStaff The staff profile being deleted
     * @return bool
     */
    public function delete(Staff $staff, Staff $targetStaff): bool
    {
        // Prevent users from deleting themselves
        if ($staff->getKey() === $targetStaff->getKey()) {
            return false;
        }

        // Requires the permission to manage staff
        return $staff->can('manage staff');
    }

    /**
     * Determine whether the user can manage roles/permissions for staff.
     *
     * @param Staff $staff The authenticated staff member
     * @param Staff $targetStaff The staff profile whose roles are being managed
     * @return bool
     */
    public function manageRoles(Staff $staff, Staff $targetStaff): bool
    {
        // Requires the 'manage roles and permissions' permission
        return $staff->can('manage roles and permissions');
    }

    /**
     * Determine whether the user can reset the target staff member's password.
     *
     * @param Staff $staff The authenticated staff member
     * @param Staff $targetStaff The staff profile whose password is being reset
     * @return bool
     */
    public function resetPassword(Staff $staff, Staff $targetStaff): bool // *** ADDED METHOD ***
    {
        // Prevent users from resetting their own password via this admin function
        if ($staff->getKey() === $targetStaff->getKey()) {
            return false;
        }
        // Requires the 'reset staff password' permission
        return $staff->can('reset staff password');
    }


    /**
     * Determine whether the user can restore the model.
     * (Add if using Soft Deletes)
     *
     * @param Staff $staff
     * @param Staff $targetStaff
     * @return bool
     */
    public function restore(Staff $staff, Staff $targetStaff): bool
    {
        // Requires the permission to manage staff
        return $staff->can('manage staff');
    }

    /**
     * Determine whether the user can permanently delete the model.
     * (Add if using Soft Deletes)
     *
     * @param Staff $staff
     * @param Staff $targetStaff
     * @return bool
     */
    public function forceDelete(Staff $staff, Staff $targetStaff): bool
    {
        // Prevent users from force deleting themselves
        if ($staff->getKey() === $targetStaff->getKey()) {
            return false;
        }
        // Requires the 'force delete records' permission
        return $staff->can('force delete records');
    }
}
