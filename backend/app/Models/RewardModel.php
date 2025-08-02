<?php

namespace App\Models;

use CodeIgniter\Model;

class RewardModel extends Model
{
    protected $table = 'rewards';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;

    protected $allowedFields = [
        'server_id', 'user_id', 'promotion_id', 'reward_type', 'reward_category', 
        'reward_name', 'reward_description', 'reward_amount', 'reward_value',
        'status', 'priority', 'game_character', 'game_account', 'distribution_method',
        'distribution_config', 'approved_by', 'approved_at', 'distributed_at',
        'error_message', 'retry_count', 'metadata'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [
        'server_id' => 'required|integer|is_not_unique[servers.id]',
        'user_id' => 'required|integer|is_not_unique[users.id]',
        'reward_type' => 'required|in_list[promotion,activity,checkin,referral,bonus]',
        'reward_category' => 'required|max_length[50]',
        'reward_name' => 'required|max_length[100]',
        'reward_amount' => 'required|integer|greater_than[0]',
        'status' => 'permit_empty|in_list[pending,approved,distributed,cancelled,failed]',
        'priority' => 'permit_empty|integer|greater_than[0]|less_than[11]',
        'distribution_method' => 'permit_empty|in_list[auto,manual,api,database]',
    ];

    protected $validationMessages = [
        'server_id' => [
            'required' => 'Server ID is required',
            'is_not_unique' => 'Server does not exist',
        ],
        'user_id' => [
            'required' => 'User ID is required',
            'is_not_unique' => 'User does not exist',
        ],
        'reward_type' => [
            'required' => 'Reward type is required',
            'in_list' => 'Invalid reward type',
        ],
        'reward_amount' => [
            'required' => 'Reward amount is required',
            'greater_than' => 'Reward amount must be greater than 0',
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = ['setDefaultPriority'];
    protected $beforeUpdate = ['updateRetryCount'];

    /**
     * Set default priority before insert
     */
    protected function setDefaultPriority(array $data)
    {
        if (!isset($data['data']['priority']) || empty($data['data']['priority'])) {
            // Set priority based on reward type
            $priorities = [
                'promotion' => 3,
                'referral' => 3,
                'activity' => 5,
                'checkin' => 7,
                'bonus' => 5,
            ];

            $rewardType = $data['data']['reward_type'] ?? 'bonus';
            $data['data']['priority'] = $priorities[$rewardType] ?? 5;
        }

        return $data;
    }

    /**
     * Update retry count when status changes to failed
     */
    protected function updateRetryCount(array $data)
    {
        if (isset($data['data']['status']) && $data['data']['status'] === 'failed') {
            if (!isset($data['data']['retry_count'])) {
                $current = $this->find($data['id']);
                $data['data']['retry_count'] = ($current['retry_count'] ?? 0) + 1;
            }
        }

        return $data;
    }

    /**
     * Get rewards with pagination and filters
     */
    public function getRewards(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $builder = $this->select('rewards.*, 
                                 servers.name as server_name, servers.code as server_code,
                                 users.username as user_username, users.email as user_email,
                                 approver.username as approver_username')
                        ->join('servers', 'servers.id = rewards.server_id')
                        ->join('users', 'users.id = rewards.user_id')
                        ->join('users as approver', 'approver.id = rewards.approved_by', 'left');

        // Apply filters
        if (!empty($filters['server_id'])) {
            $builder->where('rewards.server_id', $filters['server_id']);
        }

        if (!empty($filters['user_id'])) {
            $builder->where('rewards.user_id', $filters['user_id']);
        }

        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $builder->whereIn('rewards.status', $filters['status']);
            } else {
                $builder->where('rewards.status', $filters['status']);
            }
        }

        if (!empty($filters['reward_type'])) {
            $builder->where('rewards.reward_type', $filters['reward_type']);
        }

        if (!empty($filters['priority'])) {
            $builder->where('rewards.priority', $filters['priority']);
        }

        if (!empty($filters['date_from'])) {
            $builder->where('rewards.created_at >=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $builder->where('rewards.created_at <=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $builder->groupStart()
                    ->like('rewards.reward_name', $filters['search'])
                    ->orLike('users.username', $filters['search'])
                    ->orLike('users.email', $filters['search'])
                    ->groupEnd();
        }

        $total = $builder->countAllResults(false);
        $rewards = $builder->orderBy('rewards.priority', 'ASC')
                          ->orderBy('rewards.created_at', 'DESC')
                          ->limit($perPage, ($page - 1) * $perPage)
                          ->get()
                          ->getResultArray();

        return [
            'rewards' => $rewards,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage),
        ];
    }

    /**
     * Get pending rewards for processing
     */
    public function getPendingRewards(int $limit = 100): array
    {
        return $this->select('rewards.*, 
                             servers.name as server_name, servers.code as server_code,
                             users.username as user_username, users.email as user_email')
                    ->join('servers', 'servers.id = rewards.server_id')
                    ->join('users', 'users.id = rewards.user_id')
                    ->where('rewards.status', 'pending')
                    ->orderBy('rewards.priority', 'ASC')
                    ->orderBy('rewards.created_at', 'ASC')
                    ->limit($limit)
                    ->findAll();
    }

    /**
     * Get approved rewards for distribution
     */
    public function getApprovedRewards(int $limit = 100): array
    {
        return $this->select('rewards.*, 
                             servers.name as server_name, servers.code as server_code,
                             users.username as user_username, users.email as user_email')
                    ->join('servers', 'servers.id = rewards.server_id')
                    ->join('users', 'users.id = rewards.user_id')
                    ->where('rewards.status', 'approved')
                    ->where('rewards.distribution_method', 'auto')
                    ->orderBy('rewards.priority', 'ASC')
                    ->orderBy('rewards.approved_at', 'ASC')
                    ->limit($limit)
                    ->findAll();
    }

    /**
     * Get failed rewards for retry
     */
    public function getFailedRewards(int $maxRetries = 3, int $limit = 50): array
    {
        return $this->select('rewards.*, 
                             servers.name as server_name, servers.code as server_code,
                             users.username as user_username, users.email as user_email')
                    ->join('servers', 'servers.id = rewards.server_id')
                    ->join('users', 'users.id = rewards.user_id')
                    ->where('rewards.status', 'failed')
                    ->where('rewards.retry_count <', $maxRetries)
                    ->where('rewards.updated_at <=', date('Y-m-d H:i:s', strtotime('-30 minutes')))
                    ->orderBy('rewards.priority', 'ASC')
                    ->orderBy('rewards.updated_at', 'ASC')
                    ->limit($limit)
                    ->findAll();
    }

    /**
     * Approve reward
     */
    public function approveReward(int $rewardId, int $approvedBy): bool
    {
        return $this->update($rewardId, [
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Mark reward as distributed
     */
    public function markAsDistributed(int $rewardId, array $metadata = null): bool
    {
        $data = [
            'status' => 'distributed',
            'distributed_at' => date('Y-m-d H:i:s'),
            'error_message' => null,
        ];

        if ($metadata) {
            $data['metadata'] = json_encode($metadata);
        }

        return $this->update($rewardId, $data);
    }

    /**
     * Mark reward as failed
     */
    public function markAsFailed(int $rewardId, string $errorMessage): bool
    {
        $reward = $this->find($rewardId);
        if (!$reward) {
            return false;
        }

        return $this->update($rewardId, [
            'status' => 'failed',
            'error_message' => $errorMessage,
            'retry_count' => ($reward['retry_count'] ?? 0) + 1,
        ]);
    }

    /**
     * Cancel reward
     */
    public function cancelReward(int $rewardId, string $reason = null): bool
    {
        $data = [
            'status' => 'cancelled',
        ];

        if ($reason) {
            $data['error_message'] = $reason;
        }

        return $this->update($rewardId, $data);
    }

    /**
     * Get user rewards
     */
    public function getUserRewards(int $userId, array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $builder = $this->select('rewards.*, servers.name as server_name, servers.code as server_code')
                        ->join('servers', 'servers.id = rewards.server_id')
                        ->where('rewards.user_id', $userId);

        // Apply filters
        if (!empty($filters['server_id'])) {
            $builder->where('rewards.server_id', $filters['server_id']);
        }

        if (!empty($filters['status'])) {
            $builder->where('rewards.status', $filters['status']);
        }

        if (!empty($filters['reward_type'])) {
            $builder->where('rewards.reward_type', $filters['reward_type']);
        }

        if (!empty($filters['date_from'])) {
            $builder->where('rewards.created_at >=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $builder->where('rewards.created_at <=', $filters['date_to']);
        }

        $total = $builder->countAllResults(false);
        $rewards = $builder->orderBy('rewards.created_at', 'DESC')
                          ->limit($perPage, ($page - 1) * $perPage)
                          ->get()
                          ->getResultArray();

        return [
            'rewards' => $rewards,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage),
        ];
    }

    /**
     * Get reward statistics
     */
    public function getRewardStats(int $serverId = null, string $period = '30 days'): array
    {
        $builder = $this->select('status,
                                 reward_type,
                                 COUNT(*) as count,
                                 SUM(reward_amount) as total_amount,
                                 SUM(reward_value) as total_value')
                        ->where('created_at >=', date('Y-m-d H:i:s', strtotime("-{$period}")));

        if ($serverId) {
            $builder->where('server_id', $serverId);
        }

        $results = $builder->groupBy(['status', 'reward_type'])
                          ->findAll();

        // Organize results
        $stats = [
            'by_status' => [],
            'by_type' => [],
            'totals' => [
                'count' => 0,
                'amount' => 0,
                'value' => 0,
            ],
        ];

        foreach ($results as $result) {
            $stats['by_status'][$result['status']] = ($stats['by_status'][$result['status']] ?? 0) + $result['count'];
            $stats['by_type'][$result['reward_type']] = ($stats['by_type'][$result['reward_type']] ?? 0) + $result['count'];
            
            $stats['totals']['count'] += $result['count'];
            $stats['totals']['amount'] += $result['total_amount'];
            $stats['totals']['value'] += $result['total_value'];
        }

        return $stats;
    }

    /**
     * Get top reward earners
     */
    public function getTopEarners(int $serverId = null, int $limit = 10, string $period = '30 days'): array
    {
        $builder = $this->select('user_id,
                                 users.username,
                                 users.email,
                                 COUNT(*) as reward_count,
                                 SUM(reward_amount) as total_amount,
                                 SUM(reward_value) as total_value')
                        ->join('users', 'users.id = rewards.user_id')
                        ->where('rewards.status', 'distributed')
                        ->where('rewards.created_at >=', date('Y-m-d H:i:s', strtotime("-{$period}")));

        if ($serverId) {
            $builder->where('rewards.server_id', $serverId);
        }

        return $builder->groupBy('user_id')
                       ->orderBy('total_value', 'DESC')
                       ->limit($limit)
                       ->findAll();
    }

    /**
     * Check user reward limits
     */
    public function checkUserLimits(int $userId, int $serverId, string $rewardType, string $period = 'daily'): array
    {
        $dateFrom = match($period) {
            'daily' => date('Y-m-d 00:00:00'),
            'weekly' => date('Y-m-d 00:00:00', strtotime('monday this week')),
            'monthly' => date('Y-m-01 00:00:00'),
            default => date('Y-m-d 00:00:00'),
        };

        $count = $this->where('user_id', $userId)
                     ->where('server_id', $serverId)
                     ->where('reward_type', $rewardType)
                     ->where('status !=', 'cancelled')
                     ->where('created_at >=', $dateFrom)
                     ->countAllResults();

        $totalValue = $this->selectSum('reward_value')
                          ->where('user_id', $userId)
                          ->where('server_id', $serverId)
                          ->where('reward_type', $rewardType)
                          ->where('status', 'distributed')
                          ->where('created_at >=', $dateFrom)
                          ->first()['reward_value'] ?? 0;

        return [
            'count' => $count,
            'total_value' => $totalValue,
            'period' => $period,
            'date_from' => $dateFrom,
        ];
    }

    /**
     * Clean old rewards
     */
    public function cleanOldRewards(int $daysToKeep = 365): int
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));
        
        return $this->where('created_at <', $cutoffDate)
                    ->where('status', 'distributed')
                    ->delete();
    }
}