@php
use Illuminate\Support\Facades\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

// Resolve URLs for login, register, and password reset routes
$loginUrl = url(config('adminlte.login_url', 'login'));
$registerUrl = url(config('adminlte.register_url', 'register'));
$passResetUrl = url(config('adminlte.password_reset_url', 'password/reset'));

if (config('adminlte.use_route_url', false)) {
    try {
        $loginUrl = route(config('adminlte.login_url', 'login'));
        $registerUrl = config('adminlte.register_url', 'register') ? (Route::has(config('adminlte.register_url', 'register')) ? route(config('adminlte.register_url', 'register')) : '') : '';
        $passResetUrl = config('adminlte.password_reset_url', 'password/reset') ? (Route::has(config('adminlte.password_reset_url', 'password/reset')) ? route(config('adminlte.password_reset_url', 'password/reset')) : '') : '';
    } catch (\Exception $e) {
        // Fallback or log error
    }
}

// Current date and academic year
$currentDate = Carbon::now()->format('F d, Y');
$currentYear = Carbon::now()->year;
$academicYear = $currentYear;

// --- Logo Logic ---
$logoPathInPublicFolder = 'favicon.svg';
$actualLogoPath = public_path($logoPathInPublicFolder);
$logoAltText = 'Gift Schools';
$schoolLogoUrl = '';
$absoluteSchoolLogoUrl = '';

if (File::exists($actualLogoPath)) {
    $schoolLogoUrl = asset($logoPathInPublicFolder); // Automatically absolute if APP_URL is set
    $absoluteSchoolLogoUrl = $schoolLogoUrl;
    $logoAltText = 'Gift Schools Logo, Dar es Salaam, Tanzania';
} else {
    $placeholderUrl = 'https://placehold.co/80x80/0056b3/FFFFFF?text=GS&font=montserrat';
    $schoolLogoUrl = $placeholderUrl;
    $absoluteSchoolLogoUrl = $placeholderUrl;
    $logoAltText = 'Gift Schools Initials Logo (GS), Dar es Salaam, Tanzania';
}
// --- End Logo Logic ---


// Canonical URL - ensure this matches your primary domain and protocol (http/https)
$appBaseUrl = rtrim(config('app.url', 'https://gift-schools.sc.tz'), '/');
$canonicalUrl = $appBaseUrl . '/'; // Default to homepage
if (request()->is('login')) { // If there's a distinct /login page
    $canonicalUrl = $appBaseUrl . '/login';
}

// --- ContactPoint JSON Logic ---
$contactPointProperties = [];
// Use env() to get values, provide default for email if env var is not set but you still want a default value
$schoolEmail = env('SCHOOL_CONTACT_EMAIL', 'giftschoolstz@gmail.com'); // Default email
$schoolPhone = env('SCHOOL_CONTACT_PHONE'); // No default for phone, it will be empty if not in .env

// Add email if it's not empty
if (!empty($schoolEmail)) {
    $contactPointProperties[] = '"email": "' . e($schoolEmail) . '"';
}

// Add telephone if it's not empty
if (!empty($schoolPhone)) {
    $contactPointProperties[] = '"telephone": "' . e($schoolPhone) . '"';
}

$contactPointExtraJson = "";
if (!empty($contactPointProperties)) {
    // Prepend a comma only if there are properties to add after "url"
    $contactPointExtraJson = "," . implode(",", $contactPointProperties);
}
// --- End ContactPoint JSON Logic ---

@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
   <meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Login | Gift Schools Management System (SIMS) - Dar es Salaam, Tanzania</title>

<meta name="description" content="Secure login to Gift Schools SIMS portal. For students, parents, and staff to manage academics, fees, reports, and school updates. Based in Dar es Salaam, Tanzania.">
<meta name="keywords" content="Gift Schools, Gift SIMS, Gift SIS, School Management System Tanzania, student login, parent portal, teacher login, gift-schools.sc.tz, education portal Dar es Salaam, private schools Tanzania, Bangulo">
<meta name="robots" content="index, follow">
<meta name="author" content="Gift Schools Tanzania">

<link rel="canonical" href="{{ $canonicalUrl ?? url()->current() }}" />

<!-- Open Graph / Facebook -->
<meta property="og:type" content="website">
<meta property="og:title" content="Login | Gift Schools SIMS Portal">
<meta property="og:description" content="Access the official Gift Schools Student Information Management System. Login for students, parents, teachers in Dar es Salaam, Tanzania.">
<meta property="og:url" content="{{ $canonicalUrl ?? url()->current() }}">
<meta property="og:image" content="{{ $absoluteSchoolLogoUrl ?? asset('favicon.svg') }}">
<meta property="og:site_name" content="Gift Schools SIMS">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Login | Gift Schools SIMS">
<meta name="twitter:description" content="Login to the official Gift Schools portal for students, parents, and teachers in Tanzania. Manage academics, fees, and communications.">
<meta name="twitter:image" content="{{ $absoluteSchoolLogoUrl ?? asset('favicon.svg') }}">

<!-- Favicon -->
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

<!-- Stylesheets -->
<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('vendor/icheck-bootstrap/icheck-bootstrap.min.css') }}">

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": [
    "EducationalOrganization",
    "School",
    "PrimarySchool",
    "Preschool"
  ],
  "name": "Gift Schools",
  "alternateName": "Gift Schools Tanzania",
  "description": "Gift Schools in Dar es Salaam, Tanzania, provides quality education fostering academic excellence and personal growth. We offer a comprehensive curriculum and a supportive learning environment.",
  "url": "https://gift-schools.sc.tz",
  "logo": {
    "@type": "ImageObject",
    "url": "https://gift-schools.sc.tz/favicon.svg",
    "width": 200,
    "height": 80
  },
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "P.O. Box XXXX, Your Street Name",
    "addressLocality": "Dar es Salaam",
    "addressRegion": "Dar es Salaam",
    "postalCode": "YourPostalCode",
    "addressCountry": "TZ"
  },
  "geo": {
    "@type": "GeoCoordinates",
    "latitude": -6.8000,
    "longitude": 39.2833
  },
  "contactPoint": [
    {
      "@type": "ContactPoint",
      "contactType": "General Enquiries",
      "telephone": "+255-713-066949",
      "email": "giftschoolstz@gmail.com",
      "areaServed": "TZ",
      "availableLanguage": [
        {
          "@type": "Language",
          "name": "English"
        },
        {
          "@type": "Language",
          "name": "Swahili"
        }
      ]
    }
  ],
  "sameAs": [
    "https://facebook.com/giftschoolstz",
    "https://instagram.com/giftschoolstz"
  ],
  "slogan": "Better Education for Better Life.",
  "foundingDate": "2015-01-01",
  "areaServed": [
    {
      "@type": "AdministrativeArea",
      "name": "Dar es Salaam"
    },
    {
      "@type": "Country",
      "name": "Tanzania"
    }
  ],
  "keywords": "Gift Schools, Gift School Dar es Salaam, Gift English Medium Schools, Gift Pre and Primary School, Gift School Management System, Gift SIMS, Gift SIS, Education in Tanzania, Schools in Dar es Salaam, English Medium Schools Tanzania, Private Schools Tanzania, Tanzanian School System"
}
</script>



    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #fff;
            color: #333;
            font-size: 14px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        #wrapper {
           flex: 1;
        }
        /* Header Styles */
        #header_div {
            background-color: #f8f9fa;
            padding: 10px 0;
            border-bottom: 1px solid #e7e7e7;
        }
        .navbar-static-top {
            display: flex;
            align-items: center;
            max-width: 1140px;
            margin: 0 auto;
            padding: 0 15px;
        }
        #header_logo img {
            width: 70px;
            height: 70px;
            margin-right: 20px;
            object-fit: contain;
        }
        #header_content {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        #header_content_college { /* This will be H1 */
            font-size: 1.5rem;
            font-weight: bold;
            color: #0056b3;
            margin: 0; /* Reset margin for h1 */
        }
        #header_content_sims { /* This can be a p or span under H1 */
            font-size: 1rem;
            color: #555;
            text-transform: uppercase;
        }
        #header_line {
            height: 3px;
            background: linear-gradient(to right, #0056b3, #007bff, #28a745);
            margin-bottom: 15px;
        }

        /* Academic Year / Date Bar */
        .top-bar-info {
            font-size: 15px;
            font-weight: bold;
            color: #000;
            margin-bottom: 20px;
        }

        /* Main Content Area */
        .info-section {
            padding-right: 30px;
        }
        .info-section h2, .info-section h3 { /* Changed from h5 for better SEO */
            font-weight: bold;
            color: #0056b3;
            margin-top: 15px;
            margin-bottom: 10px; /* Increased margin for h2/h3 */
        }
         .info-section h2 { font-size: 1.4rem; }
         .info-section h3 { font-size: 1.2rem; }

        .info-section p, .info-section ul {
            margin-left: 15px;
            font-size: 0.95rem;
        }
        .info-section ul {
            list-style: none;
            padding-left: 0;
        }
        .info-section ul li {
            margin-bottom: 5px;
            position: relative;
            padding-left: 20px;
        }
        .info-section ul li::before {
            content: "\f00c";
            font-family: "Font Awesome 5 Free", "FontAwesome";
            font-weight: 900;
            position: absolute;
            left: 0;
            color: #28a745;
        }

        /* Login Box Styles */
        #login-box {
            background-color: #f9f9f9;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        #login-box .signin-header { /* This will be H2 */
            font-size: 1.6rem; /* Adjusted size for H2 */
            font-weight: bold;
            color: #0056b3;
            text-align: center;
            margin-bottom: 20px;
        }
        #login-box .form-control {
            border-radius: 4px;
            padding: 10px;
            height: auto;
        }
        #login-box label {
            font-weight: bold;
            margin-bottom: .3rem;
            font-size: 0.9rem;
        }
        #login-box .form-group {
            margin-bottom: 1rem;
        }
        #login-box .btn-submit {
            background-color: #0056b3;
            border-color: #004085;
            color: white;
            font-weight: bold;
            padding: 10px;
            width: 100%;
        }
        #login-box .btn-submit:hover {
            background-color: #004085;
            border-color: #00376e;
        }
        #login-box .extra-links {
            font-size: 0.85rem;
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
         #login-box .extra-links .icheck-primary label {
            font-weight: normal;
            font-size: 0.85rem;
         }

        /* Footer Styles */
        .footer {
            background-color: #343a40;
            color: #f8f9fa;
            padding: 20px 0;
            font-size: 0.9rem;
            margin-top: auto;
            width: 100%;
        }
        .footer #header_line {
            margin-bottom: 0;
            margin-top: -20px;
            position: relative;
        }
        .footer a {
            color: #00bfff;
        }
        .footer a:hover {
            color: #87cefa;
        }
        .footer .pull-right {
            float: right;
        }
        .footer .location-info {
            font-size: 0.85rem;
            color: #ccc;
        }

        /* Responsive Adjustments */
        @media (min-width: 768px) {
            .info-section {
                 border-right: 2px solid #e0e0e0;
            }
        }

        @media (max-width: 767.98px) {
            .info-section {
                border-right: none;
                padding-right: 0;
                margin-top: 30px;
            }
            #header_content_college { /* H1 */
                font-size: 1.3rem;
            }
            #header_content_sims {
                font-size: 0.9rem;
            }
            #login-box .signin-header { /* H2 */
                font-size: 1.4rem;
            }
            .footer .pull-right, .footer .text-md-right { /* Ensure footer content stacks and centers */
                float: none;
                text-align: center !important;
                margin-bottom: 10px;
            }
             .footer .text-md-left {
                text-align: center !important;
             }
            .top-bar-info .text-right {
                text-align: left !important;
                margin-top: 5px;
            }
        }
        .invalid-feedback {
            display: block !important;
        }
    </style>
</head>
<body>
    <div id="wrapper">
        <div id="header_div">
            <nav class="navbar-static-top">
                <div id="header_logo">
                    <img src="{{ $schoolLogoUrl }}" alt="{{ $logoAltText }}">
                </div>
                <div id="header_content">
                    <h1 id="header_content_college">GIFT SCHOOLS</h1>
                    <p id="header_content_sims">SCHOOL MANAGEMENT SYSTEM</p>
                </div>
            </nav>
        </div>
        <div id="header_line"></div>

        <div class="container top-bar-info">
            <div class="row">
                <div class="col-md-6 col-sm-6">Academic Year: {{ $academicYear }}</div>
                <div class="col-md-6 col-sm-6 text-right">{{ $currentDate }}</div>
            </div>
        </div>

        <div class="container">
            <div class="row">
                {{-- Login Box Column: Appears first on mobile (order-1), second on desktop (order-md-2) --}}
                <div class="col-md-5 order-1 order-md-2">
                    <div id="login-box">
                        <h2 class="signin-header">User Login</h2>

                        @if(session('status'))
                            <div class="alert alert-success mb-3">
                                {{ session('status') }}
                            </div>
                        @endif
                        @if($errors->any() && !$errors->has('login') && !$errors->has('password'))
                            <div class="alert alert-danger mb-3">
                                An unexpected error occurred. Please try again.
                            </div>
                        @endif

                        <form action="{{ $loginUrl }}" method="post" class="form-horizontal">
                            @csrf
                            <div class="form-group">
                                <label for="login">{{ __('Email or Username') }}</label>
                                <input type="text" name="login" id="login" class="form-control @error('login') is-invalid @enderror"
                                       value="{{ old('login') }}" placeholder="Enter your username or email" autofocus>
                                @error('login')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="password">{{ __('Password') }}</label>
                                <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror"
                                       placeholder="Enter your password">
                                @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="form-group extra-links">
                                <div class="icheck-primary">
                                    <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                    <label for="remember">
                                        {{ __('adminlte::adminlte.remember_me') }}
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-submit">
                                    <i class="fas fa-sign-in-alt mr-1"></i> {{ __('adminlte::adminlte.sign_in') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Informational Section Column: Appears second on mobile (order-2), first on desktop (order-md-1) --}}
                <div class="col-md-7 info-section order-2 order-md-1">
                    <h2>Welcome to Gift Schools Management System</h2>
                    <p>The School Management System (SMS) for Gift Schools is your central hub for all school-related information and activities.</p>

                    <h3>Students and Parents Portal</h3>
                    <ul>
                        <li>Manage Fees and Payments for Students</li>
                        <li>View Academic Progress and Results</li>
                        <li>Access Learning Materials</li>
                        <li>Communicate with Teachers</li>
                    </ul>

                    <h3>Teaching Staff Resources</h3>
                    <ul>
                        <li>View list of Students per Class/Subject</li>
                        <li>Publish Assignments and Results</li>
                        <li>Track Student Performance & Reports</li>
                        <li>Manage Timetables and Attendance</li>
                    </ul>

                    <h3>Administrative Features</h3>
                    <ul>
                        <li>Fee Payment Management & Tracking</li>
                        <li>User & Role Management</li>
                        <li>System Configuration & Settings for Gift Schools</li>
                        <li>Announcements & Event Management</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>{{-- End #wrapper --}}

    <footer class="footer">
        <div id="header_line"></div>
        <div class="container">
            <div class="row">
                <div class="col-md-6 text-center text-md-left mb-2 mb-md-0">
                    <strong>&copy; {{ date('Y') }} <a href="https://gift-schools.sc.tz" target="_blank">Gift Schools</a>.</strong> All rights reserved.<br>
                    <span class="location-info">Dar es Salaam, Tanzania.</span>
                </div>
                <div class="col-md-6 text-center text-md-right">
                    Design and Developed by <strong><a href="#" target="_blank">Gift Schools Developers / ICT Department</a></strong>.
                </div>
            </div>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
