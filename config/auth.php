<?php

use App\Models\Admin;
use App\Models\Member;
use App\Models\Organization;

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This option defines the default authentication "guard" for your
    | application. Responsables and members authenticate with a signed
    | magic link sent to their email address; admins use a password.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Three independent guards, one per kind of account: organization
    | responsables ("web"), organization members ("member"), and the
    | administrator ("admin"). Each keeps its own session state, so a
    | browser can only ever be signed in as one identity per guard.
    |
    | Supported: "session"
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'organizations',
        ],

        'member' => [
            'driver' => 'session',
            'provider' => 'members',
        ],

        'admin' => [
            'driver' => 'session',
            'provider' => 'admins',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | All authentication guards have a user provider, which defines how the
    | users are actually retrieved out of your database or other storage
    | system used by the application. Typically, Eloquent is utilized.
    |
    | Supported: "database", "eloquent"
    |
    */

    'providers' => [
        'organizations' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', Organization::class),
        ],

        'members' => [
            'driver' => 'eloquent',
            'model' => Member::class,
        ],

        'admins' => [
            'driver' => 'eloquent',
            'model' => Admin::class,
        ],
    ],

];
