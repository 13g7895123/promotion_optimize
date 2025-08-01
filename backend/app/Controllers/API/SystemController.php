<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\ServerModel;
use App\Models\UserSessionModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\API\ResponseTrait;

class SystemController extends BaseController
{
    use ResponseTrait;

    private UserModel $userModel;
    private ServerModel $serverModel;
    private UserSessionModel $sessionModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->serverModel = new ServerModel();
        $this->sessionModel = new UserSessionModel();
    }

    /**
     * Get system health status
     */
    public function health(): ResponseInterface
    {
        try {
            $health = [
                'status' => 'healthy',
                'timestamp' => date('Y-m-d H:i:s'),
                'version' => '1.0.0',
                'environment' => ENVIRONMENT,
                'checks' => []
            ];

            // Database check
            try {
                $db = \Config\Database::connect();
                $db->query('SELECT 1');
                $health['checks']['database'] = [
                    'status' => 'healthy',
                    'message' => 'Database connection successful'
                ];
            } catch (\Exception $e) {
                $health['checks']['database'] = [
                    'status' => 'unhealthy',
                    'message' => 'Database connection failed: ' . $e->getMessage()
                ];
                $health['status'] = 'unhealthy';
            }

            // Cache check
            try {
                $cache = service('cache');
                $testKey = 'health_check_' . time();
                $cache->save($testKey, 'test', 60);
                $cached = $cache->get($testKey);
                
                if ($cached === 'test') {
                    $health['checks']['cache'] = [
                        'status' => 'healthy',
                        'message' => 'Cache system operational'
                    ];
                } else {
                    throw new \Exception('Cache test failed');
                }
                
                $cache->delete($testKey);
            } catch (\Exception $e) {
                $health['checks']['cache'] = [
                    'status' => 'unhealthy',
                    'message' => 'Cache system failed: ' . $e->getMessage()
                ];
                $health['status'] = 'degraded';
            }

            // File system check
            try {
                $testFile = WRITEPATH . 'health_check_' . time() . '.tmp';
                file_put_contents($testFile, 'test');
                
                if (file_get_contents($testFile) === 'test') {
                    $health['checks']['filesystem'] = [
                        'status' => 'healthy',
                        'message' => 'File system writable'
                    ];
                } else {
                    throw new \Exception('File system test failed');
                }
                
                unlink($testFile);
            } catch (\Exception $e) {
                $health['checks']['filesystem'] = [
                    'status' => 'unhealthy',
                    'message' => 'File system error: ' . $e->getMessage()
                ];
                $health['status'] = 'degraded';
            }

            // Memory usage check
            $memoryUsage = memory_get_usage(true);
            $memoryLimit = ini_get('memory_limit');
            $memoryLimitBytes = $this->convertToBytes($memoryLimit);
            $memoryPercent = ($memoryUsage / $memoryLimitBytes) * 100;

            $health['checks']['memory'] = [
                'status' => $memoryPercent > 90 ? 'unhealthy' : ($memoryPercent > 75 ? 'warning' : 'healthy'),
                'usage' => $this->formatBytes($memoryUsage),
                'limit' => $memoryLimit,
                'percentage' => round($memoryPercent, 2)
            ];

            if ($memoryPercent > 90) {
                $health['status'] = 'unhealthy';
            } elseif ($memoryPercent > 75 && $health['status'] === 'healthy') {
                $health['status'] = 'degraded';
            }

            $response = [
                'status' => 'success',
                'message' => 'System health check completed',
                'data' => $health,
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'System health check error: ' . $e->getMessage());
            return $this->fail('Health check failed', 500);
        }
    }

    /**
     * Get system statistics
     */
    public function stats(): ResponseInterface
    {
        try {
            $stats = [
                'timestamp' => date('Y-m-d H:i:s'),
                'uptime' => $this->getSystemUptime(),
                'database' => $this->getDatabaseStats(),
                'users' => $this->getUserStats(),
                'servers' => $this->getServerStats(),
                'sessions' => $this->getSessionStats(),
                'system' => $this->getSystemInfo(),
            ];

            $response = [
                'status' => 'success',
                'message' => 'System statistics retrieved successfully',
                'data' => $stats,
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'System stats error: ' . $e->getMessage());
            return $this->fail('Failed to retrieve system statistics', 500);
        }
    }

    /**
     * Get database statistics
     */
    private function getDatabaseStats(): array
    {
        try {
            $db = \Config\Database::connect();
            
            // Get table sizes
            $tables = [
                'users', 'roles', 'permissions', 'user_roles', 'role_permissions',
                'servers', 'server_settings', 'user_sessions'
            ];
            
            $tableStats = [];
            foreach ($tables as $table) {
                try {
                    $result = $db->query("SELECT COUNT(*) as count FROM {$table}")->getRowArray();
                    $tableStats[$table] = $result['count'] ?? 0;
                } catch (\Exception $e) {
                    $tableStats[$table] = 'error';
                }
            }

            // Get database size
            $dbName = $db->getDatabase();
            $sizeQuery = "SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb 
                FROM information_schema.tables 
                WHERE table_schema = '{$dbName}'";
            
            $sizeResult = $db->query($sizeQuery)->getRowArray();
            $dbSize = $sizeResult['size_mb'] ?? 0;

            return [
                'tables' => $tableStats,
                'total_size_mb' => $dbSize,
                'connection_count' => $this->getConnectionCount(),
            ];

        } catch (\Exception $e) {
            return [
                'error' => 'Failed to retrieve database stats: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get user statistics
     */
    private function getUserStats(): array
    {
        try {
            $db = \Config\Database::connect();
            
            // Total users by status
            $statusStats = $db->table('users')
                             ->select('status, COUNT(*) as count')
                             ->where('deleted_at', null)
                             ->groupBy('status')
                             ->get()
                             ->getResultArray();

            // Recent registrations
            $recentUsers = $db->table('users')
                             ->where('created_at >', date('Y-m-d H:i:s', strtotime('-7 days')))
                             ->where('deleted_at', null)
                             ->countAllResults();

            // Active sessions
            $activeSessions = $this->sessionModel->getConcurrentSessionsCount();

            return [
                'by_status' => $statusStats,
                'recent_registrations' => $recentUsers,
                'active_sessions' => $activeSessions,
            ];

        } catch (\Exception $e) {
            return [
                'error' => 'Failed to retrieve user stats: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get server statistics
     */
    private function getServerStats(): array
    {
        try {
            return $this->serverModel->getServerStats();
        } catch (\Exception $e) {
            return [
                'error' => 'Failed to retrieve server stats: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get session statistics
     */
    private function getSessionStats(): array
    {
        try {
            return $this->sessionModel->getSessionStats();
        } catch (\Exception $e) {
            return [
                'error' => 'Failed to retrieve session stats: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get system information
     */
    private function getSystemInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'codeigniter_version' => \CodeIgniter\CodeIgniter::CI_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'operating_system' => PHP_OS,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'timezone' => date_default_timezone_get(),
        ];
    }

    /**
     * Get system uptime (simplified)
     */
    private function getSystemUptime(): string
    {
        // This is a simplified implementation
        // In a real application, you might track application start time
        return 'N/A';
    }

    /**
     * Get database connection count
     */
    private function getConnectionCount(): int
    {
        try {
            $db = \Config\Database::connect();
            $result = $db->query('SHOW STATUS LIKE "Threads_connected"')->getRowArray();
            return (int) ($result['Value'] ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Convert memory limit string to bytes
     */
    private function convertToBytes(string $value): int
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $value = (int) $value;

        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}