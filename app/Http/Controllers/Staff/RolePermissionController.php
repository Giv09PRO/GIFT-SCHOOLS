<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Exception;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionController extends Controller
{
    use AuthorizesRequests; // Use the authorization trait

    // Define the guard name used by Spatie
    protected string $guardName = 'staff';

    /**
     * Display a listing of the roles.
     *
     * @return View
     * @throws AuthorizationException
     */
    public function index(): View
    {
        // Authorize: Only users who can manage roles/permissions should access this
        $this->authorize('manage roles and permissions');

        // Fetch roles for the specific guard, eager load permissions count
        $roles = Role::where('guard_name', $this->guardName)
            ->withCount('permissions')
            ->orderBy('name')
            ->get();

        // Define headers for AdminLTE Datatable
        $heads = [
            'ID',
            'Role Name',
            'Guard',
            'Permissions Count',
            ['label' => 'Actions', 'no-export' => true, 'width' => 8, 'orderable' => false],
        ];

        // Prepare data for the datatable
        $data = [];
        $currentUser = Auth::user(); // Needed? Maybe not directly here

        foreach ($roles as $role) {
            // Generate URLs for actions
            // Use $role->id directly as Spatie models use standard integer IDs
            $editUrl = route('staff.roles.edit', $role->id);
            $destroyUrl = route('staff.roles.destroy', $role->id);

            $actionsHtml = "<nobr>";
            // Edit button (always available if user can manage roles)
            $actionsHtml .= "<a href='{$editUrl}' class='btn btn-xs btn-info' title='Edit Role & Permissions'><i class='fas fa-edit'></i></a> ";

            // Prevent deletion of core roles like 'god mode', 'super admin'
            $coreRoles = ['god mode', 'super admin', 'admin']; // Add other essential roles if needed
            if (!in_array($role->name, $coreRoles)) {
                // Check if any users are assigned this role before allowing deletion
                // $userCount = DB::table('model_has_roles')->where('role_id', $role->id)->count();
                // if ($userCount === 0) {
                $actionsHtml .= "<form action='{$destroyUrl}' method='POST' class='d-inline' onsubmit='return confirm(\"Are you sure you want to delete the role `{$role->name}`? This cannot be undone.\");'>"
                    . csrf_field() . method_field('DELETE')
                    . "<button type='submit' class='btn btn-xs btn-danger' title='Delete Role'><i class='fas fa-trash'></i></button></form>";
                // } else {
                //    $actionsHtml .= "<button type='button' class='btn btn-xs btn-danger disabled' title='Cannot delete: Role assigned to users'><i class='fas fa-trash'></i></button>";
                // }
            } else {
                $actionsHtml .= "<button type='button' class='btn btn-xs btn-secondary disabled' title='Cannot delete core role'><i class='fas fa-trash'></i></button>";
            }
            $actionsHtml .= "</nobr>";

            $data[] = [
                $role->id,
                e($role->name),
                e($role->guard_name),
                $role->permissions_count, // From withCount()
                $actionsHtml,
            ];
        }

        $config = [
            'data' => $data,
            'order' => [[1, 'asc']], // Default sort by Role Name
            'columns' => [ null, null, null, null, ['orderable' => false, 'searchable' => false, 'className' => 'text-center'] ],
            'paging' => true, 'lengthChange' => true, 'searching' => true, 'info' => true,
            'responsive' => true, 'autoWidth' => false,
        ];

        Log::info('Roles index viewed', ['user_id' => Auth::id()]);

        // Adjust view path if necessary
        return view('pages.staff.roles.index', compact('heads', 'config'));
    }

    /**
     * Show the form for creating a new role.
     *
     * @return View
     * @throws AuthorizationException
     */
    public function create(): View
    {
        $this->authorize('manage roles and permissions');

        // Fetch all available permissions for the staff guard
        $permissions = Permission::where('guard_name', $this->guardName)->orderBy('name')->get();

        Log::info('Create role form viewed', ['user_id' => Auth::id()]);
        // Adjust view path if necessary
        return view('pages.staff.roles.create', compact('permissions'));
    }

    /**
     * Store a newly created role in storage.
     *
     * @param Request $request
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage roles and permissions');

        $validated = $request->validate([
            // Ensure role name is unique for the guard
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->where('guard_name', $this->guardName)],
            'permissions' => 'nullable|array', // Array of permission names or IDs
            'permissions.*' => ['required', 'string', Rule::exists('permissions', 'name')->where('guard_name', $this->guardName)], // Validate each permission exists
        ]);

        DB::beginTransaction();
        try {
            // Create the role
            $role = Role::create([
                'name' => strtolower(str_replace(' ', '_', $validated['name'])), // Sanitize name (e.g., lowercase, underscore)
                'guard_name' => $this->guardName
            ]);

            // Assign permissions if provided
            if (!empty($validated['permissions'])) {
                // Use permission names directly with syncPermissions
                $role->syncPermissions($validated['permissions']);
            }

            DB::commit();
            app()[PermissionRegistrar::class]->forgetCachedPermissions(); // Clear cache

            Log::info('Role created successfully', ['role_id' => $role->id, 'name' => $role->name, 'creator_id' => Auth::id()]);
            return redirect()->route('staff.roles.index')->with('success', 'Role created successfully.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creating role', ['user_id' => Auth::id(), 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->withInput()->with('error', 'Failed to create role. Error: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified role and its permissions.
     *
     * @param Role $role Automatically resolved by ID
     * @return View
     * @throws AuthorizationException
     */
    public function edit(Role $role): View // Use Route Model Binding for Role
    {
        $this->authorize('manage roles and permissions');

        // Ensure the role belongs to the correct guard (optional safety check)
        if ($role->guard_name !== $this->guardName) {
            abort(403, 'Cannot edit role from a different guard.');
        }

        // Fetch all available permissions for the staff guard
        $permissions = Permission::where('guard_name', $this->guardName)->orderBy('name')->get();
        // Get the names of permissions currently assigned to this role
        $rolePermissions = $role->permissions->pluck('name')->toArray();

        Log::info('Edit role form viewed', ['role_id' => $role->id, 'name' => $role->name, 'editor_id' => Auth::id()]);
        // Adjust view path if necessary
        return view('pages.staff.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    /**
     * Update the specified role and its permissions in storage.
     *
     * @param Request $request
     * @param Role $role Automatically resolved by ID
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function update(Request $request, Role $role): RedirectResponse // Use Route Model Binding for Role
    {
        $this->authorize('manage roles and permissions');

        // Ensure the role belongs to the correct guard
        if ($role->guard_name !== $this->guardName) {
            abort(403, 'Cannot update role from a different guard.');
        }

        // Prevent renaming core roles
        $coreRoles = ['god mode', 'super admin', 'admin'];
        $isCoreRole = in_array($role->name, $coreRoles);

        $validated = $request->validate([
            // Allow name update only if it's not a core role
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('roles', 'name')->where('guard_name', $this->guardName)->ignore($role->id)
            ],
            'permissions' => 'nullable|array',
            'permissions.*' => ['required', 'string', Rule::exists('permissions', 'name')->where('guard_name', $this->guardName)],
        ]);

        DB::beginTransaction();
        try {
            // Update role name only if not a core role and if changed
            if (!$isCoreRole && $role->name !== $validated['name']) {
                $role->name = strtolower(str_replace(' ', '_', $validated['name'])); // Sanitize name
                $role->save();
            }

            // Sync permissions (this handles adding/removing permissions)
            $permissionsToSync = $validated['permissions'] ?? [];
            $role->syncPermissions($permissionsToSync);

            DB::commit();
            app()[PermissionRegistrar::class]->forgetCachedPermissions(); // Clear cache

            Log::info('Role updated successfully', ['role_id' => $role->id, 'name' => $role->name, 'updater_id' => Auth::id()]);
            return redirect()->route('staff.roles.index')->with('success', 'Role updated successfully.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating role', ['role_id' => $role->id, 'user_id' => Auth::id(), 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->withInput()->with('error', 'Failed to update role. Error: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified role from storage.
     *
     * @param Role $role Automatically resolved by ID
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(Role $role): RedirectResponse // Use Route Model Binding for Role
    {
        $this->authorize('manage roles and permissions');

        // Prevent deletion of core roles
        $coreRoles = ['god mode', 'super admin', 'admin'];
        if (in_array($role->name, $coreRoles)) {
            Log::warning('Attempted to delete core role', ['role_id' => $role->id, 'name' => $role->name, 'user_id' => Auth::id()]);
            return redirect()->route('staff.roles.index')->with('error', 'Cannot delete core system roles.');
        }

        // Ensure the role belongs to the correct guard
        if ($role->guard_name !== $this->guardName) {
            abort(403, 'Cannot delete role from a different guard.');
        }

        // ** Safety Check: Check if any users are assigned this role **
        $userCount = DB::table('model_has_roles')->where('role_id', $role->id)->count();
        if ($userCount > 0) {
            Log::warning('Attempted to delete role assigned to users', ['role_id' => $role->id, 'name' => $role->name, 'user_count' => $userCount, 'user_id' => Auth::id()]);
            return redirect()->route('staff.roles.index')->with('error', "Cannot delete role '{$role->name}' because it is assigned to {$userCount} user(s). Please reassign users first.");
        }

        DB::beginTransaction();
        try {
            $roleName = $role->name; // For logging
            // Permissions are detached automatically by Spatie on role deletion

            $role->delete();

            DB::commit();
            app()[PermissionRegistrar::class]->forgetCachedPermissions(); // Clear cache

            Log::warning('Role deleted', ['deleted_role_name' => $roleName, 'deleter_id' => Auth::id()]);
            return redirect()->route('staff.roles.index')->with('success', 'Role deleted successfully.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error deleting role', ['role_id' => $role->id, 'user_id' => Auth::id(), 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->route('staff.roles.index')->with('error', 'Failed to delete role. Error: ' . $e->getMessage());
        }
    }
}

