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
        'inputvalidation' => \App\Filters\InputValidationFilter::class,
        'promotionsecurity' => \App\Filters\PromotionSecurityFilter::class,
        'promotiontracking' => \App\Filters\PromotionTrackingFilter::class,
    ];

    /**
     * List of filter aliases that are always
     * applied before and after every request.
     */
    public array $globals = [
        'before' => [
            'cors',
            'honeypot',
            'secureheaders',
            'ratelimit',
            'inputvalidation',
        ],
        'after' => [
            'cors',
            'toolbar',
        ],
    ];

    /**
     * List of filter aliases that works on a
     * particular HTTP method (GET, POST, etc.).
     */
    public array $methods = [
        // CSRF disabled for API routes to avoid CORS conflicts
        // API authentication is handled via JWT tokens
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
                'api/promotions/*',
                'api/rewards/*',
                'api/statistics/*',
                'api/health',
                'api/stats',
            ]
        ],
        'promotionsecurity' => [
            'before' => [
                'api/promotions/*',
                'api/rewards/*',
                'api/statistics/*',
            ]
        ],
        'promotiontracking' => [
            'before' => [
                'api/promotion/track/*',
                'api/r/*',
                'r/*',
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