<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Session\Handlers\FileHandler;

class App extends BaseConfig
{
    public string $baseURL = 'http://localhost:8080/';
    public array $allowedHostnames = [];
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
    public bool $sessionMatchIP = false;
    public int $sessionTimeToUpdate = 300;
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
    public string $permittedURIChars = 'a-z 0-9~%.:_\-';
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

    // CORS configuration - Environment specific settings
    public array $corsConfig = [
        'allowedOrigins' => [
            'http://localhost:3000',
            'http://localhost:3001',
            'http://localhost:9117',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:3001',
            'http://127.0.0.1:9117',
            'https://promotion.mercylife.cc',
            'https://admin.promotion.mercylife.cc'
        ],
        'allowedMethods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH', 'HEAD'],
        'allowedHeaders' => [
            'Origin',
            'Content-Type',
            'Accept',
            'Authorization',
            'X-Requested-With',
            'X-CSRF-TOKEN',
            'X-API-KEY'
        ],
        'exposedHeaders' => [
            'Authorization',
            'X-Total-Count',
            'X-Page-Count'
        ],
        'maxAge' => 86400,
        'supportsCredentials' => true,
    ];
}