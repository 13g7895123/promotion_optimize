<?php

namespace App\Libraries;

use App\Models\PromotionModel;
use App\Models\PromotionClickModel;
use App\Models\PromotionStatsModel;
use App\Models\UserModel;
use CodeIgniter\Cache\CacheInterface;

class PromotionTracker
{
    private PromotionModel $promotionModel;
    private PromotionClickModel $clickModel;
    private PromotionStatsModel $statsModel;
    private UserModel $userModel;
    private CacheInterface $cache;
    private array $config;

    public function __construct()
    {
        $this->promotionModel = new PromotionModel();
        $this->clickModel = new PromotionClickModel();
        $this->statsModel = new PromotionStatsModel();
        $this->userModel = new UserModel();
        $this->cache = \Config\Services::cache();
        
        // Load configuration
        $this->config = [
            'unique_click_window' => 24 * 3600, // 24 hours
            'session_timeout' => 30 * 60, // 30 minutes
            'max_clicks_per_ip' => 100, // per day
            'enable_geolocation' => true,
            'enable_fraud_detection' => true,
        ];
    }

    /**
     * Track promotion click
     */
    public function trackClick(string $promotionCode, array $context = []): array
    {
        // Validate promotion
        $promotion = $this->promotionModel->getByCode($promotionCode);
        if (!$promotion) {
            throw new \InvalidArgumentException('Invalid promotion code');
        }

        // Get visitor information
        $visitorInfo = $this->getVisitorInfo($context);
        
        // Check for fraud/spam
        if ($this->config['enable_fraud_detection'] && $this->detectFraud($promotion, $visitorInfo)) {
            return [
                'success' => false,
                'reason' => 'Suspicious activity detected',
                'promotion_id' => $promotion['id'],
            ];
        }

        // Generate visitor fingerprint
        $fingerprint = $this->generateVisitorFingerprint($visitorInfo);
        
        // Prepare click data
        $clickData = [
            'ip' => $visitorInfo['ip'],
            'fingerprint' => $fingerprint,
            'user_agent' => $visitorInfo['user_agent'],
            'referrer' => $visitorInfo['referrer'],
            'landing_page' => $context['landing_page'] ?? null,
            'utm_source' => $context['utm_source'] ?? null,
            'utm_medium' => $context['utm_medium'] ?? null,
            'utm_campaign' => $context['utm_campaign'] ?? null,
            'utm_term' => $context['utm_term'] ?? null,
            'utm_content' => $context['utm_content'] ?? null,
            'metadata' => $context['metadata'] ?? [],
        ];

        // Add geolocation if enabled
        if ($this->config['enable_geolocation']) {
            $location = $this->getLocationFromIP($visitorInfo['ip']);
            $clickData = array_merge($clickData, $location);
        }

        // Record click
        $clickId = $this->clickModel->recordClick(
            $promotion['id'], 
            $promotion['server_id'], 
            $clickData
        );

        if (!$clickId) {
            throw new \RuntimeException('Failed to record click');
        }

        // Update daily statistics
        $this->updateDailyStats($promotion['id'], $promotion['server_id'], $promotion['promoter_id']);

        // Store session for conversion tracking
        $this->storeVisitorSession($promotion['id'], $clickId, $fingerprint);

        return [
            'success' => true,
            'click_id' => $clickId,
            'promotion_id' => $promotion['id'],
            'server_id' => $promotion['server_id'],
            'redirect_url' => $promotion['promotion_link'],
        ];
    }

    /**
     * Track conversion (user registration)
     */
    public function trackConversion(int $userId, array $context = []): array
    {
        // Get visitor fingerprint from session or context
        $fingerprint = $context['fingerprint'] ?? $this->generateVisitorFingerprint($this->getVisitorInfo($context));
        
        // Find recent clicks for this visitor
        $recentClicks = $this->findRecentClicksForVisitor($fingerprint, $context['ip'] ?? null);
        
        $conversions = [];
        
        foreach ($recentClicks as $click) {
            if ($click['is_converted']) {
                continue; // Already converted
            }
            
            // Mark click as converted
            if ($this->clickModel->markAsConverted($click['id'], $userId)) {
                $conversions[] = [
                    'click_id' => $click['id'],
                    'promotion_id' => $click['promotion_id'],
                    'server_id' => $click['server_id'],
                ];
                
                // Update promotion statistics
                $this->updateConversionStats($click['promotion_id'], $click['server_id'], $click['promoter_id']);
                
                // Trigger reward processing
                $this->triggerRewardProcessing($click['promotion_id'], $userId, $context);
            }
        }

        return [
            'success' => count($conversions) > 0,
            'conversions' => $conversions,
            'user_id' => $userId,
        ];
    }

    /**
     * Get visitor information
     */
    private function getVisitorInfo(array $context = []): array
    {
        return [
            'ip' => $context['ip'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'user_agent' => $context['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? '',
            'referrer' => $context['referrer'] ?? $_SERVER['HTTP_REFERER'] ?? null,
            'accept_language' => $context['accept_language'] ?? $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null,
            'timestamp' => time(),
        ];
    }

    /**
     * Generate visitor fingerprint
     */
    private function generateVisitorFingerprint(array $visitorInfo): string
    {
        $components = [
            $visitorInfo['ip'],
            $visitorInfo['user_agent'],
            $visitorInfo['accept_language'],
        ];
        
        return hash('sha256', implode('|', $components));
    }

    /**
     * Detect fraud/spam
     */
    private function detectFraud(array $promotion, array $visitorInfo): bool
    {
        $ip = $visitorInfo['ip'];
        $promotionId = $promotion['id'];
        
        // Check daily click limit per IP
        $dailyClicks = $this->clickModel->where('visitor_ip', $ip)
                                       ->where('promotion_id', $promotionId)
                                       ->where('created_at >=', date('Y-m-d 00:00:00'))
                                       ->countAllResults();
        
        if ($dailyClicks >= $this->config['max_clicks_per_ip']) {
            return true;
        }
        
        // Check for bot-like behavior
        $userAgent = strtolower($visitorInfo['user_agent']);
        $botKeywords = ['bot', 'crawler', 'spider', 'scraper', 'curl', 'wget'];
        
        foreach ($botKeywords as $keyword) {
            if (strpos($userAgent, $keyword) !== false) {
                return true;
            }
        }
        
        // Check for suspicious referrer patterns
        if ($visitorInfo['referrer']) {
            $suspiciousReferrers = ['localhost', '127.0.0.1', 'test.', 'staging.'];
            $referrerHost = parse_url($visitorInfo['referrer'], PHP_URL_HOST);
            
            foreach ($suspiciousReferrers as $suspicious) {
                if (strpos($referrerHost, $suspicious) !== false) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Get location from IP address
     */
    private function getLocationFromIP(string $ip): array
    {
        // Check cache first
        $cacheKey = "geo_location_{$ip}";
        $cached = $this->cache->get($cacheKey);
        
        if ($cached) {
            return $cached;
        }
        
        // Default location data
        $location = [
            'country' => null,
            'region' => null,
            'city' => null,
        ];
        
        // Skip for local IPs
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            // You can integrate with services like MaxMind, IPApi, etc.
            // For now, we'll use a simple free service
            try {
                $response = file_get_contents("http://ip-api.com/json/{$ip}?fields=country,regionName,city");
                if ($response) {
                    $data = json_decode($response, true);
                    if ($data && $data['status'] === 'success') {
                        $location = [
                            'country' => $data['countryCode'] ?? null,
                            'region' => $data['regionName'] ?? null,
                            'city' => $data['city'] ?? null,
                        ];
                    }
                }
            } catch (\Exception $e) {
                // Ignore geolocation errors
                log_message('warning', 'Geolocation failed for IP ' . $ip . ': ' . $e->getMessage());
            }
        }
        
        // Cache for 24 hours
        $this->cache->save($cacheKey, $location, 24 * 3600);
        
        return $location;
    }

    /**
     * Update daily statistics
     */
    private function updateDailyStats(int $promotionId, int $serverId, int $promoterId): void
    {
        $click = $this->clickModel->orderBy('id', 'DESC')
                                 ->where('promotion_id', $promotionId)
                                 ->first();
        
        $stats = [
            'clicks' => 1,
            'unique_clicks' => $click['is_unique'] ? 1 : 0,
            'conversions' => 0,
        ];
        
        $this->statsModel->updateDailyStats($promotionId, $serverId, $promoterId, $stats);
    }

    /**
     * Update conversion statistics
     */
    private function updateConversionStats(int $promotionId, int $serverId, int $promoterId): void
    {
        $stats = [
            'clicks' => 0,
            'unique_clicks' => 0,
            'conversions' => 1,
        ];
        
        $this->statsModel->updateDailyStats($promotionId, $serverId, $promoterId, $stats);
    }

    /**
     * Store visitor session for conversion tracking
     */
    private function storeVisitorSession(int $promotionId, int $clickId, string $fingerprint): void
    {
        $sessionData = [
            'promotion_id' => $promotionId,
            'click_id' => $clickId,
            'fingerprint' => $fingerprint,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        
        // Store in cache for session timeout duration
        $cacheKey = "visitor_session_{$fingerprint}";
        $this->cache->save($cacheKey, $sessionData, $this->config['session_timeout']);
    }

    /**
     * Find recent clicks for visitor
     */
    private function findRecentClicksForVisitor(string $fingerprint, ?string $ip = null): array
    {
        $builder = $this->clickModel->where('created_at >=', 
                                          date('Y-m-d H:i:s', time() - $this->config['unique_click_window']));
        
        if ($fingerprint) {
            $builder->where('visitor_fingerprint', $fingerprint);
        } elseif ($ip) {
            $builder->where('visitor_ip', $ip);
        } else {
            return [];
        }
        
        return $builder->orderBy('created_at', 'DESC')
                       ->limit(10) // Limit to prevent abuse
                       ->findAll();
    }

    /**
     * Trigger reward processing
     */
    private function triggerRewardProcessing(int $promotionId, int $userId, array $context = []): void
    {
        // This would integrate with the RewardCalculator
        $rewardCalculator = new RewardCalculator();
        
        try {
            $rewardCalculator->processPromotionReward($promotionId, $userId, $context);
        } catch (\Exception $e) {
            log_message('error', 'Failed to process promotion reward: ' . $e->getMessage());
        }
    }

    /**
     * Get promotion tracking analytics
     */
    public function getTrackingAnalytics(int $promotionId, string $period = '30 days'): array
    {
        return $this->clickModel->getClickAnalytics($promotionId, $period);
    }

    /**
     * Get conversion funnel
     */
    public function getConversionFunnel(int $promotionId, string $period = '30 days'): array
    {
        return $this->clickModel->getConversionFunnel($promotionId, $period);
    }

    /**
     * Get referrer analysis
     */
    public function getReferrerAnalysis(int $promotionId, string $period = '30 days'): array
    {
        return $this->clickModel->getReferrerAnalysis($promotionId, $period);
    }

    /**
     * Generate tracking pixel response
     */
    public function generateTrackingPixel(int $promotionId, array $context = []): string
    {
        // Track the pixel request
        try {
            $promotion = $this->promotionModel->find($promotionId);
            if ($promotion) {
                $this->trackClick($promotion['promotion_code'], array_merge($context, [
                    'tracking_type' => 'pixel',
                ]));
            }
        } catch (\Exception $e) {
            // Silent fail for pixel tracking
            log_message('warning', 'Pixel tracking failed: ' . $e->getMessage());
        }
        
        // Return 1x1 transparent GIF
        return base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    }

    /**
     * Clean expired sessions
     */
    public function cleanExpiredSessions(): int
    {
        // This would be called by a scheduled task
        $expiredTime = date('Y-m-d H:i:s', time() - $this->config['unique_click_window']);
        
        return $this->clickModel->where('created_at <', $expiredTime)
                               ->where('is_converted', false)
                               ->delete();
    }

    /**
     * Get click heatmap data
     */
    public function getClickHeatmap(int $promotionId, string $period = '30 days'): array
    {
        return $this->clickModel->getClickHeatmap($promotionId, $period);
    }

    /**
     * Export tracking data
     */
    public function exportTrackingData(int $promotionId, string $format = 'csv', array $options = []): string
    {
        $dateFrom = $options['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $dateTo = $options['date_to'] ?? date('Y-m-d');
        
        $clicks = $this->clickModel->select('*')
                                  ->where('promotion_id', $promotionId)
                                  ->where('created_at >=', $dateFrom . ' 00:00:00')
                                  ->where('created_at <=', $dateTo . ' 23:59:59')
                                  ->orderBy('created_at', 'DESC')
                                  ->findAll();
        
        if ($format === 'csv') {
            return $this->generateCSV($clicks);
        } elseif ($format === 'json') {
            return json_encode($clicks, JSON_PRETTY_PRINT);
        }
        
        throw new \InvalidArgumentException('Unsupported export format');
    }

    /**
     * Generate CSV from data
     */
    private function generateCSV(array $data): string
    {
        if (empty($data)) {
            return '';
        }
        
        $csv = '';
        
        // Headers
        $headers = array_keys($data[0]);
        $csv .= implode(',', $headers) . "\n";
        
        // Data rows
        foreach ($data as $row) {
            $csvRow = [];
            foreach ($row as $value) {
                // Escape CSV values
                if (is_string($value) && (strpos($value, ',') !== false || strpos($value, '"') !== false)) {
                    $value = '"' . str_replace('"', '""', $value) . '"';
                }
                $csvRow[] = $value;
            }
            $csv .= implode(',', $csvRow) . "\n";
        }
        
        return $csv;
    }

    /**
     * Get real-time tracking statistics  
     */
    public function getRealTimeStats(int $promotionId): array
    {
        $cacheKey = "realtime_stats_{$promotionId}";
        
        // Try to get from cache first
        $stats = $this->cache->get($cacheKey);
        
        if (!$stats) {
            $stats = [
                'clicks_last_hour' => $this->clickModel->where('promotion_id', $promotionId)
                                                      ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-1 hour')))
                                                      ->countAllResults(),
                'clicks_today' => $this->clickModel->where('promotion_id', $promotionId)
                                                  ->where('created_at >=', date('Y-m-d 00:00:00'))
                                                  ->countAllResults(),
                'unique_visitors_today' => $this->clickModel->where('promotion_id', $promotionId)
                                                           ->where('created_at >=', date('Y-m-d 00:00:00'))
                                                           ->where('is_unique', true)
                                                           ->countAllResults(),
                'conversions_today' => $this->clickModel->where('promotion_id', $promotionId)
                                                       ->where('created_at >=', date('Y-m-d 00:00:00'))
                                                       ->where('is_converted', true)
                                                       ->countAllResults(),
            ];
            
            // Cache for 5 minutes
            $this->cache->save($cacheKey, $stats, 300);
        }
        
        return $stats;
    }
}