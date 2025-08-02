<?php

namespace App\Libraries;

use CodeIgniter\Cache\CacheInterface;
use CodeIgniter\Database\BaseConnection;

class SystemMonitorService
{
    private CacheInterface $cache;
    private BaseConnection $db;
    private array $config;

    public function __construct()
    {
        $this->cache = service('cache');
        $this->db = \Config\Database::connect();
        
        $this->config = [
            'health_check_interval' => 300, // 5 minutes
            'performance_metrics_ttl' => 600, // 10 minutes
            'alert_thresholds' => [
                'cpu_usage' => 80.0,
                'memory_usage' => 85.0,
                'disk_usage' => 90.0,
                'database_connections' => 80,
                'response_time' => 2000, // ms
                'error_rate' => 5.0, // percentage
            ]
        ];
    }

    /**
     * Perform comprehensive system health check
     */
    public function performHealthCheck(): array
    {
        $healthCheck = [
            'timestamp' => date('Y-m-d H:i:s'),
            'overall_status' => 'healthy',
            'checks' => []
        ];

        // Database connectivity check
        $healthCheck['checks']['database'] = $this->checkDatabaseHealth();
        
        // Cache system check
        $healthCheck['checks']['cache'] = $this->checkCacheHealth();
        
        // File system check
        $healthCheck['checks']['filesystem'] = $this->checkFileSystemHealth();
        
        // System resources check
        $healthCheck['checks']['system_resources'] = $this->checkSystemResources();
        
        // Application-specific checks
        $healthCheck['checks']['application'] = $this->checkApplicationHealth();
        
        // Service dependencies check
        $healthCheck['checks']['dependencies'] = $this->checkDependencies();

        // Determine overall status
        $healthCheck['overall_status'] = $this->calculateOverallStatus($healthCheck['checks']);
        
        // Cache health check results
        $this->cache->save('system_health_check', $healthCheck, $this->config['health_check_interval']);

        return $healthCheck;
    }

    /**
     * Check database health
     */
    private function checkDatabaseHealth(): array
    {
        $check = [
            'status' => 'healthy',
            'response_time' => 0,
            'connection_count' => 0,
            'issues' => []
        ];

        try {
            $startTime = microtime(true);
            
            // Test basic connectivity
            $result = $this->db->query("SELECT 1");
            if (!$result) {
                throw new \Exception('Database query failed');
            }
            
            $check['response_time'] = round((microtime(true) - $startTime) * 1000, 2);
            
            // Check connection count (MySQL specific)
            try {
                $connections = $this->db->query("SHOW STATUS LIKE 'Threads_connected'")->getRow();
                $check['connection_count'] = (int)($connections->Value ?? 0);
                
                if ($check['connection_count'] > $this->config['alert_thresholds']['database_connections']) {
                    $check['status'] = 'warning';
                    $check['issues'][] = 'High database connection count';
                }
            } catch (\Exception $e) {
                // Connection count check failed, but basic connectivity works
            }
            
            // Check for long-running queries
            try {
                $longQueries = $this->db->query("SELECT COUNT(*) as count FROM information_schema.processlist WHERE time > 30")->getRow();
                if (($longQueries->count ?? 0) > 5) {
                    $check['status'] = 'warning';
                    $check['issues'][] = 'Multiple long-running queries detected';
                }
            } catch (\Exception $e) {
                // Long query check failed
            }

        } catch (\Exception $e) {
            $check['status'] = 'unhealthy';
            $check['issues'][] = 'Database connection failed: ' . $e->getMessage();
        }

        return $check;
    }

    /**
     * Check cache system health
     */
    private function checkCacheHealth(): array
    {
        $check = [
            'status' => 'healthy',
            'response_time' => 0,
            'hit_rate' => 0,
            'memory_usage' => 0,
            'issues' => []
        ];

        try {
            $startTime = microtime(true);
            
            // Test cache write/read
            $testKey = 'health_check_' . time();
            $testValue = 'test_data';
            
            if (!$this->cache->save($testKey, $testValue, 60)) {
                throw new \Exception('Cache write failed');
            }
            
            $retrieved = $this->cache->get($testKey);
            if ($retrieved !== $testValue) {
                throw new \Exception('Cache read failed');
            }
            
            $this->cache->delete($testKey);
            
            $check['response_time'] = round((microtime(true) - $startTime) * 1000, 2);
            
            // Get cache statistics if available
            $stats = $this->cache->get('cache_hit_stats') ?? ['hits' => 0, 'total' => 1];
            $check['hit_rate'] = round(($stats['hits'] / $stats['total']) * 100, 2);

        } catch (\Exception $e) {
            $check['status'] = 'unhealthy';
            $check['issues'][] = 'Cache system failed: ' . $e->getMessage();
        }

        return $check;
    }

    /**
     * Check file system health
     */
    private function checkFileSystemHealth(): array
    {
        $check = [
            'status' => 'healthy',
            'disk_usage' => 0,
            'writable_dirs' => [],
            'issues' => []
        ];

        try {
            // Check disk usage
            $diskTotal = disk_total_space('.');
            $diskFree = disk_free_space('.');
            
            if ($diskTotal && $diskFree) {
                $check['disk_usage'] = round((($diskTotal - $diskFree) / $diskTotal) * 100, 2);
                
                if ($check['disk_usage'] > $this->config['alert_thresholds']['disk_usage']) {
                    $check['status'] = 'warning';
                    $check['issues'][] = 'High disk usage: ' . $check['disk_usage'] . '%';
                }
            }
            
            // Check critical directories are writable
            $criticalDirs = [
                WRITEPATH,
                WRITEPATH . 'logs',
                WRITEPATH . 'cache',
                FCPATH . 'uploads'
            ];
            
            foreach ($criticalDirs as $dir) {
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                
                $writable = is_writable($dir);
                $check['writable_dirs'][$dir] = $writable;
                
                if (!$writable) {
                    $check['status'] = 'unhealthy';
                    $check['issues'][] = "Directory not writable: {$dir}";
                }
            }

        } catch (\Exception $e) {
            $check['status'] = 'unhealthy';
            $check['issues'][] = 'File system check failed: ' . $e->getMessage();
        }

        return $check;
    }

    /**
     * Check system resources
     */
    private function checkSystemResources(): array
    {
        $check = [
            'status' => 'healthy',
            'memory_usage' => 0,
            'cpu_load' => 0,
            'issues' => []
        ];

        try {
            // Memory usage
            $memoryUsage = memory_get_usage(true);
            $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
            
            if ($memoryLimit > 0) {
                $check['memory_usage'] = round(($memoryUsage / $memoryLimit) * 100, 2);
                
                if ($check['memory_usage'] > $this->config['alert_thresholds']['memory_usage']) {
                    $check['status'] = 'warning';
                    $check['issues'][] = 'High memory usage: ' . $check['memory_usage'] . '%';
                }
            }
            
            // CPU load (if available)
            if (function_exists('sys_getloadavg')) {
                $load = sys_getloadavg();
                $check['cpu_load'] = round($load[0] ?? 0, 2);
                
                if ($check['cpu_load'] > $this->config['alert_thresholds']['cpu_usage']) {
                    $check['status'] = 'warning';
                    $check['issues'][] = 'High CPU load: ' . $check['cpu_load'];
                }
            }

        } catch (\Exception $e) {
            $check['status'] = 'warning';
            $check['issues'][] = 'Resource check failed: ' . $e->getMessage();
        }

        return $check;
    }

    /**
     * Check application-specific health
     */
    private function checkApplicationHealth(): array
    {
        $check = [
            'status' => 'healthy',
            'error_rate' => 0,
            'active_sessions' => 0,
            'recent_errors' => 0,
            'issues' => []
        ];

        try {
            // Check recent error rate
            $errorHandler = new ErrorHandler();
            $recentErrors = $errorHandler->getRecentErrors(100);
            $check['recent_errors'] = count($recentErrors);
            
            // Calculate error rate for last hour
            $hourAgo = strtotime('-1 hour');
            $recentErrorsInHour = array_filter($recentErrors, function($error) use ($hourAgo) {
                return strtotime($error['timestamp']) > $hourAgo;
            });
            
            $check['error_rate'] = count($recentErrorsInHour);
            
            if ($check['error_rate'] > $this->config['alert_thresholds']['error_rate']) {
                $check['status'] = 'warning';
                $check['issues'][] = 'High error rate: ' . $check['error_rate'] . ' errors in last hour';
            }
            
            // Check active sessions (if session-based)
            if (session_status() === PHP_SESSION_ACTIVE) {
                // This would require custom session tracking
                $check['active_sessions'] = 0;
            }

        } catch (\Exception $e) {
            $check['status'] = 'warning';
            $check['issues'][] = 'Application health check failed: ' . $e->getMessage();
        }

        return $check;
    }

    /**
     * Check external dependencies
     */
    private function checkDependencies(): array
    {
        $check = [
            'status' => 'healthy',
            'services' => [],
            'issues' => []
        ];

        // Define external services to check
        $services = [
            'redis' => ['host' => 'localhost', 'port' => 6379],
            // Add other external services as needed
        ];

        foreach ($services as $serviceName => $config) {
            $serviceCheck = $this->checkExternalService($serviceName, $config);
            $check['services'][$serviceName] = $serviceCheck;
            
            if ($serviceCheck['status'] !== 'healthy') {
                $check['status'] = 'warning';
                $check['issues'][] = "Service {$serviceName} is {$serviceCheck['status']}";
            }
        }

        return $check;
    }

    /**
     * Check external service connectivity
     */
    private function checkExternalService(string $serviceName, array $config): array
    {
        $check = [
            'status' => 'healthy',
            'response_time' => 0,
            'last_check' => date('Y-m-d H:i:s')
        ];

        try {
            $startTime = microtime(true);
            
            switch ($serviceName) {
                case 'redis':
                    // Simple socket connection test
                    $socket = @fsockopen($config['host'], $config['port'], $errno, $errstr, 5);
                    if (!$socket) {
                        throw new \Exception("Cannot connect to Redis: {$errstr}");
                    }
                    fclose($socket);
                    break;
                    
                default:
                    // Generic HTTP check if URL provided
                    if (isset($config['url'])) {
                        $headers = @get_headers($config['url'], 1);
                        if (!$headers || strpos($headers[0], '200') === false) {
                            throw new \Exception("Service unavailable");
                        }
                    }
                    break;
            }
            
            $check['response_time'] = round((microtime(true) - $startTime) * 1000, 2);
            
        } catch (\Exception $e) {
            $check['status'] = 'unhealthy';
            $check['error'] = $e->getMessage();
        }

        return $check;
    }

    /**
     * Calculate overall system status
     */
    private function calculateOverallStatus(array $checks): string
    {
        $unhealthyCount = 0;
        $warningCount = 0;
        $totalChecks = 0;

        foreach ($checks as $check) {
            $totalChecks++;
            
            if ($check['status'] === 'unhealthy') {
                $unhealthyCount++;
            } elseif ($check['status'] === 'warning') {
                $warningCount++;
            }
        }

        if ($unhealthyCount > 0) {
            return 'unhealthy';
        } elseif ($warningCount > 0) {
            return 'warning';
        }

        return 'healthy';
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        $cached = $this->cache->get('performance_metrics');
        if ($cached) {
            return $cached;
        }

        $metrics = [
            'timestamp' => date('Y-m-d H:i:s'),
            'system' => [
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'memory_limit' => $this->parseMemoryLimit(ini_get('memory_limit')),
                'execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
                'load_average' => function_exists('sys_getloadavg') ? sys_getloadavg() : null,
            ],
            'database' => $this->getDatabaseMetrics(),
            'cache' => $this->getCacheMetrics(),
            'application' => $this->getApplicationMetrics(),
        ];

        $this->cache->save('performance_metrics', $metrics, $this->config['performance_metrics_ttl']);
        
        return $metrics;
    }

    /**
     * Get database performance metrics
     */
    private function getDatabaseMetrics(): array
    {
        try {
            $metrics = [
                'connection_time' => 0,
                'query_count' => 0,
                'slow_queries' => 0,
            ];

            $startTime = microtime(true);
            $this->db->query("SELECT 1");
            $metrics['connection_time'] = round((microtime(true) - $startTime) * 1000, 2);

            // Get query statistics (MySQL specific)
            try {
                $stats = $this->db->query("SHOW STATUS LIKE 'Queries'")->getRow();
                $metrics['query_count'] = (int)($stats->Value ?? 0);
                
                $slowQueries = $this->db->query("SHOW STATUS LIKE 'Slow_queries'")->getRow();
                $metrics['slow_queries'] = (int)($slowQueries->Value ?? 0);
            } catch (\Exception $e) {
                // Stats not available
            }

            return $metrics;

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get cache performance metrics
     */
    private function getCacheMetrics(): array
    {
        $cacheService = new PromotionCacheService();
        return $cacheService->getCachePerformanceMetrics();
    }

    /**
     * Get application-specific metrics
     */
    private function getApplicationMetrics(): array
    {
        return [
            'active_promotions' => $this->getActivePromotionsCount(),
            'recent_registrations' => $this->getRecentRegistrationsCount(),
            'server_count' => $this->getServerCount(),
            'uptime' => $this->getUptime(),
        ];
    }

    /**
     * Parse memory limit string to bytes
     */
    private function parseMemoryLimit(string $limit): int
    {
        if ($limit === '-1') {
            return -1; // Unlimited
        }

        $limit = trim($limit);
        $unit = strtolower(substr($limit, -1));
        $value = (int)$limit;

        switch ($unit) {
            case 'g':
                $value *= 1024 * 1024 * 1024;
                break;
            case 'm':
                $value *= 1024 * 1024;
                break;
            case 'k':
                $value *= 1024;
                break;
        }

        return $value;
    }

    private function getActivePromotionsCount(): int
    {
        try {
            return (int)$this->db->table('promotions')
                              ->where('status', 'active')
                              ->where('deleted_at', null)
                              ->countAllResults();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getRecentRegistrationsCount(): int
    {
        try {
            return (int)$this->db->table('users')
                              ->where('created_at >', date('Y-m-d H:i:s', strtotime('-24 hours')))
                              ->countAllResults();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getServerCount(): int
    {
        try {
            return (int)$this->db->table('servers')
                              ->where('status', 'approved')
                              ->where('deleted_at', null)
                              ->countAllResults();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getUptime(): string
    {
        // This would typically read from a file or system metric
        // For now, return a placeholder
        return 'Unknown';
    }

    /**
     * Generate system health report
     */
    public function generateHealthReport(): array
    {
        $healthCheck = $this->performHealthCheck();
        $performanceMetrics = $this->getPerformanceMetrics();
        
        return [
            'report_generated' => date('Y-m-d H:i:s'),
            'overall_status' => $healthCheck['overall_status'],
            'health_checks' => $healthCheck['checks'],
            'performance_metrics' => $performanceMetrics,
            'recommendations' => $this->generateRecommendations($healthCheck, $performanceMetrics),
        ];
    }

    /**
     * Generate system recommendations based on health and performance data
     */
    private function generateRecommendations(array $healthCheck, array $performanceMetrics): array
    {
        $recommendations = [];

        // Database recommendations
        if (isset($healthCheck['checks']['database']['connection_count'])) {
            $connCount = $healthCheck['checks']['database']['connection_count'];
            if ($connCount > 50) {
                $recommendations[] = [
                    'type' => 'database',
                    'priority' => 'medium',
                    'message' => 'Consider implementing connection pooling to optimize database connections',
                ];
            }
        }

        // Memory recommendations
        if (isset($performanceMetrics['system']['memory_usage'])) {
            $memoryUsage = $performanceMetrics['system']['memory_usage'];
            $memoryLimit = $performanceMetrics['system']['memory_limit'];
            
            if ($memoryLimit > 0 && ($memoryUsage / $memoryLimit) > 0.8) {
                $recommendations[] = [
                    'type' => 'memory',
                    'priority' => 'high',
                    'message' => 'Memory usage is high. Consider increasing memory limit or optimizing code',
                ];
            }
        }

        // Error rate recommendations
        if (isset($healthCheck['checks']['application']['error_rate'])) {
            $errorRate = $healthCheck['checks']['application']['error_rate'];
            if ($errorRate > 10) {
                $recommendations[] = [
                    'type' => 'errors',
                    'priority' => 'high',
                    'message' => 'High error rate detected. Review error logs and fix critical issues',
                ];
            }
        }

        return $recommendations;
    }
}