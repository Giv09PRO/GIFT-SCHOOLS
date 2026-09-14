<?php // app/Helpers/Qs.php

namespace App\Helpers;

use App\Models\School; // Assuming you have a School model
use App\Models\Staff;
use App\Models\Student;
use Carbon\Carbon;
use DateMalformedStringException;
use DateTime;
use Exception;
use Hashids\Hashids; // Make sure this is the correct Hashids class if you use it directly
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache; // For caching settings
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Qs
{
    protected static $systemSettings = null;

    /**
     * Generate a unique student username in the format: schooltitle_firstnameXX_year
     * (Your existing method)
     */
    public static function generateStudentUsername($school_id, $first_name, int $year = null): string
    {
        $school = School::findOrFail($school_id);
        $school_code = strtoupper(Str::slug($school->short_name ?? 'school', ''));
        $first_name = strtoupper(Str::slug($first_name, ''));
        $year = $year ?? now()->year;
        do {
            $random = str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT);
            $username = "{$school_code}/{$first_name}{$random}/{$year}";
        } while (Student::where('username', $username)->exists());
        return $username;
    }

    /**
     * Generate a unique staff username in the format: schooltitle_lastnameXX_year
     * (Your existing method)
     */
    public static function generateStaffUsername(int $school_id, string $last_name, int $year = null): string
    {
        $school = School::findOrFail($school_id);
        $school_title = strtoupper(Str::slug($school->short_name ?? 'school', ''));
        $last_name = strtoupper(Str::slug($last_name, ''));
        $year = $year ?? now()->year;
        do {
            $random_numbers = str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT);
            $username = "{$school_title}/{$last_name}{$random_numbers}/{$year}";
        } while (Staff::where('username', $username)->exists());
        return $username;
    }

    /**
     * Generate a student password in the format LastName@Year
     */
    public static function generateStudentPassword(string $last_name): string
    {
        $last_name = ucfirst(strtolower($last_name));
        $year = now()->year; // Corrected to ensure $year is always defined
        return "{$last_name}@{$year}";
    }

    /**
     * Generate a user password in the format LastName@Year
     */
    public static function generateUserPassword(string $last_name): string
    {
        $last_name = ucfirst(strtolower($last_name));
        $year = now()->year; // Corrected to ensure $year is always defined
        return "{$last_name}@{$year}";
    }

    /**
     * Fetch schools by syear
     * (Your existing method)
     */
    public function getSchoolsBySyear(Request $request): JsonResponse
    {
        $syear = $request->query('syear');
        $schools = School::where('syear', $syear)->get(['id', 'title']);
        Log::info('Fetching schools for syear: ' . $syear, ['schools' => $schools->toArray()]);
        return response()->json($schools);
    }

    /**
     * Get current school year from config.
     * (Your existing method)
     */
    public static function getCurrentSchoolYear()
    {
        // Ensure 'school.current_year' is defined in your config/school.php or other config file
        return config('school.current_year', date('Y')); // Added fallback to current year
    }

    /**
     * Get the current school ID for the authenticated staff user.
     * (Your existing method - slightly modified for clarity)
     * @return int|null The ID of the current school or null if not found/not staff.
     */
    public static function getCurrentSchoolId(): ?int
    {
        if (Auth::guard('staff')->check()) {
            /** @var Staff $staff */
            $staff = Auth::guard('staff')->user();
            return $staff->current_school_id ?? null;
        }
        return config('school.default_school_id_for_settings'); 
    }

    /**
     * Get the default school ID for the current year if no specific school context is available.
     * (Your existing method - modified to be safer)
     */
    public static function getDefaultSchoolId()
    {
        $school = School::where('syear', self::getCurrentSchoolYear())->orderBy('id')->first(); 
        return $school ? $school->id : null; 
    }


    /**
     * Calculates a default start date for enrollments for the given school year.
     * (Your existing method)
     */
    public static function getRolloverStartDate(int $toYear): string
    {
        $targetMonth = 9;
        if (class_exists(Carbon::class)) {
            $date = Carbon::create($toYear, $targetMonth)->startOfDay();
            while (!$date->isWeekday()) {
                $date->addDay();
            }
        } else {
            $date = new DateTime("{$toYear}-{$targetMonth}-01");
            $dayOfWeek = (int)$date->format('N');
            if ($dayOfWeek === 6) { $date->modify('+2 days'); }
            elseif ($dayOfWeek === 7) { $date->modify('+1 day'); }
        }
        return $date->format('Y-m-d');
    }

    /**
     * Encodes a numeric ID using the default Hashids connection.
     * (Your existing method)
     */
    public static function encode_id(int|string|null $id): ?string
    {
        if (is_null($id) || !is_numeric($id) || $id < 0) {
            return null;
        }
        try {
            return app('hashids')->encode($id);
        } catch (Exception $e) {
            Log::error('Hashids encoding failed in Qs::encode_id helper: ' . $e->getMessage(), ['id' => $id, 'exception' => $e]);
            return null;
        }
    }

    /**
     * Get a list of distinct school years present in the enrollment data.
     * (Your existing method)
     */
    public static function getSchoolYears(string $order = 'desc'): array
    {
        try {
            $years = DB::table('student_enrollment')
                ->select('syear')
                ->distinct()
                ->orderBy('syear', $order)
                ->pluck('syear')
                ->map(fn ($year) => (int)$year)
                ->toArray();
            if (empty($years)) {
                $currentYear = Carbon::now()->year;
                $years = range($currentYear, $currentYear - 5);
                if ($order === 'asc') { sort($years); } else { rsort($years); }
            }
            return $years;
        } catch (Exception $e) {
            Log::error('Failed to retrieve school years from database: ' . $e->getMessage());
            $currentYear = Carbon::now()->year;
            return [$currentYear, $currentYear - 1];
        }
    }

    /**
     * Formats a numeric amount as currency.
     * Uses currency symbol and code from system settings if available.
     */
    public static function formatCurrency($amount)
    {
        if (!is_numeric($amount)) {
            return $amount; 
        }
        $settings = self::getSystemSettings();
        $currencySymbol = property_exists($settings, 'currency_symbol') ? $settings->currency_symbol : 'TZS';
        // $currencyCode = property_exists($settings, 'currency_code') ? $settings->currency_code : 'USD'; // Not used in this format string

        // Using number_format for basic formatting.
        // For more advanced localization, consider PHP's NumberFormatter or a dedicated library.
        return $currencySymbol . number_format((float)$amount, 2, '.', ',');
    }
    
    public static function getCurrencySymbol(){
        $settings = self::getSystemSettings();
        $currencySymbol = property_exists($settings, 'currency_symbol') ? $settings->currency_symbol : 'TZS';
        return $currencySymbol;
    }

    /**
     * Fetches and caches system/school settings from the 'settings' JSON column
     * of the currently active school.
     *
     * @param bool $forceRefresh Whether to force refresh from DB, ignoring cache.
     * @return object An object containing the settings.
     */
    public static function getSystemSettings(bool $forceRefresh = false): object
    {
        if (static::$systemSettings !== null && !$forceRefresh) {
            return static::$systemSettings;
        }

        $currentSchoolYear = self::getCurrentSchoolYear(); 
        $currentSchoolId = self::getCurrentSchoolId();   

        $defaultSettings = (object) [
            'currency_code' => config('app.currency_code', 'TZS '),  // Changed default to TZS
            'currency_symbol' => config('app.currency_symbol', 'TSh '), // Changed default to TSh
            'timezone' => config('app.timezone', 'UTC'),            
            'report_header' => 'School Management System',
            'default_due_days' => 14, 
            'date_format' => 'Y-m-d', // Default date format
            // Add any other system-wide default settings here
        ];

        if (!$currentSchoolId) {
            Log::warning('Qs::getSystemSettings - Current School ID not determined. Using global default settings.');
            static::$systemSettings = $defaultSettings;
            return static::$systemSettings;
        }

        $cacheKey = "school_settings_{$currentSchoolId}_{$currentSchoolYear}";

        if (!$forceRefresh && Cache::has($cacheKey)) {
            $cachedSettings = Cache::get($cacheKey);
            if (is_object($cachedSettings)) { 
                static::$systemSettings = (object) array_merge((array) $defaultSettings, (array) $cachedSettings);
                return static::$systemSettings;
            }
        }

        try {
            $school = School::where('id', $currentSchoolId)
                ->where('syear', $currentSchoolYear) 
                ->first();

            if ($school && $school->settings) {
                $dbSettings = $school->settings;
                static::$systemSettings = (object) array_merge((array) $defaultSettings, (array) $dbSettings);
                Cache::put($cacheKey, $dbSettings, now()->addHours(24)); 
                return static::$systemSettings;
            } else {
                Log::warning("Qs::getSystemSettings - Settings not found for school ID {$currentSchoolId} and year {$currentSchoolYear}. Using global default settings.");
                static::$systemSettings = $defaultSettings;
                return static::$systemSettings;
            }
        } catch (Exception $e) {
            Log::error("Error fetching system settings for school ID {$currentSchoolId}, year {$currentSchoolYear}: " . $e->getMessage());
            static::$systemSettings = $defaultSettings; 
            return static::$systemSettings;
        }
    }

    /**
     * Helper to directly get a specific setting value with a fallback.
     *
     * @param string $key The setting key (e.g., 'currency_symbol')
     * @param mixed $default The default value if the key is not found.
     * @return mixed
     */
    public static function getSetting(string $key, $default = null)
    {
        $settings = self::getSystemSettings();
        return property_exists($settings, $key) ? $settings->{$key} : $default;
    }

    /**
     * Get the system's preferred date format string.
     *
     * @return string The date format string (e.g., 'Y-m-d', 'd/m/Y').
     */
    public static function getSystemDateFormat(): string
    {
        // Fetches 'date_format' from settings, defaults to 'Y-m-d'
        return self::getSetting('date_format', 'Y-m-d');
    }


    /**
     * Get the default user image asset path.
     * (Your existing method)
     */
    public static function getDefaultUserImage(): string
    {
        return asset('global_assets/images/user.png');
    }

    // /**
    //  * Hashes an ID using a static salt.
    //  * (Your existing method)
    //  */
    // public static function hash($id): string
    // {
    //     $salt = config('app.hashids_salt_cj', 'CJ'); 
    //     $minLength = config('app.hashids_min_length_cj', 14);
    //     return (new Hashids($salt, $minLength))->encode($id);
    // }

    // /**
    //  * Decodes a hash created by self::hash().
    //  * (Your existing method)
    //  */
    // public static function decodeHash($str, $toString = true): array|string
    // {
    //     $salt = config('app.hashids_salt_cj', 'CJ');
    //     $minLength = config('app.hashids_min_length_cj', 14);
    //     $hash = new Hashids($salt, $minLength);
    //     $decoded = $hash->decode($str);
    //     return $toString ? implode(',', $decoded) : $decoded;
    // }
}
