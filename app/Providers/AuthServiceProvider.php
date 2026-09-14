<?php

namespace App\Providers;

// Use the correct Staff model
use App\Models\Staff;
use App\Models\Student;
use App\Models\Parents; // Corrected namespace if needed
use App\Policies\StudentPolicy;
use App\Policies\StaffPolicy; // Assuming you have this policy
use App\Policies\ParentsPolicy; // Assuming you have this policy
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
// No need to import Spatie\Permission\Models\Permission here unless creating them

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Student::class => StudentPolicy::class,
        Staff::class => StaffPolicy::class,
        Parents::class => ParentsPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // --- Define Gates using Spatie Permissions ---
        // --- Student Management ---
        Gate::define('view-students', function (Staff $staff) {
            return $staff->can('view students');
        });
        Gate::define('edit-students', function (Staff $staff) {
            return $staff->can('edit students');
        });

        // --- Staff Management ---
        Gate::define('view-staff', function (Staff $staff) { // Added Gate
            return $staff->can('view staff');
        });
        Gate::define('manage-staff', function (Staff $staff) {
            return $staff->can('manage staff');
        });
        Gate::define('edit-own-profile', function (Staff $staff) {
            return $staff->can('edit own profile');
        });

        // --- Parent Management ---
        Gate::define('view-parents', function (Staff $staff) {
            return $staff->can('view parents');
        });
        Gate::define('manage-parents', function (Staff $staff) {
            return $staff->can('manage parents');
        });

        // --- Financial Management ---
        Gate::define('view-finances', function (Staff $staff) {
            return $staff->can('view finances');
        });
        Gate::define('manage-finances', function (Staff $staff) { // Added Gate
            return $staff->can('manage finances');
        });

        // --- Academic Management ---
        Gate::define('manage-grades', function (Staff $staff) {
            return $staff->can('manage grades');
        });
        Gate::define('view-attendance', function (Staff $staff) {
            return $staff->can('view attendance');
        });
        Gate::define('manage-attendance', function (Staff $staff) {
            return $staff->can('manage attendance');
        });

        // --- Settings & Overrides ---
        Gate::define('manage-school-settings', function (Staff $staff) {
            return $staff->can('manage school settings');
        });
        Gate::define('view-all-schools-data', function (Staff $staff) {
            return $staff->can('view all schools data');
        });
        Gate::define('manage-system-settings', function (Staff $staff) { // Added Gate
            return $staff->can('manage system settings');
        });

        // --- Reporting ---
        Gate::define('generate-reports', function (Staff $staff) {
            return $staff->can('generate reports');
        });

        // --- Higher Level Permissions ---
        Gate::define('manage-roles-and-permissions', function (Staff $staff) { // Added Gate
            return $staff->can('manage roles and permissions');
        });
        Gate::define('perform-bulk-operations', function (Staff $staff) { // Added Gate
            return $staff->can('perform bulk operations');
        });
        Gate::define('force-delete-records', function (Staff $staff) { // Added Gate
            return $staff->can('force delete records');
        });
        Gate::define('view results', function (Staff $staff){
            return $staff->can('view results');
        });
        Gate::define('view exams', function (Staff $staff){
            return $staff->can('view exams');
        });
        Gate::define('manage exams', function (Staff $staff){
            return $staff->can('manage exams');
        });


        // --- Implicitly Grant "God Mode" Access ---
        // This runs *before* other Gate checks. If the user has the 'god mode' role,
        // they are granted all permissions automatically, bypassing specific checks.
        Gate::before(function (Staff $staff, $ability) {
            // Ensure the role name 'god mode' matches exactly what's in your roles table.
            if ($staff->hasRole('god mode')) {
                return true; // Grant access to all abilities for god mode role
            }
            // Return null to let the specific Gate/Policy check proceed for other roles
            return null;
        });


        // --- End of Gate Definitions ---
    }
}
