<?php

use App\Models\Organization;

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This option defines the default authentication "guard" for your
    | application. There is no password broker: responsables authenticate
    | with a signed magic link sent to their email address instead.
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
    | Next, you may define every authentication guard for your application.
    | Of course, a great default configuration has been defined for you
    | which utilizes session storage plus the Eloquent user provider.
    |
    | All authentication guards have a user provider, which defines how the
    | users are actually retrieved out of your database or other storage
    | system used by the application. Typically, Eloquent is utilized.
    |
    | Supported: "session"
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'organizations',
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
    | Each organization record is itself the authenticatable "responsable"
    | for its own fiche, matched by email — see App\Models\Organization.
    |
    | Supported: "database", "eloquent"
    |
    */

    'providers' => [
        'organizations' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', Organization::class),
        ],
    ],

];
