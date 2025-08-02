<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Models\PromotionModel;
use App\Models\PromotionStatsModel;
use App\Models\RewardModel;
use App\Models\ServerModel;
use App\Libraries\PromotionTracker;
use App\Libraries\RewardCalculator;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\API\ResponseTrait;

class StatisticsController extends BaseController
{
    use ResponseTrait;

    private PromotionModel $promotionModel;
    private PromotionStatsModel $statsModel;
    private RewardModel $rewardModel;
    private ServerModel $serverModel;
    private PromotionTracker $tracker;
    private RewardCalculator $calculator;

    public function __construct()
    {
        $this->promotionModel = new PromotionModel();
        $this->statsModel = new PromotionStatsModel();
        $this->rewardModel = new RewardModel();
        $this->serverModel = new ServerModel();
        $this->tracker = new PromotionTracker();
        $this->calculator = new RewardCalculator();
    }

    /**
     * Get dashboard overview statistics
     */
    public function dashboard(): ResponseInterface
    {
        try {
            $userId = $this->request->userPayload['user_id'];
            $serverId = $this->request->getGet('server_id');
            $period = $this->request->getGet('period') ?? '30 days';

            // Get user role to determine data scope
            $userRoles = $this->request->userPayload['roles'] ?? [];
            $isAdmin = in_array('admin', $userRoles) || in_array('super_admin', $userRoles);
            $isServerOwner = in_array('server_owner', $userRoles);

            $dashboard = [];

            if ($isAdmin || $isServerOwner) {
                // System-wide or server-wide statistics
                $dashboard = $this->getAdminDashboard($serverId, $period);
            } else {
                // User-specific statistics
                $dashboard = $this->getUserDashboard($userId, $serverId, $period);
            }

            $response = [
                'status' => 'success',
                'message' => 'Dashboard statistics retrieved successfully',
                'data' => [
                    'period' => $period,
                    'server_id' => $serverId,
                    'dashboard' => $dashboard,
                ],
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Dashboard statistics error: ' . $e->getMessage());
            return $this->fail('Failed to retrieve dashboard statistics', 500);
        }
    }

    /**
     * Get promotion statistics
     */
    public function promotions(): ResponseInterface
    {
        try {
            $serverId = $this->request->getGet('server_id');
            $userId = $this->request->getGet('user_id');
            $period = $this->request->getGet('period') ?? 'daily';
            $limit = min((int) ($this->request->getGet('limit') ?? 30), 365);

            $stats = [];

            if ($serverId) {
                $stats = $this->statsModel->getServerStats($serverId, $period, $limit);
            } elseif ($userId) {
                $stats = $this->statsModel->getUserStats($userId, $period, $limit);
            } else {
                // Get overall trends
                $stats = $this->statsModel->getPromotionTrends(null, $limit);
            }

            // Get performance summary
            $summary = $this->statsModel->getPerformanceSummary($serverId, "{$limit} days");

            // Get top performers
            $topPerformers = $this->statsModel->getTopPerformers('conversions', $period, 10);

            $response = [
                'status' => 'success',
                'message' => 'Promotion statistics retrieved successfully',
                'data' => [
                    'period' => $period,
                    'limit' => $limit,
                    'server_id' => $serverId,
                    'user_id' => $userId,
                    'statistics' => $stats,
                    'summary' => $summary,
                    'top_performers' => $topPerformers,
                ],
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Promotion statistics error: ' . $e->getMessage());
            return $this->fail('Failed to retrieve promotion statistics', 500);
        }
    }

    /**
     * Get reward statistics
     */
    public function rewards(): ResponseInterface
    {
        try {
            $serverId = $this->request->getGet('server_id');
            $period = $this->request->getGet('period') ?? '30 days';

            $stats = $this->calculator->calculateRewardStats($serverId, $period);
            $leaderboard = $this->calculator->getRewardLeaderboard($serverId, 10, $period);

            // Get additional metrics
            $additionalMetrics = $this->getRewardMetrics($serverId, $period);

            $response = [
                'status' => 'success',
                'message' => 'Reward statistics retrieved successfully',
                'data' => [
                    'period' => $period,
                    'server_id' => $serverId,
                    'statistics' => $stats,
                    'leaderboard' => $leaderboard,
                    'metrics' => $additionalMetrics,
                ],
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Reward statistics error: ' . $e->getMessage());
            return $this->fail('Failed to retrieve reward statistics', 500);
        }
    }

    /**
     * Get server performance comparison
     */
    public function serverComparison(): ResponseInterface
    {
        try {
            if (!$this->hasPermission('statistics.view_all')) {
                return $this->failForbidden('Insufficient permissions');
            }

            $period = $this->request->getGet('period') ?? '30 days';
            $metric = $this->request->getGet('metric') ?? 'conversions';
            $limit = min((int) ($this->request->getGet('limit') ?? 10), 50);

            // Get active servers
            $servers = $this->serverModel->where('status', 'active')
                                        ->select('id, name, code')
                                        ->findAll();

            $comparison = [];

            foreach ($servers as $server) {
                $serverStats = $this->statsModel->getPerformanceSummary($server['id'], $period);
                $rewardStats = $this->calculator->calculateRewardStats($server['id'], $period);

                $comparison[] = [
                    'server' => $server,
                    'promotion_stats' => $serverStats,
                    'reward_stats' => $rewardStats,
                    'score' => $this->calculateServerScore($serverStats, $rewardStats),
                ];
            }

            // Sort by the requested metric
            usort($comparison, function($a, $b) use ($metric) {
                $aValue = $a['promotion_stats'][$metric] ?? 0;
                $bValue = $b['promotion_stats'][$metric] ?? 0;
                return $bValue <=> $aValue;
            });

            // Limit results
            $comparison = array_slice($comparison, 0, $limit);

            $response = [
                'status' => 'success',
                'message' => 'Server comparison retrieved successfully',
                'data' => [
                    'period' => $period,
                    'metric' => $metric,
                    'comparison' => $comparison,
                ],
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Server comparison error: ' . $e->getMessage());
            return $this->fail('Failed to retrieve server comparison', 500);
        }
    }

    /**
     * Get analytics export
     */
    public function export(): ResponseInterface
    {
        try {
            $format = $this->request->getGet('format') ?? 'csv';
            $type = $this->request->getGet('type') ?? 'promotions';
            $serverId = $this->request->getGet('server_id');
            $dateFrom = $this->request->getGet('date_from') ?? date('Y-m-d', strtotime('-30 days'));
            $dateTo = $this->request->getGet('date_to') ?? date('Y-m-d');

            $data = [];

            switch ($type) {
                case 'promotions':
                    $data = $this->exportPromotionData($serverId, $dateFrom, $dateTo);
                    break;
                    
                case 'rewards':
                    $data = $this->exportRewardData($serverId, $dateFrom, $dateTo);
                    break;
                    
                case 'clicks':
                    $data = $this->exportClickData($serverId, $dateFrom, $dateTo);
                    break;
                    
                default:
                    return $this->fail('Invalid export type', 400);
            }

            $exportContent = $this->formatExportData($data, $format);
            $filename = "{$type}_export_{$dateFrom}_to_{$dateTo}.{$format}";

            $mimeTypes = [
                'csv' => 'text/csv',
                'json' => 'application/json',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ];

            return $this->response
                        ->setHeader('Content-Type', $mimeTypes[$format] ?? 'text/plain')
                        ->setHeader('Content-Disposition', "attachment; filename=\"{$filename}\"")
                        ->setBody($exportContent);

        } catch (\Exception $e) {
            log_message('error', 'Export error: ' . $e->getMessage());
            return $this->fail('Failed to export data', 500);
        }
    }

    /**
     * Get real-time statistics
     */
    public function realtime(): ResponseInterface
    {
        try {
            $serverId = $this->request->getGet('server_id');
            $userId = $this->request->userPayload['user_id'];

            $realtime = [
                'timestamp' => date('Y-m-d H:i:s'),
                'active_promotions' => $this->getActivePromotionsCount($serverId, $userId),
                'pending_rewards' => $this->getPendingRewardsCount($serverId, $userId),
                'recent_activity' => $this->getRecentActivity($serverId, $userId, 10),
                'hourly_stats' => $this->getHourlyStats($serverId, $userId),
            ];

            $response = [
                'status' => 'success',
                'message' => 'Real-time statistics retrieved successfully',
                'data' => $realtime,
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Real-time statistics error: ' . $e->getMessage());
            return $this->fail('Failed to retrieve real-time statistics', 500);
        }
    }

    /**
     * Get admin dashboard data
     */
    private function getAdminDashboard(?int $serverId, string $period): array
    {
        $dashboard = [
            'overview' => $this->statsModel->getPerformanceSummary($serverId, $period),
            'trends' => $this->statsModel->getPromotionTrends($serverId, 30),
            'top_promoters' => $this->statsModel->getTopPerformers('conversions', 'monthly', 5),
            'reward_stats' => $this->calculator->calculateRewardStats($serverId, $period),
            'server_activity' => $this->getServerActivity($serverId),
        ];

        // Add server-specific data if server is specified
        if ($serverId) {
            $dashboard['server_details'] = $this->serverModel->find($serverId);
            $dashboard['server_promotions'] = $this->promotionModel->getServerPromotions($serverId, [], 1, 5);
        }

        return $dashboard;
    }

    /**
     * Get user dashboard data
     */
    private function getUserDashboard(int $userId, ?int $serverId, string $period): array
    {
        return [
            'user_stats' => $this->statsModel->getUserStats($userId, 'daily', 30),
            'user_promotions' => $this->promotionModel->getUserPromotions($userId, ['server_id' => $serverId], 1, 5),
            'user_rewards' => $this->rewardModel->getUserRewards($userId, ['server_id' => $serverId], 1, 5),
            'performance_summary' => $this->getUserPerformanceSummary($userId, $serverId, $period),
        ];
    }

    /**
     * Get reward metrics
     */
    private function getRewardMetrics(?int $serverId, string $period): array
    {
        $builder = $this->rewardModel->select('
            AVG(TIMESTAMPDIFF(MINUTE, created_at, approved_at)) as avg_approval_time,
            AVG(TIMESTAMPDIFF(MINUTE, approved_at, distributed_at)) as avg_distribution_time,
            COUNT(CASE WHEN status = "pending" THEN 1 END) as pending_count,
            COUNT(CASE WHEN status = "approved" THEN 1 END) as approved_count,
            COUNT(CASE WHEN status = "distributed" THEN 1 END) as distributed_count,
            COUNT(CASE WHEN status = "failed" THEN 1 END) as failed_count,
            COUNT(CASE WHEN status = "cancelled" THEN 1 END) as cancelled_count
        ')->where('created_at >=', date('Y-m-d H:i:s', strtotime("-{$period}")));

        if ($serverId) {
            $builder->where('server_id', $serverId);
        }

        return $builder->first() ?? [];
    }

    /**
     * Calculate server score
     */
    private function calculateServerScore(array $promotionStats, array $rewardStats): float
    {
        $score = 0;
        
        // Conversion rate (40% weight)
        $conversionRate = $promotionStats['conversion_rate'] ?? 0;
        $score += $conversionRate * 0.4;
        
        // Total conversions (30% weight)
        $conversions = $promotionStats['total_conversions'] ?? 0;
        $score += min($conversions / 100, 10) * 0.3; // Cap at 100 conversions for 3 points
        
        // Reward distribution efficiency (20% weight)
        $totalRewards = $rewardStats['totals']['count'] ?? 0;
        $distributedRewards = $rewardStats['by_status']['distributed'] ?? 0;
        $efficiency = $totalRewards > 0 ? ($distributedRewards / $totalRewards) * 100 : 0;
        $score += ($efficiency / 10) * 0.2; // Max 10 points for 100% efficiency
        
        // Active promoters (10% weight)
        $activePromoters = $promotionStats['active_promoters'] ?? 0;
        $score += min($activePromoters, 10) * 0.1; // Cap at 10 promoters for 1 point
        
        return round($score, 2);
    }

    /**
     * Export promotion data
     */
    private function exportPromotionData(?int $serverId, string $dateFrom, string $dateTo): array
    {
        $builder = $this->promotionModel->select('
            promotions.*,
            servers.name as server_name,
            users.username as promoter_username,
            users.email as promoter_email
        ')
        ->join('servers', 'servers.id = promotions.server_id')
        ->join('users', 'users.id = promotions.promoter_id')
        ->where('promotions.created_at >=', $dateFrom . ' 00:00:00')
        ->where('promotions.created_at <=', $dateTo . ' 23:59:59');

        if ($serverId) {
            $builder->where('promotions.server_id', $serverId);
        }

        return $builder->orderBy('promotions.created_at', 'DESC')->findAll();
    }

    /**
     * Export reward data
     */
    private function exportRewardData(?int $serverId, string $dateFrom, string $dateTo): array
    {
        $builder = $this->rewardModel->select('
            rewards.*,
            servers.name as server_name,
            users.username as user_username,
            users.email as user_email,
            promotions.promotion_code
        ')
        ->join('servers', 'servers.id = rewards.server_id')
        ->join('users', 'users.id = rewards.user_id')
        ->join('promotions', 'promotions.id = rewards.promotion_id', 'left')
        ->where('rewards.created_at >=', $dateFrom . ' 00:00:00')
        ->where('rewards.created_at <=', $dateTo . ' 23:59:59');

        if ($serverId) {
            $builder->where('rewards.server_id', $serverId);
        }

        return $builder->orderBy('rewards.created_at', 'DESC')->findAll();
    }

    /**
     * Export click data
     */
    private function exportClickData(?int $serverId, string $dateFrom, string $dateTo): array
    {
        $clickModel = new \App\Models\PromotionClickModel();
        
        $builder = $clickModel->select('
            promotion_clicks.*,
            promotions.promotion_code,
            servers.name as server_name,
            users.username as promoter_username
        ')
        ->join('promotions', 'promotions.id = promotion_clicks.promotion_id')
        ->join('servers', 'servers.id = promotion_clicks.server_id')
        ->join('users', 'users.id = promotions.promoter_id')
        ->where('promotion_clicks.created_at >=', $dateFrom . ' 00:00:00')
        ->where('promotion_clicks.created_at <=', $dateTo . ' 23:59:59');

        if ($serverId) {
            $builder->where('promotion_clicks.server_id', $serverId);
        }

        return $builder->orderBy('promotion_clicks.created_at', 'DESC')->findAll();
    }

    /**
     * Format export data
     */
    private function formatExportData(array $data, string $format): string
    {
        switch ($format) {
            case 'json':
                return json_encode($data, JSON_PRETTY_PRINT);
                
            case 'csv':
                return $this->arrayToCSV($data);
                
            default:
                throw new \InvalidArgumentException('Unsupported export format');
        }
    }

    /**
     * Convert array to CSV
     */
    private function arrayToCSV(array $data): string
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
                $csvRow[] = $value ?? '';
            }
            $csv .= implode(',', $csvRow) . "\n";
        }
        
        return $csv;
    }

    /**
     * Get server activity
     */
    private function getServerActivity(?int $serverId): array
    {
        $builder = $this->serverModel->select('
            COUNT(DISTINCT promotions.id) as promotion_count,
            COUNT(DISTINCT users.id) as promoter_count,
            SUM(promotions.click_count) as total_clicks,
            SUM(promotions.conversion_count) as total_conversions
        ')
        ->join('promotions', 'promotions.server_id = servers.id', 'left')
        ->join('users', 'users.id = promotions.promoter_id', 'left')
        ->where('servers.status', 'active');

        if ($serverId) {
            $builder->where('servers.id', $serverId);
        }

        return $builder->first() ?? [];
    }

    /**
     * Get user performance summary
     */
    private function getUserPerformanceSummary(int $userId, ?int $serverId, string $period): array
    {
        $builder = $this->promotionModel->select('
            COUNT(*) as promotion_count,
            SUM(click_count) as total_clicks,
            SUM(unique_click_count) as total_unique_clicks,
            SUM(conversion_count) as total_conversions,
            AVG(CASE WHEN unique_click_count > 0 THEN (conversion_count / unique_click_count) * 100 ELSE 0 END) as avg_conversion_rate
        ')
        ->where('promoter_id', $userId)
        ->where('created_at >=', date('Y-m-d H:i:s', strtotime("-{$period}")));

        if ($serverId) {
            $builder->where('server_id', $serverId);
        }

        return $builder->first() ?? [];
    }

    /**
     * Get active promotions count
     */
    private function getActivePromotionsCount(?int $serverId, int $userId): int
    {
        $builder = $this->promotionModel->where('status', 'active');

        if (!$this->hasPermission('statistics.view_all')) {
            $builder->where('promoter_id', $userId);
        }

        if ($serverId) {
            $builder->where('server_id', $serverId);
        }

        return $builder->countAllResults();
    }

    /**
     * Get pending rewards count
     */
    private function getPendingRewardsCount(?int $serverId, int $userId): int
    {
        $builder = $this->rewardModel->where('status', 'pending');

        if (!$this->hasPermission('statistics.view_all')) {
            $builder->where('user_id', $userId);
        }

        if ($serverId) {
            $builder->where('server_id', $serverId);
        }

        return $builder->countAllResults();
    }

    /**
     * Get recent activity
     */
    private function getRecentActivity(?int $serverId, int $userId, int $limit): array
    {
        // This is a simplified version - you might want to create a dedicated activity log table
        $activities = [];

        // Recent promotions
        $builder = $this->promotionModel->select('
            "promotion" as type,
            promotion_code as reference,
            created_at,
            "Created new promotion" as description
        ')->orderBy('created_at', 'DESC')->limit($limit);

        if (!$this->hasPermission('statistics.view_all')) {
            $builder->where('promoter_id', $userId);
        }

        if ($serverId) {
            $builder->where('server_id', $serverId);
        }

        $activities = array_merge($activities, $builder->findAll());

        // Recent rewards
        $builder = $this->rewardModel->select('
            "reward" as type,
            reward_name as reference,
            created_at,
            CONCAT("Reward ", status) as description
        ')->orderBy('created_at', 'DESC')->limit($limit);

        if (!$this->hasPermission('statistics.view_all')) {
            $builder->where('user_id', $userId);
        }

        if ($serverId) {
            $builder->where('server_id', $serverId);
        }

        $activities = array_merge($activities, $builder->findAll());

        // Sort by date and limit
        usort($activities, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return array_slice($activities, 0, $limit);
    }

    /**
     * Get hourly stats for today
     */
    private function getHourlyStats(?int $serverId, int $userId): array
    {
        $clickModel = new \App\Models\PromotionClickModel();
        
        $builder = $clickModel->select('
            HOUR(created_at) as hour,
            COUNT(*) as clicks,
            SUM(is_unique) as unique_clicks,
            SUM(is_converted) as conversions
        ')
        ->where('DATE(created_at)', date('Y-m-d'))
        ->groupBy('HOUR(created_at)')
        ->orderBy('hour', 'ASC');

        if ($serverId) {
            $builder->where('server_id', $serverId);
        }

        if (!$this->hasPermission('statistics.view_all')) {
            $builder->join('promotions', 'promotions.id = promotion_clicks.promotion_id')
                   ->where('promotions.promoter_id', $userId);
        }

        return $builder->findAll();
    }

    /**
     * Check if user has permission
     */
    private function hasPermission(string $permission): bool
    {
        $userPayload = $this->request->userPayload;
        return in_array($permission, $userPayload['permissions'] ?? []);
    }
}