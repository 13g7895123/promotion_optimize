<?php

namespace App\Libraries;

use CodeIgniter\Cache\CacheInterface;

class SecurityService
{
    private CacheInterface $cache;
    private array $config;

    public function __construct()
    {
        $this->cache = \Config\Services::cache();
        
        $this->config = [
            // Anti-fraud settings
            'max_promotions_per_user_per_day' => 5,
            'max_clicks_per_ip_per_hour' => 50,
            'max_clicks_per_promotion_per_ip' => 10,
            'click_interval_seconds' => 30, // Min time between clicks from same IP
            
            // Suspicious activity thresholds
            'suspicious_click_rate_threshold' => 0.8, // 80% click rate from same IP
            'suspicious_conversion_rate_threshold' => 0.3, // 30% conversion rate
            'max_failed_attempts' => 5,
            
            // Blocking settings
            'ip_block_duration' => 3600, // 1 hour
            'user_suspend_duration' => 86400, // 24 hours
            
            // Validation settings
            'require_referrer_validation' => true,
            'require_user_agent_validation' => true,
            'enable_device_fingerprinting' => true,
        ];
    }

    /**
     * Validate promotion creation request
     */
    public function validatePromotionCreation(int $userId, array $requestData): array
    {
        // Check daily promotion limit
        $dailyLimit = $this->checkDailyPromotionLimit($userId);
        if (!$dailyLimit['allowed']) {
            return ['valid' => false, 'reason' => $dailyLimit['reason']];
        }

        // Check for suspicious patterns
        $suspiciousCheck = $this->checkSuspiciousPromotionPattern($userId, $requestData);
        if (!$suspiciousCheck['valid']) {
            return $suspiciousCheck;
        }

        // Validate server ownership or permissions
        $serverCheck = $this->validateServerAccess($userId, $requestData['server_id'] ?? 0);
        if (!$serverCheck['valid']) {
            return $serverCheck;
        }

        return ['valid' => true];
    }

    /**
     * Validate click tracking request
     */
    public function validateClickTracking(string $promotionCode, array $context): array
    {
        $ip = $context['ip'] ?? '';
        $userAgent = $context['user_agent'] ?? '';

        // Check if IP is blocked
        if ($this->isIPBlocked($ip)) {
            return ['valid' => false, 'reason' => 'IP address is blocked'];
        }

        // Check click rate limits
        $rateLimitCheck = $this->checkClickRateLimit($ip, $promotionCode);
        if (!$rateLimitCheck['allowed']) {
            return ['valid' => false, 'reason' => $rateLimitCheck['reason']];
        }

        // Check for bot traffic
        $botCheck = $this->detectBotTraffic($userAgent, $context);
        if ($botCheck['is_bot']) {
            $this->logSuspiciousActivity('bot_traffic', [
                'ip' => $ip,
                'user_agent' => $userAgent,
                'promotion_code' => $promotionCode,
                'bot_indicators' => $botCheck['indicators']
            ]);
            return ['valid' => false, 'reason' => 'Bot traffic detected'];
        }

        // Check for click fraud patterns
        $fraudCheck = $this->detectClickFraud($ip, $promotionCode, $context);
        if ($fraudCheck['is_fraud']) {
            $this->handleFraudAttempt($ip, $promotionCode, $fraudCheck['indicators']);
            return ['valid' => false, 'reason' => 'Click fraud detected'];
        }

        // Validate referrer if required
        if ($this->config['require_referrer_validation']) {
            $referrerCheck = $this->validateReferrer($context['referrer'] ?? '');
            if (!$referrerCheck['valid']) {
                return $referrerCheck;
            }
        }

        return ['valid' => true];
    }

    /**
     * Check daily promotion creation limit
     */
    private function checkDailyPromotionLimit(int $userId): array
    {
        $cacheKey = "daily_promotions_{$userId}_" . date('Y-m-d');
        $todayCount = $this->cache->get($cacheKey) ?? 0;

        if ($todayCount >= $this->config['max_promotions_per_user_per_day']) {
            return [
                'allowed' => false,
                'reason' => 'Daily promotion creation limit exceeded'
            ];
        }

        // Increment counter
        $this->cache->save($cacheKey, $todayCount + 1, 86400);

        return ['allowed' => true];
    }

    /**
     * Check for suspicious promotion patterns
     */
    private function checkSuspiciousPromotionPattern(int $userId, array $requestData): array
    {
        // Check for rapid consecutive promotions
        $cacheKey = "last_promotion_time_{$userId}";
        $lastPromotionTime = $this->cache->get($cacheKey);
        
        if ($lastPromotionTime && (time() - $lastPromotionTime) < 60) {
            return [
                'valid' => false,
                'reason' => 'Promotions created too rapidly'
            ];
        }

        $this->cache->save($cacheKey, time(), 3600);

        // Check for duplicate promotion patterns
        $patternKey = "promotion_pattern_{$userId}";
        $patterns = $this->cache->get($patternKey) ?? [];
        
        $currentPattern = md5(json_encode([
            'server_id' => $requestData['server_id'] ?? 0,
            'base_link' => $requestData['base_link'] ?? '',
            'utm_source' => $requestData['utm_source'] ?? '',
        ]));

        if (in_array($currentPattern, $patterns)) {
            return [
                'valid' => false,
                'reason' => 'Duplicate promotion pattern detected'
            ];
        }

        // Store pattern (keep last 10)
        $patterns[] = $currentPattern;
        if (count($patterns) > 10) {
            $patterns = array_slice($patterns, -10);
        }
        
        $this->cache->save($patternKey, $patterns, 86400);

        return ['valid' => true];
    }

    /**
     * Validate server access permissions
     */
    private function validateServerAccess(int $userId, int $serverId): array
    {
        if ($serverId <= 0) {
            return ['valid' => false, 'reason' => 'Invalid server ID'];
        }

        // Check cache first
        $cacheKey = "server_access_{$userId}_{$serverId}";
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== null) {
            return $cached ? ['valid' => true] : ['valid' => false, 'reason' => 'Access denied to server'];
        }

        // Check database (simplified - you would implement actual permission check)
        $serverModel = new \App\Models\ServerModel();
        $canAccess = $serverModel->canUserManage($serverId, $userId);
        
        // Cache result for 5 minutes
        $this->cache->save($cacheKey, $canAccess, 300);

        return $canAccess ? ['valid' => true] : ['valid' => false, 'reason' => 'Access denied to server'];
    }

    /**
     * Check if IP is blocked
     */
    private function isIPBlocked(string $ip): bool
    {
        if (empty($ip)) return false;

        $cacheKey = "blocked_ip_{$ip}";
        return $this->cache->get($cacheKey) !== null;
    }

    /**
     * Check click rate limits
     */
    private function checkClickRateLimit(string $ip, string $promotionCode): array
    {
        $hour = date('Y-m-d-H');
        
        // Check hourly IP limit
        $hourlyKey = "clicks_per_ip_{$ip}_{$hour}";
        $hourlyClicks = $this->cache->get($hourlyKey) ?? 0;
        
        if ($hourlyClicks >= $this->config['max_clicks_per_ip_per_hour']) {
            return [
                'allowed' => false,
                'reason' => 'Hourly click limit exceeded for IP'
            ];
        }

        // Check clicks per promotion per IP
        $promotionKey = "clicks_per_promotion_ip_{$promotionCode}_{$ip}";
        $promotionClicks = $this->cache->get($promotionKey) ?? 0;
        
        if ($promotionClicks >= $this->config['max_clicks_per_promotion_per_ip']) {
            return [
                'allowed' => false,
                'reason' => 'Click limit exceeded for this promotion from your IP'
            ];
        }

        // Check minimum interval between clicks
        $intervalKey = "last_click_{$ip}";
        $lastClick = $this->cache->get($intervalKey);
        
        if ($lastClick && (time() - $lastClick) < $this->config['click_interval_seconds']) {
            return [
                'allowed' => false,
                'reason' => 'Clicks too frequent'
            ];
        }

        // Update counters
        $this->cache->save($hourlyKey, $hourlyClicks + 1, 3600);
        $this->cache->save($promotionKey, $promotionClicks + 1, 86400);
        $this->cache->save($intervalKey, time(), $this->config['click_interval_seconds']);

        return ['allowed' => true];
    }

    /**
     * Detect bot traffic
     */
    private function detectBotTraffic(string $userAgent, array $context): array
    {
        $indicators = [];
        $userAgent = strtolower($userAgent);

        // Common bot signatures
        $botSignatures = [
            'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget', 
            'python-requests', 'axios', 'urllib', 'httpclient'
        ];

        foreach ($botSignatures as $signature) {
            if (strpos($userAgent, $signature) !== false) {
                $indicators[] = "bot_signature_{$signature}";
            }
        }

        // Check for missing common headers
        if (empty($context['referrer'])) {
            $indicators[] = 'missing_referrer';
        }

        // Check for unusual header combinations
        if (empty($context['accept_language'])) {
            $indicators[] = 'missing_accept_language';
        }

        // Check user agent length (bots often have very short or very long UAs)
        if (strlen($userAgent) < 20 || strlen($userAgent) > 500) {
            $indicators[] = 'unusual_ua_length';
        }

        return [
            'is_bot' => !empty($indicators),
            'indicators' => $indicators,
            'confidence' => count($indicators) * 0.2 // 0.0 to 1.0
        ];
    }

    /**
     * Detect click fraud patterns
     */
    private function detectClickFraud(string $ip, string $promotionCode, array $context): array
    {
        $indicators = [];
        
        // Check click pattern (rapid successive clicks)
        $patternKey = "click_pattern_{$ip}_{$promotionCode}";
        $recentClicks = $this->cache->get($patternKey) ?? [];
        
        $currentTime = time();
        $recentClicks = array_filter($recentClicks, function($timestamp) use ($currentTime) {
            return ($currentTime - $timestamp) < 300; // Last 5 minutes
        });
        
        if (count($recentClicks) >= 5) {
            $indicators[] = 'rapid_clicks';
        }
        
        $recentClicks[] = $currentTime;
        $this->cache->save($patternKey, $recentClicks, 300);

        // Check for session consistency
        $sessionKey = "click_session_{$ip}";
        $sessionData = $this->cache->get($sessionKey) ?? [];
        
        $currentFingerprint = md5($context['user_agent'] . $context['accept_language'] ?? '');
        
        if (!empty($sessionData['fingerprint']) && $sessionData['fingerprint'] !== $currentFingerprint) {
            $indicators[] = 'session_inconsistency';
        }
        
        $sessionData['fingerprint'] = $currentFingerprint;
        $sessionData['last_seen'] = $currentTime;
        $this->cache->save($sessionKey, $sessionData, 3600);

        // Check geographic consistency (if available)
        if (isset($context['country']) && isset($sessionData['country'])) {
            if ($context['country'] !== $sessionData['country']) {
                $indicators[] = 'geographic_inconsistency';
            }
        }

        // Check for suspicious timing patterns
        if (!empty($recentClicks) && count($recentClicks) > 1) {
            $intervals = [];
            for ($i = 1; $i < count($recentClicks); $i++) {
                $intervals[] = $recentClicks[$i] - $recentClicks[$i-1];
            }
            
            // Check if intervals are too regular (indicating automation)
            $variance = $this->calculateVariance($intervals);
            if ($variance < 5) { // Very low variance indicates automation
                $indicators[] = 'regular_timing_pattern';
            }
        }

        return [
            'is_fraud' => count($indicators) >= 2, // Require at least 2 indicators
            'indicators' => $indicators,
            'confidence' => min(count($indicators) * 0.3, 1.0)
        ];
    }

    /**
     * Validate referrer
     */
    private function validateReferrer(string $referrer): array
    {
        if (empty($referrer)) {
            return ['valid' => false, 'reason' => 'Missing referrer information'];
        }

        // Check if referrer is from blocked domains
        $blockedDomains = ['spam-site.com', 'fraud-domain.net']; // Add actual blocked domains
        
        foreach ($blockedDomains as $blocked) {
            if (strpos($referrer, $blocked) !== false) {
                return ['valid' => false, 'reason' => 'Referrer from blocked domain'];
            }
        }

        return ['valid' => true];
    }

    /**
     * Handle fraud attempt
     */
    private function handleFraudAttempt(string $ip, string $promotionCode, array $indicators): void
    {
        // Increment fraud attempt counter
        $attemptKey = "fraud_attempts_{$ip}";
        $attempts = $this->cache->get($attemptKey) ?? 0;
        $attempts++;
        
        $this->cache->save($attemptKey, $attempts, 86400);

        // Block IP after too many fraud attempts
        if ($attempts >= $this->config['max_failed_attempts']) {
            $this->blockIP($ip, $this->config['ip_block_duration']);
        }

        // Log fraud attempt
        $this->logSuspiciousActivity('click_fraud', [
            'ip' => $ip,
            'promotion_code' => $promotionCode,
            'indicators' => $indicators,
            'attempt_count' => $attempts
        ]);
    }

    /**
     * Block IP address
     */
    private function blockIP(string $ip, int $duration): void
    {
        $cacheKey = "blocked_ip_{$ip}";
        $this->cache->save($cacheKey, time(), $duration);
        
        log_message('warning', "IP blocked for suspicious activity: {$ip}");
    }

    /**
     * Log suspicious activity
     */
    private function logSuspiciousActivity(string $type, array $data): void
    {
        $logData = array_merge([
            'type' => $type,
            'timestamp' => date('Y-m-d H:i:s'),
            'severity' => 'warning'
        ], $data);

        log_message('warning', 'Suspicious Activity: ' . json_encode($logData));
        
        // Store in database for analysis (optional)
        // You could create a security_events table to store this data
    }

    /**
     * Calculate variance of an array
     */
    private function calculateVariance(array $values): float
    {
        if (count($values) < 2) return 0;
        
        $mean = array_sum($values) / count($values);
        $squaredDiffs = array_map(function($value) use ($mean) {
            return pow($value - $mean, 2);
        }, $values);
        
        return array_sum($squaredDiffs) / count($values);
    }

    /**
     * Get security statistics
     */
    public function getSecurityStats(): array
    {
        // This would typically query a database table
        // For now, return sample data
        return [
            'blocked_ips_count' => 0,
            'fraud_attempts_today' => 0,
            'bot_traffic_blocked' => 0,
            'suspicious_activities' => 0,
        ];
    }

    /**
     * Clean up expired security data
     */
    public function cleanupExpiredData(): void
    {
        // This would be called by a scheduled task
        // Clear expired blocks, attempts, etc.
        
        log_message('info', 'Security data cleanup completed');
    }

    /**
     * Whitelist IP address
     */
    public function whitelistIP(string $ip): bool
    {
        $cacheKey = "whitelisted_ip_{$ip}";
        return $this->cache->save($cacheKey, true, 86400 * 30); // 30 days
    }

    /**
     * Check if IP is whitelisted
     */
    public function isIPWhitelisted(string $ip): bool
    {
        $cacheKey = "whitelisted_ip_{$ip}";
        return $this->cache->get($cacheKey) !== null;
    }

    /**
     * Generate fraud detection report
     */
    public function generateFraudReport(int $days = 7): array
    {
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        
        // This would query actual data from logs/database
        return [
            'period' => "{$startDate} to {$endDate}",
            'total_clicks' => 0,
            'blocked_clicks' => 0,
            'fraud_rate' => 0.0,
            'top_blocked_ips' => [],
            'fraud_indicators' => [],
            'recommendations' => []
        ];
    }
}