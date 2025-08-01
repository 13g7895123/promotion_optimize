<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\SecureHeaders;

class Filters extends BaseConfig
{
    /**
     * Configures aliases for Filter classes to
     * make reading things nicer and simpler.
     */
    public array $aliases = [
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'jwtauth'       => \App\Filters\JWTAuthFilter::class,
        'rbac'          => \App\Filters\RBACFilter::class,
        'ratelimit'     => \App\Filters\RateLimitFilter::class,
        'cors'          => \App\Filters\CORSFilter::class,
        'apiauth'       => \App\Filters\APIAuthFilter::class,
        'inputvalidation' => \App\Filters\InputValidationFilter::class,
    ];

    /**
     * List of filter aliases that are always
     * applied before and after every request.
     */
    public array $globals = [
        'before' => [
            'honeypot',
            'cors',
            'secureheaders',
            'ratelimit',
            'inputvalidation',
        ],
        'after' => [
            'toolbar',
        ],
    ];

    /**
     * List of filter aliases that works on a
     * particular HTTP method (GET, POST, etc.).
     */
    public array $methods = [
        'POST' => ['csrf'],
        'PUT'  => ['csrf'],
        'PATCH' => ['csrf'],
        'DELETE' => ['csrf'],
    ];

    /**
     * List of filter aliases that should run on any
     * before or after URI patterns.
     */
    public array $filters = [
        'jwtauth' => [
            'before' => [
                'api/auth/logout',
                'api/auth/profile',
                'api/users/*',
                'api/roles/*',
                'api/permissions/*',
                'api/servers/*',
                'api/health',
                'api/stats',
            ]
        ],
        'cors' => [
            'before' => ['api/*']
        ],
        'ratelimit' => [
            'before' => ['api/*']
        ],
    ];
}