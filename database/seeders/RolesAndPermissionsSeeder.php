<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use App\Models\Staff; // Your Staff model
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // --- Define Permissions for the 'staff' guard ---
        $permissions = [
            // Student Management
            'view students',
            'edit students', // Includes create, update, delete, reset password, enroll
            // Staff Management
            'view staff', // Added permission to view staff list/profiles
            'manage staff', // Includes create, update, delete staff (excluding roles)
            'reset staff password', // *** ADDED specific permission ***
            'edit own profile',
            // Parent Management
            'view parents',
            'manage parents', // Includes create, update, delete, link students
            // Financial Management
            'view finances',
            'manage finances', // Includes managing fees/payments
            // Academic Management
            'view grades', // *** Ensure this permission is defined ***
            'manage grades', // Includes creating/editing grade levels & assigning grades to students
            'view attendance',
            'manage attendance',
            // School Management
            'view schools',
            'manage schools', // Includes create, update, delete
            // Settings & Overrides
            'manage school settings', // Manage specific school settings
            'view all schools data', // For admin/superadmin override
            // Reporting
            'generate reports',
            // --- Higher Level Permissions ---
            'manage system settings', // Manage application-wide settings
            'manage roles and permissions', // Manage RBAC setup
            'perform bulk operations', // Student import, rollover etc.
            'force delete records', // Potentially bypass soft deletes
            'perform school rollover', // *** ADDED Rollover Permission ***
        ];

        // Get all existing permissions for the staff guard before creating new ones
        $existingPermissions = Permission::where('guard_name', 'staff')->pluck('name')->toArray();
        $this->command->info('Checking permissions...');
        foreach ($permissions as $permission) {
            // Create permission only if it doesn't exist
            if (!in_array($permission, $existingPermissions)) {
                Permission::create(['name' => $permission, 'guard_name' => 'staff']);
                $this->command->info("Permission '{$permission}' created.");
            } else {
                $this->command->warn("Permission '{$permission}' already exists.");
            }
        }
        $this->command->info('Permission check complete.');

        // --- Define Roles for the 'staff' guard ---
        // Use firstOrCreate to avoid errors if seeder runs multiple times
        $this->command->info('Creating/finding roles...');
        $godModeRole = Role::firstOrCreate(['name' => 'god mode', 'guard_name' => 'staff']);
        $superAdminRole = Role::firstOrCreate(['name' => 'super admin', 'guard_name' => 'staff']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'staff']); // Added Admin Role
        $principalRole = Role::firstOrCreate(['name' => 'principal', 'guard_name' => 'staff']);
        $teacherRole = Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'staff']);
        $accountantRole = Role::firstOrCreate(['name' => 'accountant', 'guard_name' => 'staff']);
        $officeStaffRole = Role::firstOrCreate(['name' => 'office staff', 'guard_name' => 'staff']);
        $this->command->info('Roles created/found.');

        // --- Assign Permissions to Roles ---

        $allStaffPermissions = Permission::where('guard_name', 'staff')->get();

        // God Mode gets absolutely all permissions
        $this->command->info("Assigning all permissions to 'god mode' role...");
        $godModeRole->syncPermissions($allStaffPermissions);

        // Super Admin gets most permissions (including rollover)
        $this->command->info("Assigning permissions to 'super admin' role...");
        $superAdminPermissions = $allStaffPermissions->reject(function ($permission) {
            // Exclude only the most sensitive (maybe roles/permissions management and force delete)
            return in_array($permission->name, [
                'manage roles and permissions',
                'force delete records'
            ]);
        });
        $superAdminRole->syncPermissions($superAdminPermissions); // Super admin CAN perform rollover

        // Admin gets standard administrative permissions (NO rollover by default)
        $this->command->info("Assigning permissions to 'admin' role...");
        $adminRole->syncPermissions([
            'view students', 'edit students',
            'view staff', 'manage staff',
            'reset staff password',
            'edit own profile',
            'view parents', 'manage parents',
            'view finances', // May or may not manage finances depending on structure
            'view grades', // *** ADDED assignment ***
            'manage grades',
            'view attendance', 'manage attendance',
            'manage school settings',
            'view all schools data',
            'generate reports',
            'perform bulk operations',
            'view schools', // Added school permissions
            'manage schools',// Added school permissions
        ]);

        // Principal Permissions (Example - NO rollover by default)
        $this->command->info("Assigning permissions to 'principal' role...");
        $principalRole->syncPermissions([
            'view students', 'edit students',
            'view staff',
            'edit own profile',
            'view parents', 'manage parents',
            'view finances',
            'view grades', // *** ADDED assignment ***
            'manage grades',
            'view attendance', 'manage attendance',
            'manage school settings',
            'view all schools data',
            'generate reports',
            'perform bulk operations',
            'view schools', // Added school permissions
            // 'manage schools', // Decide if principals manage schools
        ]);

        // Teacher Permissions (Example)
        $this->command->info("Assigning permissions to 'teacher' role...");
        $teacherRole->syncPermissions([
            'view students', // View students (policy might restrict to own class/school)
            'edit own profile',
            'view parents', // View parents (policy might restrict)
            'view grades', // *** ADDED assignment ***
            'manage grades', // Teachers manage grades for their classes
            'view attendance', 'manage attendance', // Teachers manage attendance for their classes
        ]);

        // Accountant Permissions (Example)
        $this->command->info("Assigning permissions to 'accountant' role...");
        $accountantRole->syncPermissions([
            'edit own profile',
            'view finances', 'manage finances',
            'view parents', // Might need to view parents for billing
            'generate reports', // Financial reports
        ]);

        // Office Staff Permissions (Example)
        $this->command->info("Assigning permissions to 'office staff' role...");
        $officeStaffRole->syncPermissions([
            'view students', 'edit students', // May handle student registration/updates
            'view staff', // View staff directory
            'edit own profile',
            'view parents', 'manage parents', // May handle parent communication/accounts
            'view attendance', // May view attendance records
            'generate reports', // Basic reports
            'view schools', // Can view school list/details
            'view grades', // *** ADDED assignment ***
        ]);
        $this->command->info('Permissions assigned to roles.');


        // --- Create Default Users --- (No changes needed here)
        // God Mode User
        $this->command->info('Creating default god mode user...');
        $godModeUser = Staff::firstOrCreate(
            ['username' => 'godmode'],
            [
                'first_name' => 'God', 'last_name' => 'Mode', 'email' => 'godmode@example.com',
                'password' => 'password', 'profile' => 'god mode', 'syear' => now()->year, 'current_school_id' => null,
            ]
        );
        $godModeUser->syncRoles([$godModeRole]);
        $this->command->info('God Mode user created/found and assigned role.');

        // Super Admin User
        $this->command->info('Creating default super admin user...');
        $superAdminUser = Staff::firstOrCreate(
            ['username' => 'superadmin'],
            [
                'first_name' => 'Super', 'last_name' => 'Admin', 'email' => 'superadmin@example.com',
                'password' => 'password', 'profile' => 'super admin', 'syear' => now()->year, 'current_school_id' => null,
            ]
        );
        $superAdminUser->syncRoles([$superAdminRole]);
        $this->command->info('Super Admin user created/found and assigned role.');

        // Admin User
        $this->command->info('Creating default admin user...');
        $adminUser = Staff::firstOrCreate(
            ['username' => 'admin'],
            [
                'first_name' => 'Admin', 'last_name' => 'User', 'email' => 'admin@example.com',
                'password' => 'password', 'profile' => 'admin', 'syear' => now()->year, 'current_school_id' => null, // Assign a school ID if applicable
            ]
        );
        $adminUser->syncRoles([$adminRole]);
        $this->command->info('Admin user created/found and assigned role.');

        $this->command->info('Roles and permissions seeding process completed.');
    }
}
