<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Cache\CacheInterface;

class PromotionSecurityFilter implements FilterInterface
{
    private CacheInterface $cache;
    private array $config;

    public function __construct()
    {
        $this->cache = \Config\Services::cache();
        
        // Security configuration
        $this->config = [
            'max_requests_per_minute' => 60,
            'max_requests_per_hour' => 1000,
            'max_promotion_creates_per_day' => 10,
            'max_clicks_per_ip_per_hour' => 100,
            'blocked_user_agents' => [
                'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget', 'python-requests'
            ],
            'honeypot_fields' => ['website', 'url', 'homepage'],
            'enable_csrf_protection' => true,
            'enable_fingerprinting' => true,
        ];
    }

    /**
     * Before filter - executed before controller method
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $uri = $request->getUri();
        $method = $request->getMethod();
        $ip = $request->getIPAddress();
        $userAgent = $request->getUserAgent();
        
        // Skip security checks for certain routes
        if ($this->shouldSkipSecurityCheck($uri->getPath())) {
            return;
        }

        // Rate limiting
        if (!$this->checkRateLimit($ip, $method, $uri->getPath())) {
            return $this->rateLimitResponse();
        }

        // User agent filtering
        if (!$this->isValidUserAgent($userAgent)) {
            return $this->forbiddenResponse('Invalid user agent');
        }

        // CSRF protection for state-changing operations
        if ($this->config['enable_csrf_protection'] && in_array($method, ['POST', 'PUT', 'DELETE'])) {
            if (!$this->validateCSRFToken($request)) {
                return $this->forbiddenResponse('CSRF token validation failed');
            }
        }

        // Honeypot validation
        if ($method === 'POST' && !$this->validateHoneypot($request)) {
            log_message('warning', "Honeypot trap triggered from IP: {$ip}");
            return $this->forbiddenResponse('Invalid request');
        }

        // Geolocation-based blocking (if needed)
        if (!$this->isAllowedLocation($ip)) {
            return $this->forbiddenResponse('Access denied from your location');
        }

        // Device fingerprinting for fraud detection
        if ($this->config['enable_fingerprinting']) {
            $this->recordDeviceFingerprint($request);
        }

        return;
    }

    /**
     * After filter - executed after controller method
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Add security headers
        $response->setHeader('X-Content-Type-Options', 'nosniff');
        $response->setHeader('X-Frame-Options', 'DENY');
        $response->setHeader('X-XSS-Protection', '1; mode=block');
        $response->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Add CORS headers for API endpoints
        if (strpos($request->getUri()->getPath(), '/api/') === 0) {
            $response->setHeader('Access-Control-Allow-Origin', $this->getAllowedOrigins());
            $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->setHeader('Access-Control-Allow-Headers', 'Origin, Content-Type, Accept, Authorization, X-Request-With, X-CSRF-TOKEN');
            $response->setHeader('Access-Control-Max-Age', '86400');
        }

        return $response;
    }

    /**
     * Check if security checks should be skipped
     */
    private function shouldSkipSecurityCheck(string $path): bool
    {
        $skipPaths = [
            '/api/promotion/track/', // Tracking endpoints
            '/api/r/', // Short link redirects
            '/api/health', // Health check
        ];

        foreach ($skipPaths as $skipPath) {
            if (strpos($path, $skipPath) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Rate limiting check
     */
    private function checkRateLimit(string $ip, string $method, string $path): bool
    {
        // Different limits for different endpoints
        $limits = [
            'POST:/api/promotions' => ['limit' => $this->config['max_promotion_creates_per_day'], 'window' => 86400],
            'GET:/api/promotion/track/' => ['limit' => $this->config['max_clicks_per_ip_per_hour'], 'window' => 3600],
            'default' => ['limit' => $this->config['max_requests_per_minute'], 'window' => 60],
        ];

        $ruleKey = "{$method}:{$path}";
        $rule = $limits[$ruleKey] ?? $limits['default'];

        // Check if path matches tracking pattern
        if (strpos($path, '/api/promotion/track/') === 0) {
            $rule = $limits['GET:/api/promotion/track/'];
        }

        $cacheKey = "rate_limit_{$ip}_{$method}_{$rule['window']}";
        $requests = $this->cache->get($cacheKey) ?? 0;

        if ($requests >= $rule['limit']) {
            return false;
        }

        // Increment counter
        $this->cache->save($cacheKey, $requests + 1, $rule['window']);
        
        return true;
    }

    /**
     * Validate user agent
     */
    private function isValidUserAgent(?string $userAgent): bool
    {
        if (empty($userAgent)) {
            return false;
        }

        $userAgent = strtolower($userAgent);
        
        foreach ($this->config['blocked_user_agents'] as $blocked) {
            if (strpos($userAgent, $blocked) !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate CSRF token
     */
    private function validateCSRFToken(RequestInterface $request): bool
    {
        // Skip CSRF for tracking endpoints
        if (strpos($request->getUri()->getPath(), '/api/promotion/track') === 0) {
            return true;
        }

        $token = $request->getHeaderLine('X-CSRF-TOKEN') ?: $request->getPost('_token');
        
        if (empty($token)) {
            return false;
        }

        // Validate token format and expiry
        return $this->isValidCSRFToken($token);
    }

    /**
     * Validate CSRF token format and expiry
     */
    private function isValidCSRFToken(string $token): bool
    {
        try {
            $decoded = base64_decode($token);
            $parts = explode('|', $decoded);
            
            if (count($parts) !== 3) {
                return false;
            }

            [$timestamp, $hash, $signature] = $parts;
            
            // Check if token is expired (24 hours)
            if (time() - $timestamp > 86400) {
                return false;
            }

            // Verify signature
            $expectedSignature = hash_hmac('sha256', $timestamp . '|' . $hash, env('app.key', 'your-app-key'));
            
            return hash_equals($expectedSignature, $signature);
            
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validate honeypot fields
     */
    private function validateHoneypot(RequestInterface $request): bool
    {
        $postData = $request->getPost();
        
        foreach ($this->config['honeypot_fields'] as $field) {
            if (!empty($postData[$field])) {
                return false; // Honeypot triggered
            }
        }
        
        return true;
    }

    /**
     * Check if IP is from allowed location
     */
    private function isAllowedLocation(string $ip): bool
    {
        // Skip for local IPs
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return true;
        }

        // Check cache first
        $cacheKey = "geo_allowed_{$ip}";
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }

        // Simple geolocation check (you can integrate with more sophisticated services)
        $allowed = true; // Default to allow
        
        try {
            // You can implement IP geolocation checking here
            // For example, block certain countries if needed
            
            // Cache result for 24 hours
            $this->cache->save($cacheKey, $allowed, 86400);
            
        } catch (\Exception $e) {
            log_message('warning', 'Geolocation check failed: ' . $e->getMessage());
        }

        return $allowed;
    }

    /**
     * Record device fingerprint
     */
    private function recordDeviceFingerprint(RequestInterface $request): void
    {
        $fingerprint = [
            'ip' => $request->getIPAddress(),
            'user_agent' => $request->getUserAgent(),
            'accept_language' => $request->getHeaderLine('Accept-Language'),
            'accept_encoding' => $request->getHeaderLine('Accept-Encoding'),
            'timestamp' => time(),
        ];

        $hash = hash('sha256', json_encode($fingerprint));
        $cacheKey = "fingerprint_{$hash}";
        
        // Store fingerprint for 30 days
        $existing = $this->cache->get($cacheKey) ?? [];
        $existing[] = $fingerprint;
        
        // Keep only last 10 records
        if (count($existing) > 10) {
            $existing = array_slice($existing, -10);
        }
        
        $this->cache->save($cacheKey, $existing, 86400 * 30);
    }

    /**
     * Get allowed origins for CORS
     */
    private function getAllowedOrigins(): string
    {
        $allowedOrigins = [
            'http://localhost:3000',
            'https://localhost:3000',
        ];

        // Add production domains from environment
        $prodDomains = explode(',', env('ALLOWED_ORIGINS', ''));
        $allowedOrigins = array_merge($allowedOrigins, array_filter($prodDomains));

        return implode(', ', $allowedOrigins);
    }

    /**
     * Rate limit response
     */
    private function rateLimitResponse(): ResponseInterface
    {
        $response = service('response');
        
        return $response->setStatusCode(429)
                       ->setJSON([
                           'status' => 'error',
                           'message' => 'Rate limit exceeded. Please try again later.',
                           'code' => 'RATE_LIMIT_EXCEEDED',
                       ]);
    }

    /**
     * Forbidden response
     */
    private function forbiddenResponse(string $message = 'Access denied'): ResponseInterface
    {
        $response = service('response');
        
        return $response->setStatusCode(403)
                       ->setJSON([
                           'status' => 'error',
                           'message' => $message,
                           'code' => 'ACCESS_DENIED',
                       ]);
    }

    /**
     * Get client IP with proxy detection
     */
    private function getRealClientIP(RequestInterface $request): string
    {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Load balancers/proxies
            'HTTP_X_FORWARDED',          // Proxies
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster balancers
            'HTTP_FORWARDED_FOR',        // RFC 7239
            'HTTP_FORWARDED',            // RFC 7239
            'REMOTE_ADDR'                // Standard
        ];

        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $request->getIPAddress();
    }

    /**
     * Log security event
     */
    private function logSecurityEvent(string $event, array $data = []): void
    {
        $logData = array_merge([
            'event' => $event,
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        ], $data);

        log_message('warning', 'Security Event: ' . json_encode($logData));
    }
}