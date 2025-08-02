<?php

namespace App\Models;

use CodeIgniter\Model;

class PromotionClickModel extends Model
{
    protected $table = 'promotion_clicks';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;

    protected $allowedFields = [
        'promotion_id', 'server_id', 'visitor_ip', 'visitor_fingerprint',
        'user_agent', 'referrer_url', 'landing_page', 'utm_source', 'utm_medium',
        'utm_campaign', 'utm_term', 'utm_content', 'device_type', 'browser',
        'os', 'country', 'region', 'city', 'is_unique', 'is_converted',
        'converted_user_id', 'converted_at', 'session_duration', 'metadata'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'promotion_id' => 'required|integer|is_not_unique[promotions.id]',
        'server_id' => 'required|integer|is_not_unique[servers.id]',
        'visitor_ip' => 'required|valid_ip',
        'device_type' => 'permit_empty|in_list[desktop,mobile,tablet,unknown]',
    ];

    protected $validationMessages = [
        'promotion_id' => [
            'required' => 'Promotion ID is required',
            'is_not_unique' => 'Promotion does not exist',
        ],
        'server_id' => [
            'required' => 'Server ID is required',
            'is_not_unique' => 'Server does not exist',
        ],
        'visitor_ip' => [
            'required' => 'Visitor IP is required',
            'valid_ip' => 'Invalid IP address',
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = ['checkUniqueClick', 'parseUserAgent'];

    /**
     * Check if this is a unique click
     */
    protected function checkUniqueClick(array $data)
    {
        $promotionId = $data['data']['promotion_id'];
        $visitorIp = $data['data']['visitor_ip'];
        $fingerprint = $data['data']['visitor_fingerprint'] ?? null;

        // Check for recent clicks from same IP or fingerprint
        $builder = $this->where('promotion_id', $promotionId)
                        ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-24 hours')));

        if ($fingerprint) {
            $builder->where('visitor_fingerprint', $fingerprint);
        } else {
            $builder->where('visitor_ip', $visitorIp);
        }

        $recentClick = $builder->first();
        $data['data']['is_unique'] = !$recentClick;

        return $data;
    }

    /**
     * Parse user agent information
     */
    protected function parseUserAgent(array $data)
    {
        $userAgent = $data['data']['user_agent'] ?? '';
        
        if (empty($userAgent)) {
            return $data;
        }

        // Simple user agent parsing (you might want to use a library for this)
        $data['data']['device_type'] = $this->detectDeviceType($userAgent);
        $data['data']['browser'] = $this->detectBrowser($userAgent);
        $data['data']['os'] = $this->detectOS($userAgent);

        return $data;
    }

    /**
     * Detect device type from user agent
     */
    private function detectDeviceType(string $userAgent): string
    {
        $userAgent = strtolower($userAgent);
        
        if (strpos($userAgent, 'mobile') !== false || strpos($userAgent, 'android') !== false || strpos($userAgent, 'iphone') !== false) {
            return 'mobile';
        } elseif (strpos($userAgent, 'tablet') !== false || strpos($userAgent, 'ipad') !== false) {
            return 'tablet';
        } elseif (strpos($userAgent, 'mozilla') !== false || strpos($userAgent, 'chrome') !== false || strpos($userAgent, 'safari') !== false) {
            return 'desktop';
        }
        
        return 'unknown';
    }

    /**
     * Detect browser from user agent
     */
    private function detectBrowser(string $userAgent): ?string
    {
        $userAgent = strtolower($userAgent);
        
        if (strpos($userAgent, 'chrome') !== false && strpos($userAgent, 'edg') === false) {
            return 'Chrome';
        } elseif (strpos($userAgent, 'firefox') !== false) {
            return 'Firefox';
        } elseif (strpos($userAgent, 'safari') !== false && strpos($userAgent, 'chrome') === false) {
            return 'Safari';
        } elseif (strpos($userAgent, 'edg') !== false) {
            return 'Edge';
        } elseif (strpos($userAgent, 'opera') !== false || strpos($userAgent, 'opr') !== false) {
            return 'Opera';
        }
        
        return null;
    }

    /**
     * Detect OS from user agent
     */
    private function detectOS(string $userAgent): ?string
    {
        $userAgent = strtolower($userAgent);
        
        if (strpos($userAgent, 'windows') !== false) {
            return 'Windows';
        } elseif (strpos($userAgent, 'macintosh') !== false || strpos($userAgent, 'mac os') !== false) {
            return 'macOS';
        } elseif (strpos($userAgent, 'linux') !== false) {
            return 'Linux';
        } elseif (strpos($userAgent, 'android') !== false) {
            return 'Android';
        } elseif (strpos($userAgent, 'iphone') !== false || strpos($userAgent, 'ipad') !== false) {
            return 'iOS';
        }
        
        return null;
    }

    /**
     * Record click
     */
    public function recordClick(int $promotionId, int $serverId, array $clickData): ?int
    {
        $data = [
            'promotion_id' => $promotionId,
            'server_id' => $serverId,
            'visitor_ip' => $clickData['ip'] ?? $_SERVER['REMOTE_ADDR'],
            'visitor_fingerprint' => $clickData['fingerprint'] ?? null,
            'user_agent' => $clickData['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? null,
            'referrer_url' => $clickData['referrer'] ?? $_SERVER['HTTP_REFERER'] ?? null,
            'landing_page' => $clickData['landing_page'] ?? null,
            'utm_source' => $clickData['utm_source'] ?? null,
            'utm_medium' => $clickData['utm_medium'] ?? null,
            'utm_campaign' => $clickData['utm_campaign'] ?? null,
            'utm_term' => $clickData['utm_term'] ?? null,
            'utm_content' => $clickData['utm_content'] ?? null,
            'country' => $clickData['country'] ?? null,
            'region' => $clickData['region'] ?? null,
            'city' => $clickData['city'] ?? null,
            'metadata' => isset($clickData['metadata']) ? json_encode($clickData['metadata']) : null,
        ];

        $clickId = $this->insert($data);
        
        if ($clickId) {
            // Update promotion click count
            $promotionModel = new PromotionModel();
            $click = $this->find($clickId);
            $promotionModel->incrementClick($promotionId, $click['is_unique']);
        }

        return $clickId;
    }

    /**
     * Mark click as converted
     */
    public function markAsConverted(int $clickId, int $convertedUserId): bool
    {
        $click = $this->find($clickId);
        if (!$click || $click['is_converted']) {
            return false;
        }

        $result = $this->update($clickId, [
            'is_converted' => true,
            'converted_user_id' => $convertedUserId,
            'converted_at' => date('Y-m-d H:i:s'),
        ]);

        if ($result) {
            // Update promotion conversion count
            $promotionModel = new PromotionModel();
            $promotionModel->incrementConversion($click['promotion_id'], $convertedUserId);
        }

        return $result;
    }

    /**
     * Get click analytics
     */
    public function getClickAnalytics(int $promotionId, string $period = '30 days'): array
    {
        $dateFrom = date('Y-m-d H:i:s', strtotime("-{$period}"));

        // Basic stats
        $basicStats = $this->select('COUNT(*) as total_clicks,
                                    COUNT(DISTINCT visitor_ip) as unique_visitors,
                                    SUM(is_unique) as unique_clicks,
                                    SUM(is_converted) as conversions,
                                    AVG(session_duration) as avg_session_duration')
                           ->where('promotion_id', $promotionId)
                           ->where('created_at >=', $dateFrom)
                           ->first();

        // Device breakdown
        $deviceStats = $this->select('device_type, COUNT(*) as count')
                            ->where('promotion_id', $promotionId)
                            ->where('created_at >=', $dateFrom)
                            ->groupBy('device_type')
                            ->findAll();

        // Browser breakdown
        $browserStats = $this->select('browser, COUNT(*) as count')
                             ->where('promotion_id', $promotionId)
                             ->where('created_at >=', $dateFrom)
                             ->where('browser IS NOT NULL')
                             ->groupBy('browser')
                             ->orderBy('count', 'DESC')
                             ->limit(10)
                             ->findAll();

        // Daily trends
        $dailyTrends = $this->select('DATE(created_at) as date,
                                     COUNT(*) as clicks,
                                     SUM(is_unique) as unique_clicks,
                                     SUM(is_converted) as conversions')
                            ->where('promotion_id', $promotionId)
                            ->where('created_at >=', $dateFrom)
                            ->groupBy('DATE(created_at)')
                            ->orderBy('date', 'ASC')
                            ->findAll();

        // Traffic sources
        $trafficSources = $this->select('utm_source, utm_medium, COUNT(*) as count')
                               ->where('promotion_id', $promotionId)
                               ->where('created_at >=', $dateFrom)
                               ->where('utm_source IS NOT NULL')
                               ->groupBy(['utm_source', 'utm_medium'])
                               ->orderBy('count', 'DESC')
                               ->limit(10)
                               ->findAll();

        // Geographic data
        $geoData = $this->select('country, region, city, COUNT(*) as count')
                        ->where('promotion_id', $promotionId)
                        ->where('created_at >=', $dateFrom)
                        ->where('country IS NOT NULL')
                        ->groupBy(['country', 'region', 'city'])
                        ->orderBy('count', 'DESC')
                        ->limit(20)
                        ->findAll();

        return [
            'basic_stats' => $basicStats,
            'device_breakdown' => $deviceStats,
            'browser_breakdown' => $browserStats,
            'daily_trends' => $dailyTrends,
            'traffic_sources' => $trafficSources,
            'geographic_data' => $geoData,
            'period' => $period,
            'date_from' => $dateFrom,
        ];
    }

    /**
     * Get conversion funnel
     */
    public function getConversionFunnel(int $promotionId, string $period = '30 days'): array
    {
        $dateFrom = date('Y-m-d H:i:s', strtotime("-{$period}"));

        $stats = $this->select('COUNT(*) as total_clicks,
                               SUM(is_unique) as unique_clicks,
                               SUM(is_converted) as conversions')
                      ->where('promotion_id', $promotionId)
                      ->where('created_at >=', $dateFrom)
                      ->first();

        $totalClicks = $stats['total_clicks'];
        $uniqueClicks = $stats['unique_clicks'];
        $conversions = $stats['conversions'];

        return [
            'steps' => [
                [
                    'name' => 'Total Clicks',
                    'count' => $totalClicks,
                    'percentage' => 100,
                ],
                [
                    'name' => 'Unique Visitors',
                    'count' => $uniqueClicks,
                    'percentage' => $totalClicks > 0 ? round(($uniqueClicks / $totalClicks) * 100, 2) : 0,
                ],
                [
                    'name' => 'Conversions',
                    'count' => $conversions,
                    'percentage' => $uniqueClicks > 0 ? round(($conversions / $uniqueClicks) * 100, 2) : 0,
                ],
            ],
            'conversion_rate' => $uniqueClicks > 0 ? round(($conversions / $uniqueClicks) * 100, 2) : 0,
            'click_to_unique_rate' => $totalClicks > 0 ? round(($uniqueClicks / $totalClicks) * 100, 2) : 0,
        ];
    }

    /**
     * Get referrer analysis
     */
    public function getReferrerAnalysis(int $promotionId, string $period = '30 days'): array
    {
        $dateFrom = date('Y-m-d H:i:s', strtotime("-{$period}"));

        // Parse referrer domains
        $referrers = $this->select('referrer_url, COUNT(*) as count, SUM(is_converted) as conversions')
                          ->where('promotion_id', $promotionId)
                          ->where('created_at >=', $dateFrom)
                          ->where('referrer_url IS NOT NULL')
                          ->groupBy('referrer_url')
                          ->orderBy('count', 'DESC')
                          ->limit(20)
                          ->findAll();

        // Process and group by domain
        $domainStats = [];
        foreach ($referrers as $referrer) {
            $domain = parse_url($referrer['referrer_url'], PHP_URL_HOST);
            if ($domain) {
                if (!isset($domainStats[$domain])) {
                    $domainStats[$domain] = [
                        'domain' => $domain,
                        'clicks' => 0,
                        'conversions' => 0,
                        'urls' => [],
                    ];
                }
                $domainStats[$domain]['clicks'] += $referrer['count'];
                $domainStats[$domain]['conversions'] += $referrer['conversions'];
                $domainStats[$domain]['urls'][] = [
                    'url' => $referrer['referrer_url'],
                    'clicks' => $referrer['count'],
                    'conversions' => $referrer['conversions'],
                ];
            }
        }

        // Sort by clicks
        uasort($domainStats, function($a, $b) {
            return $b['clicks'] - $a['clicks'];
        });

        return [
            'raw_referrers' => $referrers,
            'domain_stats' => array_values($domainStats),
        ];
    }

    /**
     * Clean old clicks
     */
    public function cleanOldClicks(int $daysToKeep = 90): int
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));
        
        return $this->where('created_at <', $cutoffDate)
                    ->delete();
    }

    /**
     * Get click heatmap data
     */
    public function getClickHeatmap(int $promotionId, string $period = '30 days'): array
    {
        $dateFrom = date('Y-m-d H:i:s', strtotime("-{$period}"));

        // Hourly distribution
        $hourlyData = $this->select('HOUR(created_at) as hour, COUNT(*) as count')
                           ->where('promotion_id', $promotionId)
                           ->where('created_at >=', $dateFrom)
                           ->groupBy('HOUR(created_at)')
                           ->orderBy('hour', 'ASC')
                           ->findAll();

        // Day of week distribution
        $dailyData = $this->select('DAYOFWEEK(created_at) as day_of_week, COUNT(*) as count')
                          ->where('promotion_id', $promotionId)
                          ->where('created_at >=', $dateFrom)
                          ->groupBy('DAYOFWEEK(created_at)')
                          ->orderBy('day_of_week', 'ASC')
                          ->findAll();

        return [
            'hourly_distribution' => $hourlyData,
            'daily_distribution' => $dailyData,
        ];
    }
}