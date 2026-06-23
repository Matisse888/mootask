<?php

return [

    /*
    |--------------------------------------------------------------------------
    | JWT Configuration
    |--------------------------------------------------------------------------
    |
    | JWT (JSON Web Token) configuration for MooTask authentication system.
    | The token is used for user authentication and authorization.
    |
    */

    'secret' => env('JWT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | JWT Time To Live
    |--------------------------------------------------------------------------
    |
    | Specifies the number of minutes the JWT will be valid for. The default
    | value is 1440 minutes (24 hours). Adjust as needed.
    |
    */

    'ttl' => env('JWT_TTL', 1440),

    /*
    |--------------------------------------------------------------------------
    | JWT Refresh Time To Live
    |--------------------------------------------------------------------------
    |
    | Specifies the number of days the refresh token will be valid for.
    |
    */

    'refresh_ttl' => env('JWT_REFRESH_TTL', 20160),

    /*
    |--------------------------------------------------------------------------
    | JWT Algorithm
    |--------------------------------------------------------------------------
    |
    | Specifies the algorithm used to sign the JWT.
    |
    */

    'algo' => env('JWT_ALGO', 'HS256'),

    /*
    |--------------------------------------------------------------------------
    | Required Claims
    |--------------------------------------------------------------------------
    |
    | The "iss" (issuer), "iat" (issued at), "exp" (expiration time),
    | and "nbf" (not before) claims are required.
    |
    */

    'required_claims' => [
        'iss',
        'iat',
        'exp',
        'nbf',
        'sub',
    ],

    /*
    |--------------------------------------------------------------------------
    | Persistent Claims
    |--------------------------------------------------------------------------
    |
    | Specifies the claims that will be persisted when refreshing a token.
    |
    */

    'persistent_claims' => [],

    /*
    |--------------------------------------------------------------------------
    | Lock Subject
    |--------------------------------------------------------------------------
    |
    | This will determine whether the "sub" claim is required when refreshing
    | a token.
    |
    */

    'lock_subject' => true,

    /*
    |--------------------------------------------------------------------------
    | Leeway
    |--------------------------------------------------------------------------
    |
    | This property gives the JWT library a leeway, in seconds, to account
    | for clock skew between different servers.
    |
    */

    'leeway' => env('JWT_LEEWAY', 0),

    /*
    |--------------------------------------------------------------------------
    | Blacklist Enabled
    |--------------------------------------------------------------------------
    |
    | When true, the JWT will be added to a blacklist when logged out.
    |
    */

    'blacklist_enabled' => env('JWT_BLACKLIST_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Blacklist Grace Period
    |--------------------------------------------------------------------------
    |
    | This is the number of seconds a blacklisted token will still be valid
    | for after being blacklisted.
    |
    */

    'blacklist_grace_period' => env('JWT_BLACKLIST_GRACE_PERIOD', 0),

    /*
    |--------------------------------------------------------------------------
    | Show Black List Exception
    |--------------------------------------------------------------------------
    |
    | When true, the "Your token has been blacklisted" exception will be
    | thrown when the token is blacklisted.
    |
    */

    'show_black_list_exception' => env('JWT_SHOW_BLACKLIST_EXCEPTION', 1),

    /*
    |--------------------------------------------------------------------------
    | Cookies Configurations
    |--------------------------------------------------------------------------
    |
    | Configurations for the cookies used by the JWT package.
    |
    */

    'cookies' => [
        'key' => 'mootask_token',
    ],

];
