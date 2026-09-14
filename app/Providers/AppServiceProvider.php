<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // You can register services here if needed.
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Define gates based on profile column for role-based access
        $roles = [
            'super_admin',
            'admin',
            'accountant',
            'god_mode',
            'student',
            'parent',
        ];

        foreach ($roles as $role) {
            Gate::define("view-$role", function ($user) use ($role) {
                return $user->profile === $role;
            });
        }

        // Dynamically override dashboard route depending on guard
        View::composer('adminlte::page', function ($view) {
            $menu = config('adminlte.menu');
    
            // Override the dashboard menu item URL dynamically
            foreach ($menu as &$item) {
                if (isset($item['text']) && $item['text'] === 'Dashboard') {
                    if (Auth::guard('staff')->check()) {
                        $item['route'] = 'staff.dashboard';
                        unset($item['url']);
                    } elseif (Auth::guard('students')->check()) {
                        $item['route'] = 'student.dashboard';
                        unset($item['url']);
                    } elseif (Auth::guard('parents')->check()) {
                        $item['route'] = 'parent.dashboard';
                        unset($item['url']);
                    } else {
                        $item['route'] = 'login';
                        unset($item['url']);
                    }
                    break;
                }
            }
    
            // Pass the modified menu back to config before the view renders
            config(['adminlte.menu' => $menu]);
        });
    }
}
