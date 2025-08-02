<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Cache\CacheInterface;

class PromotionTrackingFilter implements FilterInterface
{
    private CacheInterface $cache;
    private array $config;

    public function __construct()
    {
        $this->cache = \Config\Services::cache();
        
        // Tracking configuration
        $this->config = [
            'max_clicks_per_ip_per_hour' => 50,
            'max_clicks_per_code_per_minute' => 20,
            'enable_fraud_detection' => true,
            'enable_geolocation' => true,
            'tracking_cooldown' => 300, // 5 minutes
            'suspicious_patterns' => [
                'rapid_clicking' => 10, // clicks per minute
                'same_referrer_threshold' => 20,
                'empty_referrer_threshold' => 30,
            ],
        ];
    }

    /**
     * Before filter - executed before tracking methods
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $ip = $this->getRealClientIP($request);
        $userAgent = $request->getUserAgent();
        $path = $request->getUri()->getPath();

        // Extract promotion code from path
        $promotionCode = $this->extractPromotionCode($path);
        
        if (!$promotionCode) {
            return $this->badRequestResponse('Invalid promotion code');
        }

        // Anti-fraud checks
        if ($this->config['enable_fraud_detection']) {
            
            // Check for rapid clicking
            if (!$this->checkRapidClickingLimit($ip, $promotionCode)) {
                $this->logSuspiciousActivity('rapid_clicking', [
                    'ip' => $ip,
                    'promotion_code' => $promotionCode,
                    'user_agent' => $userAgent,
                ]);
                return $this->tooManyRequestsResponse('Too many requests');
            }

            // Check for bot-like behavior
            if ($this->detectBotBehavior($userAgent, $request)) {
                $this->logSuspiciousActivity('bot_detected', [
                    'ip' => $ip,
                    'promotion_code' => $promotionCode,
                    'user_agent' => $userAgent,
                ]);
                return $this->forbiddenResponse('Bot detected');
            }

            // Check for suspicious referrer patterns
            if ($this->detectSuspiciousReferrer($request, $promotionCode)) {
                $this->logSuspiciousActivity('suspicious_referrer', [
                    'ip' => $ip,
                    'promotion_code' => $promotionCode,
                    'referrer' => $request->getHeaderLine('Referer'),
                ]);
            }
        }

        // Rate limiting specific to tracking
        if (!$this->checkTrackingRateLimit($ip, $promotionCode)) {
            return $this->tooManyRequestsResponse('Rate limit exceeded');
        }

        // Store tracking context for the controller
        $request->trackingContext = [
            'real_ip' => $ip,
            'promotion_code' => $promotionCode,
            'is_suspected_fraud' => false,
            'geolocation' => $this->getGeolocation($ip),
            'fingerprint' => $this->generateFingerprint($request),
        ];

        return;
    }

    /**
     * After filter - executed after tracking methods
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Add no-cache headers for tracking endpoints
        $response->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->setHeader('Pragma', 'no-cache');
        $response->setHeader('Expires', '0');

        // Add tracking-specific headers
        $response->setHeader('X-Tracking-Version', '1.0');
        
        // For tracking pixels, ensure proper content type
        if (strpos($request->getUri()->getPath(), '/pixel/') !== false) {
            $response->setHeader('Content-Type', 'image/gif');
        }

        return $response;
    }

    /**
     * Extract promotion code from URL path
     */
    private function extractPromotionCode(string $path): ?string
    {
        // Handle different tracking URL patterns
        $patterns = [
            '/\/api\/promotion\/track\/([A-Za-z0-9]+)/',
            '/\/api\/r\/([A-Za-z0-9]+)/',
            '/\/r\/([A-Za-z0-9]+)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $path, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Check rapid clicking limits
     */
    private function checkRapidClickingLimit(string $ip, string $promotionCode): bool
    {
        $cacheKey = "rapid_click_{$ip}_{$promotionCode}";
        $clicks = $this->cache->get($cacheKey) ?? [];
        $currentTime = time();
        
        // Remove clicks older than 1 minute
        $clicks = array_filter($clicks, function($timestamp) use ($currentTime) {
            return ($currentTime - $timestamp) < 60;
        });

        // Check if exceeded threshold
        if (count($clicks) >= $this->config['suspicious_patterns']['rapid_clicking']) {
            return false;
        }

        // Add current click
        $clicks[] = $currentTime;
        $this->cache->save($cacheKey, $clicks, 300); // Cache for 5 minutes

        return true;
    }

    /**
     * Detect bot behavior
     */
    private function detectBotBehavior(?string $userAgent, RequestInterface $request): bool
    {
        if (empty($userAgent)) {
            return true;
        }

        $userAgent = strtolower($userAgent);
        
        // Known bot signatures
        $botSignatures = [
            'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget', 
            'python-requests', 'go-http-client', 'java/', 'apache-httpclient',
            'postman', 'insomnia', 'http_request2', 'guzzlehttp'
        ];

        foreach ($botSignatures as $signature) {
            if (strpos($userAgent, $signature) !== false) {
                return true;
            }
        }

        // Check for missing or suspicious headers
        $suspiciousHeaders = [
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => true, // Must be present
            'HTTP_ACCEPT_ENCODING' => true, // Must be present
        ];

        foreach ($suspiciousHeaders as $header => $expected) {
            $value = $_SERVER[$header] ?? null;
            
            if ($expected === true && empty($value)) {
                return true; // Required header missing
            }
        }

        return false;
    }

    /**
     * Detect suspicious referrer patterns
     */
    private function detectSuspiciousReferrer(RequestInterface $request, string $promotionCode): bool
    {
        $referrer = $request->getHeaderLine('Referer');
        $cacheKey = "referrer_stats_{$promotionCode}";
        $stats = $this->cache->get($cacheKey) ?? [
            'same_referrer_count' => 0,
            'empty_referrer_count' => 0,
            'last_referrer' => null,
            'total_clicks' => 0,
        ];

        $stats['total_clicks']++;

        if (empty($referrer)) {
            $stats['empty_referrer_count']++;
        } elseif ($stats['last_referrer'] === $referrer) {
            $stats['same_referrer_count']++;
        } else {
            $stats['same_referrer_count'] = 1; // Reset counter
            $stats['last_referrer'] = $referrer;
        }

        // Save updated stats
        $this->cache->save($cacheKey, $stats, 3600); // Cache for 1 hour

        // Check thresholds
        if ($stats['same_referrer_count'] >= $this->config['suspicious_patterns']['same_referrer_threshold']) {
            return true;
        }

        if ($stats['empty_referrer_count'] >= $this->config['suspicious_patterns']['empty_referrer_threshold']) {
            return true;
        }

        return false;
    }

    /**
     * Check tracking-specific rate limits
     */
    private function checkTrackingRateLimit(string $ip, string $promotionCode): bool
    {
        // IP-based rate limiting
        $ipKey = "track_limit_ip_{$ip}";
        $ipCount = $this->cache->get($ipKey) ?? 0;
        
        if ($ipCount >= $this->config['max_clicks_per_ip_per_hour']) {
            return false;
        }
        
        $this->cache->save($ipKey, $ipCount + 1, 3600);

        // Promotion code-based rate limiting
        $codeKey = "track_limit_code_{$promotionCode}";
        $codeCount = $this->cache->get($codeKey) ?? 0;
        
        if ($codeCount >= $this->config['max_clicks_per_code_per_minute']) {
            return false;
        }
        
        $this->cache->save($codeKey, $codeCount + 1, 60);

        return true;
    }

    /**
     * Get geolocation for IP
     */
    private function getGeolocation(string $ip): ?array
    {
        if (!$this->config['enable_geolocation']) {
            return null;
        }

        // Skip for local IPs
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return null;
        }

        $cacheKey = "geo_{$ip}";
        $cached = $this->cache->get($cacheKey);
        
        if ($cached) {
            return $cached;
        }

        try {
            // Simple geolocation (you can replace with a better service)
            $response = @file_get_contents("http://ip-api.com/json/{$ip}?fields=country,regionName,city,lat,lon,timezone");
            
            if ($response) {
                $data = json_decode($response, true);
                
                if ($data && $data['status'] === 'success') {
                    $geolocation = [
                        'country' => $data['country'] ?? null,
                        'region' => $data['regionName'] ?? null,
                        'city' => $data['city'] ?? null,
                        'latitude' => $data['lat'] ?? null,
                        'longitude' => $data['lon'] ?? null,
                        'timezone' => $data['timezone'] ?? null,
                    ];
                    
                    // Cache for 24 hours
                    $this->cache->save($cacheKey, $geolocation, 86400);
                    
                    return $geolocation;
                }
            }
        } catch (\Exception $e) {
            log_message('warning', 'Geolocation lookup failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Generate device fingerprint
     */
    private function generateFingerprint(RequestInterface $request): string
    {
        $components = [
            $request->getIPAddress(),
            $request->getUserAgent(),
            $request->getHeaderLine('Accept-Language'),
            $request->getHeaderLine('Accept-Encoding'),
            $request->getHeaderLine('Accept'),
        ];

        return hash('sha256', implode('|', array_filter($components)));
    }

    /**
     * Get real client IP
     */
    private function getRealClientIP(RequestInterface $request): string
    {
        $ipHeaders = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Load balancers/proxies
            'HTTP_X_REAL_IP',            // Nginx proxy
            'HTTP_X_FORWARDED',          // Proxies
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster balancers
            'HTTP_FORWARDED_FOR',        // RFC 7239
            'HTTP_FORWARDED',            // RFC 7239
            'REMOTE_ADDR'                // Standard
        ];

        foreach ($ipHeaders as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $request->getIPAddress();
    }

    /**
     * Log suspicious activity
     */
    private function logSuspiciousActivity(string $type, array $data): void
    {
        $logEntry = array_merge([
            'type' => $type,
            'timestamp' => date('Y-m-d H:i:s'),
            'severity' => 'warning',
        ], $data);

        log_message('warning', 'Suspicious tracking activity: ' . json_encode($logEntry));

        // Store in cache for further analysis
        $cacheKey = "suspicious_activity_" . date('Y-m-d-H');
        $activities = $this->cache->get($cacheKey) ?? [];
        $activities[] = $logEntry;
        
        // Keep only last 1000 entries per hour
        if (count($activities) > 1000) {
            $activities = array_slice($activities, -1000);
        }
        
        $this->cache->save($cacheKey, $activities, 3600);
    }

    /**
     * Bad request response
     */
    private function badRequestResponse(string $message = 'Bad request'): ResponseInterface
    {
        $response = service('response');
        
        return $response->setStatusCode(400)
                       ->setJSON([
                           'status' => 'error',
                           'message' => $message,
                           'code' => 'BAD_REQUEST',
                       ]);
    }

    /**
     * Too many requests response
     */
    private function tooManyRequestsResponse(string $message = 'Too many requests'): ResponseInterface
    {
        $response = service('response');
        
        return $response->setStatusCode(429)
                       ->setJSON([
                           'status' => 'error',
                           'message' => $message,
                           'code' => 'TOO_MANY_REQUESTS',
                       ])
                       ->setHeader('Retry-After', '60');
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
     * Check if IP is in whitelist
     */
    private function isWhitelistedIP(string $ip): bool
    {
        $whitelist = [
            '127.0.0.1',
            '::1',
            // Add your trusted IPs here
        ];

        return in_array($ip, $whitelist);
    }

    /**
     * Get tracking statistics
     */
    public function getTrackingStats(): array
    {
        $currentHour = date('Y-m-d-H');
        $activities = $this->cache->get("suspicious_activity_{$currentHour}") ?? [];
        
        $stats = [
            'total_suspicious_activities' => count($activities),
            'activities_by_type' => [],
            'top_suspicious_ips' => [],
        ];

        foreach ($activities as $activity) {
            $type = $activity['type'];
            $ip = $activity['ip'] ?? 'unknown';
            
            $stats['activities_by_type'][$type] = ($stats['activities_by_type'][$type] ?? 0) + 1;
            $stats['top_suspicious_ips'][$ip] = ($stats['top_suspicious_ips'][$ip] ?? 0) + 1;
        }

        // Sort by frequency
        arsort($stats['top_suspicious_ips']);
        $stats['top_suspicious_ips'] = array_slice($stats['top_suspicious_ips'], 0, 10, true);

        return $stats;
    }
}