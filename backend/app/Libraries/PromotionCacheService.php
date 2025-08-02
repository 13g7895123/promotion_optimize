<?php

namespace App\Libraries;

use CodeIgniter\Cache\CacheInterface;
use App\Models\PromotionModel;
use App\Models\PromotionStatsModel;
use App\Models\RewardModel;
use App\Models\RewardSettingModel;

class PromotionCacheService
{
    private CacheInterface $cache;
    private array $config;

    public function __construct()
    {
        $this->cache = \Config\Services::cache();
        
        $this->config = [
            'default_ttl' => 3600, // 1 hour
            'promotion_ttl' => 1800, // 30 minutes
            'stats_ttl' => 300, // 5 minutes
            'settings_ttl' => 3600, // 1 hour
            'user_data_ttl' => 900, // 15 minutes
            'analytics_ttl' => 600, // 10 minutes
        ];
    }

    /**
     * Cache promotion data
     */
    public function cachePromotion(int $promotionId, array $promotionData): bool
    {
        $cacheKey = $this->getPromotionCacheKey($promotionId);
        return $this->cache->save($cacheKey, $promotionData, $this->config['promotion_ttl']);
    }

    /**
     * Get cached promotion data
     */
    public function getCachedPromotion(int $promotionId): ?array
    {
        $cacheKey = $this->getPromotionCacheKey($promotionId);
        return $this->cache->get($cacheKey);
    }

    /**
     * Cache promotion by code
     */
    public function cachePromotionByCode(string $code, array $promotionData): bool
    {
        $cacheKey = $this->getPromotionCodeCacheKey($code);
        return $this->cache->save($cacheKey, $promotionData, $this->config['promotion_ttl']);
    }

    /**
     * Get cached promotion by code
     */
    public function getCachedPromotionByCode(string $code): ?array
    {
        $cacheKey = $this->getPromotionCodeCacheKey($code);
        return $this->cache->get($cacheKey);
    }

    /**
     * Cache user promotions
     */
    public function cacheUserPromotions(int $userId, array $filters, int $page, array $data): bool
    {
        $cacheKey = $this->getUserPromotionsCacheKey($userId, $filters, $page);
        return $this->cache->save($cacheKey, $data, $this->config['user_data_ttl']);
    }

    /**
     * Get cached user promotions
     */
    public function getCachedUserPromotions(int $userId, array $filters, int $page): ?array
    {
        $cacheKey = $this->getUserPromotionsCacheKey($userId, $filters, $page);
        return $this->cache->get($cacheKey);
    }

    /**
     * Cache promotion statistics
     */
    public function cachePromotionStats(int $promotionId, string $period, array $statsData): bool
    {
        $cacheKey = $this->getPromotionStatsCacheKey($promotionId, $period);
        return $this->cache->save($cacheKey, $statsData, $this->config['stats_ttl']);
    }

    /**
     * Get cached promotion statistics
     */
    public function getCachedPromotionStats(int $promotionId, string $period): ?array
    {
        $cacheKey = $this->getPromotionStatsCacheKey($promotionId, $period);
        return $this->cache->get($cacheKey);
    }

    /**
     * Cache server statistics
     */
    public function cacheServerStats(int $serverId, string $period, array $statsData): bool
    {
        $cacheKey = $this->getServerStatsCacheKey($serverId, $period);
        return $this->cache->save($cacheKey, $statsData, $this->config['stats_ttl']);
    }

    /**
     * Get cached server statistics
     */
    public function getCachedServerStats(int $serverId, string $period): ?array
    {
        $cacheKey = $this->getServerStatsCacheKey($serverId, $period);
        return $this->cache->get($cacheKey);
    }

    /**
     * Cache reward settings
     */
    public function cacheRewardSettings(int $serverId, string $settingType, array $settings): bool
    {
        $cacheKey = $this->getRewardSettingsCacheKey($serverId, $settingType);
        return $this->cache->save($cacheKey, $settings, $this->config['settings_ttl']);
    }

    /**
     * Get cached reward settings
     */
    public function getCachedRewardSettings(int $serverId, string $settingType): ?array
    {
        $cacheKey = $this->getRewardSettingsCacheKey($serverId, $settingType);
        return $this->cache->get($cacheKey);
    }

    /**
     * Cache analytics data
     */
    public function cacheAnalytics(string $type, int $targetId, string $period, array $analyticsData): bool
    {
        $cacheKey = $this->getAnalyticsCacheKey($type, $targetId, $period);
        return $this->cache->save($cacheKey, $analyticsData, $this->config['analytics_ttl']);
    }

    /**
     * Get cached analytics data
     */
    public function getCachedAnalytics(string $type, int $targetId, string $period): ?array
    {
        $cacheKey = $this->getAnalyticsCacheKey($type, $targetId, $period);
        return $this->cache->get($cacheKey);
    }

    /**
     * Cache leaderboard data
     */
    public function cacheLeaderboard(string $metric, string $period, int $limit, array $leaderboardData): bool
    {
        $cacheKey = $this->getLeaderboardCacheKey($metric, $period, $limit);
        return $this->cache->save($cacheKey, $leaderboardData, $this->config['stats_ttl']);
    }

    /**
     * Get cached leaderboard data
     */
    public function getCachedLeaderboard(string $metric, string $period, int $limit): ?array
    {
        $cacheKey = $this->getLeaderboardCacheKey($metric, $period, $limit);
        return $this->cache->get($cacheKey);
    }

    /**
     * Cache dashboard data
     */
    public function cacheDashboard(int $userId, ?int $serverId, string $period, array $dashboardData): bool
    {
        $cacheKey = $this->getDashboardCacheKey($userId, $serverId, $period);
        return $this->cache->save($cacheKey, $dashboardData, $this->config['user_data_ttl']);
    }

    /**
     * Get cached dashboard data
     */
    public function getCachedDashboard(int $userId, ?int $serverId, string $period): ?array
    {
        $cacheKey = $this->getDashboardCacheKey($userId, $serverId, $period);
        return $this->cache->get($cacheKey);
    }

    /**
     * Invalidate promotion cache
     */
    public function invalidatePromotionCache(int $promotionId): bool
    {
        $keys = [
            $this->getPromotionCacheKey($promotionId),
        ];

        foreach ($keys as $key) {
            $this->cache->delete($key);
        }

        return true;
    }

    /**
     * Invalidate user promotion cache
     */
    public function invalidateUserPromotionCache(int $userId): bool
    {
        // Since we can't easily list all cache keys with filters,
        // we'll use a more targeted approach or implement cache tags
        $cacheKey = "user_promotions_{$userId}_*";
        
        // For now, we'll invalidate common combinations
        $commonFilters = [
            [],
            ['status' => 'active'],
            ['status' => 'paused'],
        ];

        foreach ($commonFilters as $filters) {
            for ($page = 1; $page <= 10; $page++) {
                $key = $this->getUserPromotionsCacheKey($userId, $filters, $page);
                $this->cache->delete($key);
            }
        }

        return true;
    }

    /**
     * Invalidate server statistics cache
     */
    public function invalidateServerStatsCache(int $serverId): bool
    {
        $periods = ['daily', 'weekly', 'monthly'];
        
        foreach ($periods as $period) {
            $key = $this->getServerStatsCacheKey($serverId, $period);
            $this->cache->delete($key);
        }

        return true;
    }

    /**
     * Invalidate reward settings cache
     */
    public function invalidateRewardSettingsCache(int $serverId): bool
    {
        $settingTypes = ['promotion', 'activity', 'checkin', 'general'];
        
        foreach ($settingTypes as $type) {
            $key = $this->getRewardSettingsCacheKey($serverId, $type);
            $this->cache->delete($key);
        }

        return true;
    }

    /**
     * Batch cache operations
     */
    public function batchSave(array $data): bool
    {
        $success = true;
        
        foreach ($data as $item) {
            $result = $this->cache->save(
                $item['key'], 
                $item['value'], 
                $item['ttl'] ?? $this->config['default_ttl']
            );
            
            if (!$result) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Batch cache retrieval
     */
    public function batchGet(array $keys): array
    {
        $results = [];
        
        foreach ($keys as $key) {
            $results[$key] = $this->cache->get($key);
        }

        return $results;
    }

    /**
     * Increment counter with expiry
     */
    public function incrementCounter(string $key, int $ttl = null): int
    {
        $ttl = $ttl ?? $this->config['default_ttl'];
        $current = $this->cache->get($key) ?? 0;
        $new = $current + 1;
        
        $this->cache->save($key, $new, $ttl);
        
        return $new;
    }

    /**
     * Decrement counter
     */
    public function decrementCounter(string $key, int $ttl = null): int
    {
        $ttl = $ttl ?? $this->config['default_ttl'];
        $current = $this->cache->get($key) ?? 0;
        $new = max(0, $current - 1);
        
        $this->cache->save($key, $new, $ttl);
        
        return $new;
    }

    /**
     * Set cache with lock
     */
    public function setWithLock(string $key, $value, int $ttl = null, int $lockTimeout = 10): bool
    {
        $lockKey = "lock_{$key}";
        $lockValue = uniqid();
        
        // Acquire lock
        if (!$this->cache->save($lockKey, $lockValue, $lockTimeout)) {
            return false;
        }

        // Set the actual value
        $result = $this->cache->save($key, $value, $ttl ?? $this->config['default_ttl']);
        
        // Release lock
        if ($this->cache->get($lockKey) === $lockValue) {
            $this->cache->delete($lockKey);
        }

        return $result;
    }

    /**
     * Cache with tags (simplified version)
     */
    public function saveWithTags(string $key, $value, array $tags, int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->config['default_ttl'];
        
        // Save the actual data
        $result = $this->cache->save($key, $value, $ttl);
        
        // Save tag mappings
        foreach ($tags as $tag) {
            $tagKey = "tag_{$tag}";
            $taggedKeys = $this->cache->get($tagKey) ?? [];
            
            if (!in_array($key, $taggedKeys)) {
                $taggedKeys[] = $key;
                $this->cache->save($tagKey, $taggedKeys, $ttl);
            }
        }

        return $result;
    }

    /**
     * Invalidate by tags
     */
    public function invalidateByTags(array $tags): bool
    {
        foreach ($tags as $tag) {
            $tagKey = "tag_{$tag}";
            $taggedKeys = $this->cache->get($tagKey) ?? [];
            
            foreach ($taggedKeys as $key) {
                $this->cache->delete($key);
            }
            
            $this->cache->delete($tagKey);
        }

        return true;
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        // This would depend on your cache driver
        // For Redis, you could use Redis::info()
        return [
            'hits' => 0,
            'misses' => 0,
            'memory_usage' => 0,
            'keys_count' => 0,
        ];
    }

    /**
     * Cache query results with smart invalidation
     */
    public function cacheQueryResult(string $queryHash, array $result, int $ttl = null, array $tags = []): bool
    {
        $ttl = $ttl ?? $this->config['default_ttl'];
        $cacheKey = "query_result_{$queryHash}";
        
        if (!empty($tags)) {
            return $this->saveWithTags($cacheKey, $result, $tags, $ttl);
        }
        
        return $this->cache->save($cacheKey, $result, $ttl);
    }

    /**
     * Get cached query result
     */
    public function getCachedQueryResult(string $queryHash): ?array
    {
        $cacheKey = "query_result_{$queryHash}";
        return $this->cache->get($cacheKey);
    }

    /**
     * Cache with automatic dependency tracking
     */
    public function cacheWithDependencies(string $key, $value, array $dependencies, int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->config['default_ttl'];
        
        // Save the actual data
        $result = $this->cache->save($key, $value, $ttl);
        
        // Track dependencies
        foreach ($dependencies as $dependency) {
            $depKey = "dep_{$dependency}";
            $dependentKeys = $this->cache->get($depKey) ?? [];
            
            if (!in_array($key, $dependentKeys)) {
                $dependentKeys[] = $key;
                $this->cache->save($depKey, $dependentKeys, $ttl);
            }
        }
        
        return $result;
    }

    /**
     * Invalidate by dependency
     */
    public function invalidateByDependency(string $dependency): bool
    {
        $depKey = "dep_{$dependency}";
        $dependentKeys = $this->cache->get($depKey) ?? [];
        
        foreach ($dependentKeys as $key) {
            $this->cache->delete($key);
        }
        
        $this->cache->delete($depKey);
        return true;
    }

    /**
     * Multi-level caching with fallback
     */
    public function getWithFallback(string $key, callable $fallback, int $ttl = null, array $tags = []): mixed
    {
        // Try to get from cache first
        $cached = $this->cache->get($key);
        if ($cached !== null) {
            return $cached;
        }
        
        // Execute fallback to get fresh data
        $data = $fallback();
        
        // Cache the result
        $ttl = $ttl ?? $this->config['default_ttl'];
        if (!empty($tags)) {
            $this->saveWithTags($key, $data, $tags, $ttl);
        } else {
            $this->cache->save($key, $data, $ttl);
        }
        
        return $data;
    }

    /**
     * Batch cache with pipeline optimization
     */
    public function batchCacheWithPipeline(array $operations): array
    {
        $results = [];
        
        // Group operations by type for optimization
        $saves = [];
        $gets = [];
        
        foreach ($operations as $op) {
            if ($op['type'] === 'save') {
                $saves[] = $op;
            } elseif ($op['type'] === 'get') {
                $gets[] = $op;
            }
        }
        
        // Execute batch gets
        foreach ($gets as $get) {
            $results[$get['key']] = $this->cache->get($get['key']);
        }
        
        // Execute batch saves
        foreach ($saves as $save) {
            $results[$save['key']] = $this->cache->save(
                $save['key'], 
                $save['value'], 
                $save['ttl'] ?? $this->config['default_ttl']
            );
        }
        
        return $results;
    }

    /**
     * Cache warming with priority queue
     */
    public function warmCacheWithPriority(array $warmupTasks): bool
    {
        // Sort by priority (higher first)
        usort($warmupTasks, function($a, $b) {
            return ($b['priority'] ?? 0) - ($a['priority'] ?? 0);
        });
        
        foreach ($warmupTasks as $task) {
            try {
                if (isset($task['callback'])) {
                    $data = call_user_func($task['callback']);
                    $this->cache->save(
                        $task['key'], 
                        $data, 
                        $task['ttl'] ?? $this->config['default_ttl']
                    );
                }
            } catch (\Exception $e) {
                log_message('warning', 'Cache warmup failed for key: ' . $task['key'] . ' - ' . $e->getMessage());
                continue;
            }
        }
        
        return true;
    }

    /**
     * Smart cache eviction based on usage patterns
     */
    public function smartEviction(): int
    {
        $evicted = 0;
        
        // This is a simplified version - in production you might use Redis LRU
        // or implement more sophisticated eviction strategies
        
        try {
            // Identify low-usage cache entries
            $usageStats = $this->cache->get('cache_usage_stats') ?? [];
            
            foreach ($usageStats as $key => $stats) {
                if ($stats['last_accessed'] < strtotime('-1 hour') && $stats['access_count'] < 5) {
                    $this->cache->delete($key);
                    $evicted++;
                }
            }
            
        } catch (\Exception $e) {
            log_message('warning', 'Smart eviction failed: ' . $e->getMessage());
        }
        
        return $evicted;
    }

    /**
     * Cache performance monitoring
     */
    public function getCachePerformanceMetrics(): array
    {
        return [
            'hit_rate' => $this->calculateHitRate(),
            'memory_usage' => $this->getCacheMemoryUsage(),
            'average_response_time' => $this->getAverageResponseTime(),
            'most_accessed_keys' => $this->getMostAccessedKeys(),
            'cache_size' => $this->getCacheSize(),
            'eviction_rate' => $this->getEvictionRate(),
        ];
    }

    /**
     * Warm up cache
     */
    public function warmUpCache(): bool
    {
        try {
            $promotionModel = new PromotionModel();
            $statsModel = new PromotionStatsModel();
            $settingModel = new RewardSettingModel();

            // Warm up active promotions
            $activePromotions = $promotionModel->where('status', 'active')
                                             ->limit(100)
                                             ->findAll();

            foreach ($activePromotions as $promotion) {
                $this->cachePromotion($promotion['id'], $promotion);
                $this->cachePromotionByCode($promotion['promotion_code'], $promotion);
            }

            // Warm up recent statistics
            $recentStats = $statsModel->where('stat_date >=', date('Y-m-d', strtotime('-7 days')))
                                    ->findAll();

            foreach ($recentStats as $stat) {
                $this->cachePromotionStats(
                    $stat['promotion_id'], 
                    $stat['stat_type'], 
                    $stat
                );
            }

            // Warm up active reward settings
            $activeSettings = $settingModel->where('is_active', true)->findAll();
            
            $serverSettings = [];
            foreach ($activeSettings as $setting) {
                $serverId = $setting['server_id'];
                $settingType = $setting['setting_type'];
                
                if (!isset($serverSettings[$serverId][$settingType])) {
                    $serverSettings[$serverId][$settingType] = [];
                }
                
                $serverSettings[$serverId][$settingType][] = $setting;
            }

            foreach ($serverSettings as $serverId => $settings) {
                foreach ($settings as $settingType => $settingData) {
                    $this->cacheRewardSettings($serverId, $settingType, $settingData);
                }
            }

            return true;
            
        } catch (\Exception $e) {
            log_message('error', 'Cache warm-up failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear all promotion-related cache
     */
    public function clearAllPromotionCache(): bool
    {
        // This is a simplified approach - in production you might want
        // to use cache tags or a more sophisticated method
        $patterns = [
            'promotion_*',
            'user_promotions_*',
            'promotion_stats_*',
            'server_stats_*',
            'reward_settings_*',
            'analytics_*',
            'leaderboard_*',
            'dashboard_*',
        ];

        foreach ($patterns as $pattern) {
            // Note: This is pseudo-code - actual implementation depends on cache driver
            // For Redis: $redis->del($redis->keys($pattern));
        }

        return true;
    }

    // Private helper methods for generating cache keys

    private function getPromotionCacheKey(int $promotionId): string
    {
        return "promotion_{$promotionId}";
    }

    private function getPromotionCodeCacheKey(string $code): string
    {
        return "promotion_code_{$code}";
    }

    private function getUserPromotionsCacheKey(int $userId, array $filters, int $page): string
    {
        $filterHash = md5(serialize($filters));
        return "user_promotions_{$userId}_{$filterHash}_{$page}";
    }

    private function getPromotionStatsCacheKey(int $promotionId, string $period): string
    {
        return "promotion_stats_{$promotionId}_{$period}";
    }

    private function getServerStatsCacheKey(int $serverId, string $period): string
    {
        return "server_stats_{$serverId}_{$period}";
    }

    private function getRewardSettingsCacheKey(int $serverId, string $settingType): string
    {
        return "reward_settings_{$serverId}_{$settingType}";
    }

    private function getAnalyticsCacheKey(string $type, int $targetId, string $period): string
    {
        return "analytics_{$type}_{$targetId}_{$period}";
    }

    private function getLeaderboardCacheKey(string $metric, string $period, int $limit): string
    {
        return "leaderboard_{$metric}_{$period}_{$limit}";
    }

    private function getDashboardCacheKey(int $userId, ?int $serverId, string $period): string
    {
        $serverPart = $serverId ? "_{$serverId}" : '';
        return "dashboard_{$userId}{$serverPart}_{$period}";
    }

    // Performance monitoring helper methods
    
    private function calculateHitRate(): float
    {
        $stats = $this->cache->get('cache_hit_stats') ?? ['hits' => 0, 'misses' => 0];
        $total = $stats['hits'] + $stats['misses'];
        return $total > 0 ? round($stats['hits'] / $total * 100, 2) : 0.0;
    }

    private function getCacheMemoryUsage(): int
    {
        // This would depend on your cache driver
        // For Redis: return memory_get_usage();
        return memory_get_usage();
    }

    private function getAverageResponseTime(): float
    {
        $times = $this->cache->get('cache_response_times') ?? [];
        return !empty($times) ? round(array_sum($times) / count($times), 2) : 0.0;
    }

    private function getMostAccessedKeys(): array
    {
        $stats = $this->cache->get('cache_access_stats') ?? [];
        arsort($stats);
        return array_slice($stats, 0, 10, true);
    }

    private function getCacheSize(): int
    {
        // This would depend on your cache implementation
        return 0;
    }

    private function getEvictionRate(): float
    {
        $evictions = $this->cache->get('cache_evictions') ?? 0;
        $total = $this->cache->get('cache_total_operations') ?? 1;
        return round($evictions / $total * 100, 2);
    }
}