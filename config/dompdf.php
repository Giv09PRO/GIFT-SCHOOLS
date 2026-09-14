<?php

return [

    'show_warnings' => false,

    /**
     * Explicitly define the public path for DomPDF.
     * This MUST be the absolute path to your web server's document root
     * where public assets like images and fonts are served from.
     * Ensure this path has a trailing slash.
     */
    'public_path' => env('DOMPDF_PUBLIC_PATH', public_path() . DIRECTORY_SEPARATOR),

    'convert_entities' => true,

    'options' => [
        'font_dir' => storage_path('fonts/'), // For DomPDF's internal font storage/cache
        'font_cache' => storage_path('fonts/'), // For DomPDF's internal font storage/cache
        
        /**
         * temp_dir: Ensure this directory exists and is writable by the web server.
         * Using storage_path is generally more reliable than sys_get_temp_dir() on shared hosts.
         */
        'temp_dir' => storage_path('app/dompdf_temp/'), // <--- ENSURE THIS EXISTS AND IS WRITABLE

        /**
         * chroot: Restrict DomPDF to reading files (like images, fonts referenced in HTML/CSS)
         * from this directory and its subdirectories.
         * This should also point to your actual public asset root.
         */
        'chroot' => env('DOMPDF_CHROOT', public_path() . DIRECTORY_SEPARATOR),

        'allowed_protocols' => [
            'data://' => ['rules' => []],
            'file://' => ['rules' => []], // Be cautious if enabling file:// access
            'http://' => ['rules' => []],
            'https://' => ['rules' => []],
        ],

        'log_output_file' => null, // Consider storage_path('logs/dompdf.html') for debugging

        'enable_font_subsetting' => false,
        'pdf_backend' => 'CPDF',
        'default_media_type' => 'screen',
        'default_paper_size' => 'a4',
        'default_paper_orientation' => 'portrait',
        
        /**
         * default_font: Ensure 'Roboto' (or your chosen font) is correctly set up
         * and the font files are accessible via the chroot path (e.g., in public_html/fonts/).
         */
        'default_font' => 'Roboto', 
        
        'dpi' => 96,
        'enable_php' => false, // Keep false for security
        'enable_javascript' => true, // For PDF-based JS, not browser JS
        'enable_remote' => false, // Keep false unless absolutely necessary
        'allowed_remote_hosts' => null,
        'font_height_ratio' => 1.1,
        'enable_html5_parser' => true,
    ],
];