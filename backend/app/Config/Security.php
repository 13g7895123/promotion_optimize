<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Security extends BaseConfig
{
    public ?string $csrfTokenName = 'csrf_token_name';
    public ?string $csrfHeaderName = 'X-CSRF-TOKEN';
    public ?string $csrfCookieName = 'csrf_cookie_name';
    public int $csrfExpire = 7200;
    public bool $csrfRegenerate = true;
    public bool $csrfRedirect = false; // Set to false for API
    public ?string $csrfSameSite = 'Lax';

    // Content Security Policy
    public bool $cspEnabled = true;
    public ?string $cspReportURI = null;
    public bool $cspSandbox = false;
    public string $cspUpgradeInsecureRequests = 'auto';

    // CSP Directives
    public array $cspDirectives = [
        'default-src' => ['self'],
        'script-src'  => ['self', 'unsafe-inline'],
        'style-src'   => ['self', 'unsafe-inline'],
        'img-src'     => ['self', 'data:', 'https:'],
        'font-src'    => ['self'],
        'connect-src' => ['self'],
        'media-src'   => ['self'],
        'object-src'  => ['none'],
        'child-src'   => ['self'],
        'frame-src'   => ['self'],
        'worker-src'  => ['self'],
        'form-action' => ['self'],
        'base-uri'    => ['self'],
    ];

    // Security Headers
    public array $securityHeaders = [
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-Content-Type-Options' => 'nosniff',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
    ];

    // Input filtering and validation
    public array $globalXssFiltering = true;
    public bool $csrfExcludeURIs = [];

    // API-specific exemptions for CSRF (for stateless JWT authentication)
    public array $csrfExemptRoutes = [
        'api/auth/login',
        'api/auth/register',
        'api/auth/refresh',
    ];

    // Rate limiting configuration
    public array $rateLimitConfig = [
        'auth_attempts' => [
            'max_attempts' => 5,
            'window' => 900, // 15 minutes
            'block_duration' => 3600, // 1 hour
        ],
        'api_calls' => [
            'max_requests' => 1000,
            'window' => 3600, // 1 hour
        ],
        'registration' => [
            'max_attempts' => 3,
            'window' => 3600, // 1 hour
        ],
    ];

    // Password policy
    public array $passwordPolicy = [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => false,
        'max_length' => 255,
    ];

    // JWT Security Configuration
    public array $jwtSecurity = [
        'secret_key_min_length' => 32,
        'algorithm' => 'HS256',
        'leeway' => 60, // 1 minute leeway for clock skew
        'not_before_leeway' => 60,
        'issued_at_leeway' => 60,
    ];

    // File upload security
    public array $uploadSecurity = [
        'allowed_types' => 'gif|jpg|jpeg|png|pdf|doc|docx',
        'max_size' => 2048, // 2MB
        'max_width' => 1920,
        'max_height' => 1080,
        'encrypt_name' => true,
    ];

    // SQL injection prevention patterns
    public array $sqlInjectionPatterns = [
        'union[\s]+(all[\s]+)?select',
        'select.*from',
        'insert[\s]+into',
        'update.*set',
        'delete[\s]+from',
        'drop[\s]+(table|database)',
        'create[\s]+(table|database)',
        'alter[\s]+table',
        'exec[\s]*\(',
        'script[\s]*:',
        'javascript[\s]*:',
        'vbscript[\s]*:',
    ];

    public function __construct()
    {
        // Load security configuration from environment
        if (ENVIRONMENT === 'production') {
            $this->securityHeaders['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
            $this->cspDirectives['upgrade-insecure-requests'] = true;
        }

        // Disable CSRF for API routes in development
        if (ENVIRONMENT === 'development') {
            $this->csrfRegenerate = false;
        }
    }
}