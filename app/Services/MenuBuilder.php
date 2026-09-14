<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;

class MenuBuilder
{
    public function getMenu(): array
    {
        if (Auth::guard('staff')->check()) {
            return $this->staffMenu();
        } elseif (Auth::guard('students')->check()) {
            return $this->studentMenu();
        } elseif (Auth::guard('parents')->check()) {
            return $this->parentMenu();
        }

        return []; // guest menu or fallback
    }

    protected function staffMenu()
    {
        return [
            [
                'text' => 'Dashboard',
                'route' => 'staff.dashboard',
                'icon' => 'fas fa-tachometer-alt',
            ],
            [
                'header' => 'User Management',
            ],
            [
                'text' => 'Staff',
                'route' => 'staff.manage.staff.index',
                'icon' => 'fas fa-users',
                'can' => 'view staff',
            ],
            // ... more items
        ];
    }

    protected function studentMenu()
    {
        return [
            [
                'text' => 'Dashboard',
                'route' => 'student.dashboard',
                'icon' => 'fas fa-home',
            ],
            [
                'text' => 'My Classes',
                'route' => 'student.classes.index',
                'icon' => 'fas fa-book',
            ],
        ];
    }

    protected function parentMenu()
    {
        return [
            [
                'text' => 'Dashboard',
                'route' => 'parent.dashboard',
                'icon' => 'fas fa-home',
            ],
            [
                'text' => 'My Children',
                'route' => 'parent.children.index',
                'icon' => 'fas fa-child',
            ],
        ];
    }
}
