<?php

namespace App\Models;

use CodeIgniter\Model;

class PromotionStatsModel extends Model
{
    protected $table = 'promotion_stats';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;

    protected $allowedFields = [
        'promotion_id', 'server_id', 'promoter_id', 'stat_date', 'stat_type',
        'clicks', 'unique_clicks', 'conversions', 'rewards_earned',
        'revenue_generated', 'conversion_rate', 'metadata'
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
        'promoter_id' => 'required|integer|is_not_unique[users.id]',
        'stat_date' => 'required|valid_date',
        'stat_type' => 'required|in_list[daily,weekly,monthly,yearly]',
        'clicks' => 'permit_empty|integer',
        'unique_clicks' => 'permit_empty|integer',
        'conversions' => 'permit_empty|integer',
        'rewards_earned' => 'permit_empty|integer',
        'revenue_generated' => 'permit_empty|decimal',
    ];

    protected $validationMessages = [
        'promotion_id' => [
            'required' => 'Promotion ID is required',
            'is_not_unique' => 'Promotion does not exist',
        ],
        'stat_date' => [
            'required' => 'Stat date is required',
            'valid_date' => 'Invalid date format',
        ],
        'stat_type' => [
            'required' => 'Stat type is required',
            'in_list' => 'Invalid stat type',
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = ['calculateConversionRate'];
    protected $beforeUpdate = ['calculateConversionRate'];

    /**
     * Calculate conversion rate before save
     */
    protected function calculateConversionRate(array $data)
    {
        if (isset($data['data']['unique_clicks']) && isset($data['data']['conversions'])) {
            $uniqueClicks = (int) $data['data']['unique_clicks'];
            $conversions = (int) $data['data']['conversions'];
            
            $data['data']['conversion_rate'] = $uniqueClicks > 0 ? 
                round(($conversions / $uniqueClicks) * 100, 4) : 0;
        }

        return $data;
    }

    /**
     * Create or update daily stats
     */
    public function updateDailyStats(int $promotionId, int $serverId, int $promoterId, array $stats): bool
    {
        $today = date('Y-m-d');
        
        $existing = $this->where('promotion_id', $promotionId)
                         ->where('stat_date', $today)
                         ->where('stat_type', 'daily')
                         ->first();

        $data = [
            'promotion_id' => $promotionId,
            'server_id' => $serverId,
            'promoter_id' => $promoterId,
            'stat_date' => $today,
            'stat_type' => 'daily',
            'clicks' => $stats['clicks'] ?? 0,
            'unique_clicks' => $stats['unique_clicks'] ?? 0,
            'conversions' => $stats['conversions'] ?? 0,
            'rewards_earned' => $stats['rewards_earned'] ?? 0,
            'revenue_generated' => $stats['revenue_generated'] ?? 0,
            'metadata' => $stats['metadata'] ?? null,
        ];

        if ($existing) {
            return $this->update($existing['id'], $data);
        } else {
            return $this->insert($data) !== false;
        }
    }

    /**
     * Aggregate stats for weekly/monthly/yearly
     */
    public function aggregateStats(string $period = 'weekly'): bool
    {
        $db = \Config\Database::connect();
        
        switch ($period) {
            case 'weekly':
                $dateFormat = '%Y-%u'; // Year-Week
                $dateField = 'YEARWEEK(stat_date)';
                break;
            case 'monthly':
                $dateFormat = '%Y-%m'; // Year-Month
                $dateField = 'DATE_FORMAT(stat_date, "%Y-%m-01")';
                break;
            case 'yearly':
                $dateFormat = '%Y'; // Year
                $dateField = 'DATE_FORMAT(stat_date, "%Y-01-01")';
                break;
            default:
                return false;
        }

        $sql = "
            INSERT INTO {$this->table} 
            (promotion_id, server_id, promoter_id, stat_date, stat_type, 
             clicks, unique_clicks, conversions, rewards_earned, revenue_generated, 
             conversion_rate, created_at, updated_at)
            SELECT 
                promotion_id,
                server_id,
                promoter_id,
                {$dateField} as stat_date,
                '{$period}' as stat_type,
                SUM(clicks) as clicks,
                SUM(unique_clicks) as unique_clicks,
                SUM(conversions) as conversions,
                SUM(rewards_earned) as rewards_earned,
                SUM(revenue_generated) as revenue_generated,
                CASE 
                    WHEN SUM(unique_clicks) > 0 
                    THEN ROUND((SUM(conversions) / SUM(unique_clicks)) * 100, 4)
                    ELSE 0 
                END as conversion_rate,
                NOW() as created_at,
                NOW() as updated_at
            FROM {$this->table}
            WHERE stat_type = 'daily'
            AND stat_date >= DATE_SUB(CURDATE(), INTERVAL 1 " . strtoupper($period) . ")
            GROUP BY promotion_id, server_id, promoter_id, {$dateFormat}
            ON DUPLICATE KEY UPDATE
                clicks = VALUES(clicks),
                unique_clicks = VALUES(unique_clicks),
                conversions = VALUES(conversions),
                rewards_earned = VALUES(rewards_earned),
                revenue_generated = VALUES(revenue_generated),
                conversion_rate = VALUES(conversion_rate),
                updated_at = NOW()
        ";

        return $db->query($sql);
    }

    /**
     * Get stats for promotion
     */
    public function getPromotionStats(int $promotionId, string $period = 'daily', int $limit = 30): array
    {
        return $this->where('promotion_id', $promotionId)
                    ->where('stat_type', $period)
                    ->orderBy('stat_date', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }

    /**
     * Get stats for server
     */
    public function getServerStats(int $serverId, string $period = 'daily', int $limit = 30): array
    {
        return $this->select('stat_date, stat_type, 
                             SUM(clicks) as total_clicks,
                             SUM(unique_clicks) as total_unique_clicks,
                             SUM(conversions) as total_conversions,
                             SUM(rewards_earned) as total_rewards_earned,
                             SUM(revenue_generated) as total_revenue_generated,
                             AVG(conversion_rate) as avg_conversion_rate')
                    ->where('server_id', $serverId)
                    ->where('stat_type', $period)
                    ->groupBy('stat_date')
                    ->orderBy('stat_date', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }

    /**
     * Get stats for user
     */
    public function getUserStats(int $userId, string $period = 'daily', int $limit = 30): array
    {
        return $this->select('stat_date, stat_type,
                             SUM(clicks) as total_clicks,
                             SUM(unique_clicks) as total_unique_clicks,
                             SUM(conversions) as total_conversions,
                             SUM(rewards_earned) as total_rewards_earned,
                             SUM(revenue_generated) as total_revenue_generated,
                             AVG(conversion_rate) as avg_conversion_rate')
                    ->where('promoter_id', $userId)
                    ->where('stat_type', $period)
                    ->groupBy('stat_date')
                    ->orderBy('stat_date', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }

    /**
     * Get top performers
     */
    public function getTopPerformers(string $metric = 'conversions', string $period = 'monthly', int $limit = 10): array
    {
        $validMetrics = ['clicks', 'unique_clicks', 'conversions', 'rewards_earned', 'revenue_generated'];
        
        if (!in_array($metric, $validMetrics)) {
            $metric = 'conversions';
        }

        return $this->select("promoter_id, 
                             users.username, 
                             users.email,
                             SUM({$metric}) as total_{$metric},
                             AVG(conversion_rate) as avg_conversion_rate")
                    ->join('users', 'users.id = promotion_stats.promoter_id')
                    ->where('stat_type', $period)
                    ->where('stat_date >=', date('Y-m-d', strtotime('-3 months')))
                    ->groupBy('promoter_id')
                    ->orderBy("total_{$metric}", 'DESC')
                    ->limit($limit)
                    ->findAll();
    }

    /**
     * Get promotion trends
     */
    public function getPromotionTrends(int $serverId = null, int $days = 30): array
    {
        $builder = $this->select('stat_date,
                                 SUM(clicks) as daily_clicks,
                                 SUM(unique_clicks) as daily_unique_clicks,
                                 SUM(conversions) as daily_conversions,
                                 AVG(conversion_rate) as daily_conversion_rate')
                        ->where('stat_type', 'daily')
                        ->where('stat_date >=', date('Y-m-d', strtotime("-{$days} days")))
                        ->groupBy('stat_date')
                        ->orderBy('stat_date', 'ASC');

        if ($serverId) {
            $builder->where('server_id', $serverId);
        }

        return $builder->findAll();
    }

    /**
     * Get performance summary
     */
    public function getPerformanceSummary(int $serverId = null, string $period = '30 days'): array
    {
        $builder = $this->select('SUM(clicks) as total_clicks,
                                 SUM(unique_clicks) as total_unique_clicks,
                                 SUM(conversions) as total_conversions,
                                 SUM(rewards_earned) as total_rewards_earned,
                                 SUM(revenue_generated) as total_revenue_generated,
                                 AVG(conversion_rate) as avg_conversion_rate,
                                 COUNT(DISTINCT promoter_id) as active_promoters')
                        ->where('stat_type', 'daily')
                        ->where('stat_date >=', date('Y-m-d', strtotime("-{$period}")));

        if ($serverId) {
            $builder->where('server_id', $serverId);
        }

        $result = $builder->first();
        
        // Calculate additional metrics
        if ($result) {
            $result['click_through_rate'] = $result['total_unique_clicks'] > 0 ? 
                round(($result['total_unique_clicks'] / $result['total_clicks']) * 100, 2) : 0;
            
            $result['reward_per_conversion'] = $result['total_conversions'] > 0 ? 
                round($result['total_rewards_earned'] / $result['total_conversions'], 2) : 0;
            
            $result['revenue_per_click'] = $result['total_unique_clicks'] > 0 ? 
                round($result['total_revenue_generated'] / $result['total_unique_clicks'], 2) : 0;
        }

        return $result ?: [];
    }

    /**
     * Clean old stats
     */
    public function cleanOldStats(int $daysToKeep = 365): int
    {
        $cutoffDate = date('Y-m-d', strtotime("-{$daysToKeep} days"));
        
        return $this->where('stat_date <', $cutoffDate)
                    ->where('stat_type', 'daily')
                    ->delete();
    }
}