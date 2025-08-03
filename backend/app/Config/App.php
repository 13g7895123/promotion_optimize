<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Session\Handlers\FileHandler;

class App extends BaseConfig
{
    public string $baseURL = 'http://localhost:8080/';
    public string $allowedHostnames = '';
    public string $indexPage = '';
    public string $uriProtocol = 'REQUEST_URI';
    public string $defaultLocale = 'en';
    public bool $negotiateLocale = false;
    public array $supportedLocales = ['en'];
    public string $appTimezone = 'UTC';
    public string $charset = 'UTF-8';
    public bool $forceGlobalSecureRequests = false;
    public int $sessionExpiration = 7200;
    public string $sessionSavePath = WRITEPATH . 'session';
    public string $sessionMatchIP = false;
    public string $sessionTimeToUpdate = 300;
    public bool $sessionRegenerateDestroy = false;
    public string $sessionDriver = FileHandler::class;
    public string $cookieDomain = '';
    public string $cookiePath = '/';
    public bool $cookieSecure = false;
    public bool $cookieHTTPOnly = true;
    public ?string $cookieSameSite = 'Lax';
    public bool $proxyIPs = false;
    public ?string $CSRFTokenName = 'csrf_token_name';
    public ?string $CSRFHeaderName = 'X-CSRF-TOKEN';
    public ?string $CSRFCookieName = 'csrf_cookie_name';
    public int $CSRFExpire = 7200;
    public bool $CSRFRegenerate = true;
    public bool $CSRFRedirect = true;
    public ?string $CSRFSameSite = 'Lax';
    public bool $CSPEnabled = false;
    public array $permittedURIChars = 'a-z 0-9~%.:_\-';
    public string $defaultController = 'Home';
    public string $defaultMethod = 'index';
    public bool $translateURIDashes = false;
    public int $logThreshold = 4;
    public array $dateFormat = [
        'default' => 'Y-m-d H:i:s',
        'short'   => 'Y-m-d',
        'long'    => 'Y-m-d H:i:s'
    ];

    // Security configurations for JWT and API
    public array $jwtConfig = [
        'secretKey' => 'your-256-bit-secret-key-here-change-in-production',
        'algorithm' => 'HS256',
        'expiration' => 3600, // 1 hour
        'refreshExpiration' => 604800, // 1 week
    ];

    // Rate limiting configuration
    public array $rateLimiting = [
        'enabled' => true,
        'requests' => 100,
        'window' => 3600, // 1 hour
        'skipSuccessfulRequests' => false,
        'skipFailedRequests' => false,
    ];

    // CORS configuration - Fully permissive for development
    public array $corsConfig = [
        'allowedOrigins' => ['*'], // Allow all origins
        'allowedMethods' => ['*'], // Allow all methods
        'allowedHeaders' => ['*'], // Allow all headers
        'exposedHeaders' => ['*'], // Expose all headers
        'maxAge' => 86400,
        'supportsCredentials' => false, // Set to false when using wildcard
    ];
}