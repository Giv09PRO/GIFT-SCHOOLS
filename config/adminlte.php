<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Title
    |--------------------------------------------------------------------------
    |
    | Here you can change the default title of your admin panel.
    |
    | For detailed instructions you can look the title section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'title' => 'School System',
    'title_prefix' => 'SIMS',
    'title_postfix' => '',

    /*
    |--------------------------------------------------------------------------
    | Favicon
    |--------------------------------------------------------------------------
    |
    | Here you can activate the favicon.
    |
    | For detailed instructions you can look the favicon section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'use_ico_only' => true,
    'use_full_favicon' => true,

    /*
    |--------------------------------------------------------------------------
    | Google Fonts
    |--------------------------------------------------------------------------
    |
    | Here you can allow or not the use of external google fonts. Disabling the
    | google fonts may be useful if your admin panel internet access is
    | restricted somehow.
    |
    | For detailed instructions you can look the Google fonts section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'google_fonts' => [
        'allowed' => false,
    ],

'logo' => '<b>Gift</b>Schools',
'logo_img' => 'favicon.svg', // use a larger image if available
'logo_img_class' => 'brand-image elevation-3 w-12 h-12', // added size classes
'logo_img_xl' => null,
'logo_img_xl_class' => 'brand-image-xl w-16 h-16', // optional for larger screens
'logo_img_alt' => 'School System Logo',



    /*
    |--------------------------------------------------------------------------
    | Authentication Logo
    |--------------------------------------------------------------------------
    |
    | Here you can set up an alternative logo to use on your login and register
    | screens. When disabled, the admin panel logo will be used instead.
    |
    | For detailed instructions you can look the auth logo section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'auth_logo' => [
        'enabled' => true, // Set to true to use a custom auth logo
        'img' => [
            'path' => 'favicon.svg',
            'alt' => 'Auth Logo',
            'class' => '',
            'width' => 100,
            'height' => 120,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Preloader Animation
    |--------------------------------------------------------------------------
    |
    | Here you can change the preloader animation configuration. Currently, two
    | modes are supported: 'fullscreen' for a fullscreen preloader animation
    | and 'cwrapper' to attach the preloader animation into the content-wrapper
    | element and avoid overlapping it with the sidebars and the top navbar.
    |
    | For detailed instructions you can look the preloader section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'preloader' => [
        'enabled' => true, // Set to false to disable preloader
        'mode' => 'fullscreen',
        'img' => [
            'path' => 'favicon.svg', // Adjust path
            'alt' => 'AdminLTE Preloader Image',
            'effect' => 'animation__shake',
            'width' => 200,
            'height' => 200,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Menu
    |--------------------------------------------------------------------------
    |
    | Here you can activate and change the user menu.
    | Note: This refers to the TOP RIGHT user menu, not the sidebar user panel.
    |
    | For detailed instructions you can look the user menu section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'usermenu_enabled' => true, // Enable the top right user menu
    'usermenu_header' => true, // Show header with username in dropdown
    'usermenu_header_class' => 'bg-primary', // Background color for header
    'usermenu_image' => false, // Show user image in dropdown header (needs adminlte_image() method on User model)
    'usermenu_desc' => false, // Show user description in dropdown header (needs adminlte_desc() method on User model)
    'usermenu_profile_url' => false, // Enable link to profile (uses 'profile_url' config below or 'staff.profile.show' route)

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    |
    | Here we change the layout of your admin panel.
    |
    | For detailed instructions you can look the layout section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'layout_topnav' => null, // Set to true for top navigation layout
    'layout_boxed' => null, // Set to true for boxed layout
    'layout_fixed_sidebar' => true, // Set true to fix the sidebar
    'layout_fixed_navbar' => true, // Set true or an array for fixed navbar options
    'layout_fixed_footer' => null, // Set true to fix the footer
    'layout_dark_mode' => false, // Set true to enable dark mode by default

    /*
    |--------------------------------------------------------------------------
    | Authentication Views Classes
    |--------------------------------------------------------------------------
    |
    | Here you can change the look and behavior of the authentication views.
    |
    | For detailed instructions you can look the auth classes section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'classes_auth_card' => 'card-outline card-primary',
    'classes_auth_header' => '',
    'classes_auth_body' => '',
    'classes_auth_footer' => 'text-center', // Center footer links
    'classes_auth_icon' => '',
    'classes_auth_btn' => 'btn-flat btn-primary',

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Classes
    |--------------------------------------------------------------------------
    |
    | Here you can change the look and behavior of the admin panel.
    |
    | For detailed instructions you can look the admin panel classes here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'classes_body' => '',
    'classes_brand' => '',
    'classes_brand_text' => '',
    'classes_content_wrapper' => '',
    'classes_content_header' => '',
    'classes_content' => '',
    'classes_sidebar' => 'sidebar-dark-primary elevation-4', // Sidebar style
    'classes_sidebar_nav' => '',
    'classes_topnav' => 'navbar-dark navbar-dark', // Top navbar style
    'classes_topnav_nav' => 'navbar-expand',
    'classes_topnav_container' => 'container', // Use container for topnav layout

    /*
    |--------------------------------------------------------------------------
    | Sidebar
    |--------------------------------------------------------------------------
    |
    | Here we can modify the sidebar of the admin panel.
    |
    | For detailed instructions you can look the sidebar section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'sidebar_mini' => 'lg', // Enable sidebar mini mode on larger screens
    'sidebar_collapse' => false, // Start with sidebar collapsed?
    'sidebar_collapse_auto_size' => false,
    'sidebar_collapse_remember' => false, // Remember collapsed state?
    'sidebar_collapse_remember_no_transition' => true,
    'sidebar_scrollbar_theme' => 'os-theme-light',
    'sidebar_scrollbar_auto_hide' => 'l',
    'sidebar_nav_accordion' => true, // Enable accordion effect
    'sidebar_nav_animation_speed' => 300,

    /*
    |--------------------------------------------------------------------------
    | Control Sidebar (Right Sidebar)
    |--------------------------------------------------------------------------
    |
    | Here we can modify the right sidebar aka control sidebar of the admin panel.
    |
    | For detailed instructions you can look the right sidebar section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'right_sidebar' => false, // Set to true to enable right sidebar
    'right_sidebar_icon' => 'fas fa-cogs',
    'right_sidebar_theme' => 'dark',
    'right_sidebar_slide' => true,
    'right_sidebar_push' => true,
    'right_sidebar_scrollbar_theme' => 'os-theme-light',
    'right_sidebar_scrollbar_auto_hide' => 'l',

    /*
    |--------------------------------------------------------------------------
    | URLs
    |--------------------------------------------------------------------------
    |
    | Here we can modify the url settings of the admin panel.
    |
    | For detailed instructions you can look the urls section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'use_route_url' => true, // Use route() helper instead of url()
    'dashboard_url' => 'dashboard',
    'logout_url' => 'logout', // Named route for logout
    'login_url' => 'login', // Named route for login
    'register_url' => 'register', // Named route for register (set to false if no registration)
    'password_reset_url' => 'password.request', // Named route for password reset request form
    'password_email_url' => 'password.email', // Named route for sending reset link
    'profile_url' => 'staff.profile.show', // Named route for user profile page
    'disable_darkmode_routes' => false, // Keep dark mode routes enabled

    /*
    |--------------------------------------------------------------------------
    | Laravel Asset Bundling
    |--------------------------------------------------------------------------
    |
    | Here we can enable the Laravel Asset Bundling option for the admin panel.
    | Currently, the next modes are supported: 'mix', 'vite' and 'vite_js_only'.
    | When using 'vite_js_only', it's expected that your CSS is imported using
    | JavaScript. Typically, in your application's 'resources/js/app.js' file.
    | If you are not using any of these, leave it as 'false'.
    |
    | For detailed instructions you can look the asset bundling section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Other-Configuration
    |
    */

    'laravel_asset_bundling' => false, // Set to 'vite' or 'mix' if you use them
    'laravel_css_path' => 'css/app.css', // Path for bundled CSS (if using mix/vite)
    'laravel_js_path' => 'js/app.js', // Path for bundled JS (if using mix/vite)

    /*
    |--------------------------------------------------------------------------
    | Menu Items
    |--------------------------------------------------------------------------
    |
    | Defines the menu items displayed in the sidebar and top navbar.
    | Uses the 'can' key for permission checks based on Spatie permissions.
    |
    */
    'menu' => [
    // Navbar items:
    [
        'topnav_right' => true,
        'type' => 'fullscreen-widget',
    ],
    // Sidebar User Panel Widget - Displays logged-in user's name/image
    [
        'type' => 'user-panel', // Uses Auth::user() automatically
    ],
    // Dashboard Link
    [
        'text' => 'Dashboard',
        'route' => 'dashboard', // Make sure this route exists per guard
        'icon' => 'fas fa-tachometer-alt', // Classic dashboard icon
    ],
    // School Management Section
    [
        'header' => 'School Management',
        'can' => ['view schools', 'manage school settings', 'perform school rollover'], // Show if any of these permissions
    ],
    [
        'text' => 'Schools',
        'route' => 'staff.schools.index',
        'icon' => 'fas fa-school', // Clear education icon
        'can' => 'view schools',
    ],
    [
        'text' => 'School Year Rollover',
        'route' => 'staff.rollover.form',
        'icon' => 'fas fa-calendar-alt', // Calendar icon for rollover
        'can' => 'perform school rollover',
    ],
    // User Management Section
    [
        'header' => 'User Management',
        'can' => ['view staff', 'view students', 'view parents', 'manage roles and permissions'],
    ],
    [
        'text' => 'Staff',
        'route' => 'staff.manage.staff.index',
        'icon' => 'fas fa-users', // Group icon for staff
        'can' => 'view staff',
    ],
    [
        'text' => 'Students',
        'route' => 'staff.students.index',
        'icon' => 'fas fa-user-graduate', // Student icon
        'can' => 'view students',
    ],
    [
        'text' => 'Unassigned Students',
        'route' => 'staff.grades.unassigned',
        'icon' => 'fas fa-user-clock', // More intuitive for unassigned/waiting
        'can' => 'view students',
    ],
    [
        'text' => 'Parents',
        'route' => 'staff.manage.parents.index',
        'icon' => 'fas fa-user-friends', // Parents/community icon
        'can' => 'view parents',
    ],
    [
        'text' => 'Roles & Permissions',
        'route' => 'staff.roles.index',
        'icon' => 'fas fa-shield-alt', // Clearer security/roles icon
        'can' => 'manage roles and permissions',
    ],
    // Academic Management Section
    [
        'header' => 'Academic Management',
        'can' => ['view grades', 'view exams', 'view results', 'view report_cards'],
    ],
    [
        'text' => 'Classes',
        'route' => 'staff.grades.index',
        'icon' => 'fas fa-layer-group', // Groups icon for classes
        'can' => 'view grades',
    ],
    [
        'text' => 'Exams',
        'route' => 'staff.exams.index',
        'icon' => 'fas fa-clipboard-list', // More specific for exams
        'can' => 'view exams',
    ],
    [
        'text' => 'Results',
        'route' => 'staff.results.index',
        'icon' => 'fas fa-poll', // Good for results/stats
        'can' => 'view results',
    ],
    [
        'text' => 'Report Card',
        'route' => 'staff.reports.index',
        'icon' => 'fas fa-file-alt', // Document icon for reports
        'can' => 'view report_cards',
    ],
    // Finance Management Section
    [
        'header' => 'Finance Management',
        'can' => 'view finances',
    ],
    [
        'text' => 'Fees',
        'icon' => 'fas fa-money-check-alt',
        'can' => 'view finances',
        'submenu' => [
            [
                'text' => 'Fees List',
                'route' => 'staff.fees.index',
                'icon' => 'fas fa-list-ul',
            ],
            [
                'text' => 'Mass Assign Fees',
                'route' => 'staff.fees.assign_bulk.form',
                'icon' => 'fas fa-cogs',
                'can' => 'manage finances',
            ],
            [
                'text' => 'Fees Reports',
                'route' => 'staff.fees.report.form',
                'icon' => 'fas fa-file-invoice-dollar',
                'can' => 'generate reports',
            ],
        ],
    ],
    [
        'text' => 'Payments',
        'icon' => 'fas fa-wallet',
        'can' => 'view finances',
        'submenu' => [
            [
                'text' => 'Payments List',
                'route' => 'staff.payments.index',
                'icon' => 'fas fa-list-ul',
            ],
            [
                'text' => 'Mass Assign Payments',
                'route' => 'staff.payments.assign_bulk.form', // Update with correct route
                'icon' => 'fas fa-cogs',
                'can' => 'manage finances',
            ],
            [
                'text' => 'Payments Report',
                'route' => 'staff.payments.report.form', // Update with correct route
                'icon' => 'fas fa-file-invoice',
                'can' => 'generate reports',
            ],
        ],
    ],
    [
        'text' => 'Statements',
        'icon' => 'fas fa-file-alt',
        'can' => 'view finances',
        'submenu' => [
            [
                'text' => 'View Student Statement',
                'route' => 'staff.finance.statements.index',
                'icon' => 'fas fa-search-dollar',
            ],
            [
                'text' => 'Batch Statements',
                'route' => 'staff.finance.statements.batch.form',
                'icon' => 'fas fa-users-cog',
                'can' => 'manage finances',
            ],
        ],
    ],
    // Account Settings Section
    [
        'header' => 'Account Settings',
    ],
    [
        'text' => 'My Profile',
        'route' => 'staff.profile.show',
        'icon' => 'fas fa-user-circle',
    ],
],

    /*
    |--------------------------------------------------------------------------
    | Menu Filters
    |--------------------------------------------------------------------------
    |
    | Here we can modify the menu filters of the admin panel.
    |
    | For detailed instructions you can look the menu filters section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Menu-Configuration
    |
    */

    'filters' => [
        JeroenNoten\LaravelAdminLte\Menu\Filters\GateFilter::class, // *** IMPORTANT: Handles the 'can' key ***
        JeroenNoten\LaravelAdminLte\Menu\Filters\HrefFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\SearchFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ActiveFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ClassesFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\LangFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\DataFilter::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Plugins Initialization
    |--------------------------------------------------------------------------
    |
    | Here we can modify the plugins used inside the admin panel.
    |
    | For detailed instructions you can look the plugins section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Plugins-Configuration
    |
    */

    'plugins' => [
        'Datatables' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => true,
                    'location' => '//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => true,
                    'location' => '//cdn.datatables.net/1.10.19/js/dataTables.bootstrap4.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => true,
                    'location' => '//cdn.datatables.net/1.10.19/css/dataTables.bootstrap4.min.css',
                ],
            ],
        ],
        'Select2' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => true,
                    'location' => 'vendor/select2/js/select2.full.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => true,
                    'location' => 'vendor/select2/css/select2.min.css',
                ],
                [
                    'type' => 'css',
                    'asset' => true,
                    'location' => 'vendor/select2-bootstrap4-theme/select2-bootstrap4.min.css',
                ],
            ],
        ],
        'Chartjs' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => true,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.0/Chart.bundle.min.js',
                ],
            ],
        ],
        'Sweetalert2' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.jsdelivr.net/npm/sweetalert2@8',
                ],
            ],
        ],
        'Pace' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'css',
                    'asset' => true,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/themes/blue/pace-theme-center-radar.min.css',
                ],
                [
                    'type' => 'js',
                    'asset' => true,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/pace.min.js',
                ],
            ],
        ],
        'BsCustomFileInput' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => true,
                    'location' => 'vendor/bs-custom-file-input/bs-custom-file-input.min.js',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | IFrame
    |--------------------------------------------------------------------------
    |
    | Here we change the IFrame mode configuration. Note these changes will
    | only apply to the view that extends and enable the IFrame mode.
    |
    | For detailed instructions you can look the iframe mode section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/IFrame-Mode-Configuration
    |
    */

    'iframe' => [
        'default_tab' => [
            'url' => null,
            'title' => null,
        ],
        'buttons' => [
            'close' => true,
            'close_all' => true,
            'close_all_other' => true,
            'scroll_left' => true,
            'scroll_right' => true,
            'fullscreen' => true,
        ],
        'options' => [
            'loading_screen' => 1000,
            'auto_show_new_tab' => true,
            'use_navbar_items' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Livewire
    |--------------------------------------------------------------------------
    |
    | Here we can enable the Livewire support.
    |
    | For detailed instructions you can look the livewire here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Other-Configuration
    |
    */

    'livewire' => false,
];
