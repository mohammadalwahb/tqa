<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Allowed Email Domains
    |--------------------------------------------------------------------------
    |
    | Only users whose Google account email ends with one of these domains may
    | sign in. Comma-separated list lives in the ALLOWED_EMAIL_DOMAINS env var.
    |
    */
    'allowed_email_domains' => array_filter(array_map(
        'trim',
        explode(',', (string) env('ALLOWED_EMAIL_DOMAINS', 'uoz.edu.krd,staff.uoz.edu.krd'))
    )),

    /*
    |--------------------------------------------------------------------------
    | Bootstrap Super Admin(s)
    |--------------------------------------------------------------------------
    |
    | Comma-separated list of emails that should be created and assigned the
    | Super Admin role by the seeder. Each email must belong to one of the
    | allowed domains (otherwise OAuth would reject the login anyway).
    |
    */
    'super_admin_emails' => array_values(array_filter(array_map(
        fn ($e) => mb_strtolower(trim($e)),
        explode(',', (string) env(
            'TQA_SUPER_ADMIN_EMAILS',
            env('TQA_SUPER_ADMIN_EMAIL', 'admin@uoz.edu.krd,mohammad.abdulwahab@uoz.edu.krd')
        ))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Current Academic Year
    |--------------------------------------------------------------------------
    |
    | Label used for new evaluation periods (seeders and create form defaults).
    |
    */
    'current_academic_year' => env('ACADEMIC_YEAR', '2025-2026'),

    /*
    |--------------------------------------------------------------------------
    | Rating Scale
    |--------------------------------------------------------------------------
    */
    'rating' => [
        'min' => 1,
        'max' => 5,
    ],
];
