<?php

namespace App\Models;

use CodeIgniter\Model;

class PromotionModel extends Model
{
    protected $table = 'promotions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;

    protected $allowedFields = [
        'server_id', 'promoter_id', 'promoted_user_id', 'promotion_code', 
        'promotion_link', 'click_count', 'unique_click_count', 'conversion_count',
        'status', 'source_ip', 'user_agent', 'referrer_url', 'expires_at',
        'last_clicked_at', 'metadata'
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
        'promoter_id' => 'required|integer|is_not_unique[users.id]',
        'promotion_code' => 'required|alpha_numeric|min_length[8]|max_length[32]|is_unique[promotions.promotion_code,id,{id}]',
        'promotion_link' => 'required|valid_url|max_length[500]',
        'status' => 'permit_empty|in_list[active,paused,expired,banned]',
        'expires_at' => 'permit_empty|valid_date',
    ];

    protected $validationMessages = [
        'server_id' => [
            'required' => 'Server ID is required',
            'is_not_unique' => 'Server does not exist',
        ],
        'promoter_id' => [
            'required' => 'Promoter ID is required',
            'is_not_unique' => 'Promoter does not exist',
        ],
        'promotion_code' => [
            'required' => 'Promotion code is required',
            'is_unique' => 'Promotion code already exists',
            'min_length' => 'Promotion code must be at least 8 characters',
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = ['generatePromotionCode'];
    protected $beforeUpdate = ['updateStats'];

    /**
     * Generate unique promotion code before insert
     */
    protected function generatePromotionCode(array $data)
    {
        if (!isset($data['data']['promotion_code']) || empty($data['data']['promotion_code'])) {
            do {
                $code = $this->generateUniqueCode();
            } while ($this->where('promotion_code', $code)->first());
            
            $data['data']['promotion_code'] = $code;
        }

        return $data;
    }

    /**
     * Update statistics before update
     */
    protected function updateStats(array $data)
    {
        // Update last_clicked_at if click_count changed
        if (isset($data['data']['click_count'])) {
            $data['data']['last_clicked_at'] = date('Y-m-d H:i:s');
        }

        return $data;
    }

    /**
     * Generate unique promotion code
     */
    private function generateUniqueCode(): string
    {
        return strtoupper(bin2hex(random_bytes(8)));
    }

    /**
     * Get promotion with related data
     */
    public function getPromotionWithDetails(int $promotionId): ?array
    {
        $promotion = $this->select('promotions.*, servers.name as server_name, servers.code as server_code,
                                   users.username as promoter_username, users.email as promoter_email')
                          ->join('servers', 'servers.id = promotions.server_id')
                          ->join('users', 'users.id = promotions.promoter_id')
                          ->find($promotionId);

        if (!$promotion) {
            return null;
        }

        // Get promotion statistics
        $promotion['stats'] = $this->getPromotionStats($promotionId);
        
        return $promotion;
    }

    /**
     * Get promotion statistics
     */
    public function getPromotionStats(int $promotionId): array
    {
        $statsModel = new PromotionStatsModel();
        return $statsModel->where('promotion_id', $promotionId)
                          ->orderBy('stat_date', 'DESC')
                          ->findAll();
    }

    /**
     * Get user promotions with pagination
     */
    public function getUserPromotions(int $userId, array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $builder = $this->select('promotions.*, servers.name as server_name, servers.code as server_code')
                        ->join('servers', 'servers.id = promotions.server_id')
                        ->where('promotions.promoter_id', $userId);

        // Apply filters
        if (!empty($filters['server_id'])) {
            $builder->where('promotions.server_id', $filters['server_id']);
        }

        if (!empty($filters['status'])) {
            $builder->where('promotions.status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $builder->where('promotions.created_at >=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $builder->where('promotions.created_at <=', $filters['date_to']);
        }

        $total = $builder->countAllResults(false);
        $promotions = $builder->orderBy('promotions.created_at', 'DESC')
                            ->limit($perPage, ($page - 1) * $perPage)
                            ->get()
                            ->getResultArray();

        return [
            'promotions' => $promotions,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage),
        ];
    }

    /**
     * Get server promotions with pagination
     */
    public function getServerPromotions(int $serverId, array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $builder = $this->select('promotions.*, users.username as promoter_username, users.email as promoter_email')
                        ->join('users', 'users.id = promotions.promoter_id')
                        ->where('promotions.server_id', $serverId);

        // Apply filters
        if (!empty($filters['status'])) {
            $builder->where('promotions.status', $filters['status']);
        }

        if (!empty($filters['promoter_search'])) {
            $builder->groupStart()
                    ->like('users.username', $filters['promoter_search'])
                    ->orLike('users.email', $filters['promoter_search'])
                    ->groupEnd();
        }

        if (!empty($filters['date_from'])) {
            $builder->where('promotions.created_at >=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $builder->where('promotions.created_at <=', $filters['date_to']);
        }

        $total = $builder->countAllResults(false);
        $promotions = $builder->orderBy('promotions.created_at', 'DESC')
                            ->limit($perPage, ($page - 1) * $perPage)
                            ->get()
                            ->getResultArray();

        return [
            'promotions' => $promotions,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage),
        ];
    }

    /**
     * Increment click count
     */
    public function incrementClick(int $promotionId, bool $isUnique = false): bool
    {
        $builder = $this->builder();
        $builder->where('id', $promotionId);
        
        if ($isUnique) {
            $builder->set('click_count', 'click_count + 1', false)
                   ->set('unique_click_count', 'unique_click_count + 1', false);
        } else {
            $builder->set('click_count', 'click_count + 1', false);
        }
        
        $builder->set('last_clicked_at', date('Y-m-d H:i:s'));
        
        return $builder->update();
    }

    /**
     * Increment conversion count
     */
    public function incrementConversion(int $promotionId, int $promotedUserId): bool
    {
        return $this->update($promotionId, [
            'conversion_count' => 'conversion_count + 1',
            'promoted_user_id' => $promotedUserId,
        ]);
    }

    /**
     * Check if user can create promotion for server
     */
    public function canUserPromoteServer(int $userId, int $serverId): bool
    {
        // Check if user already has active promotion for this server
        $existing = $this->where('promoter_id', $userId)
                         ->where('server_id', $serverId)
                         ->where('status', 'active')
                         ->first();

        return !$existing;
    }

    /**
     * Get promotion by code
     */
    public function getByCode(string $code): ?array
    {
        return $this->where('promotion_code', $code)
                    ->where('status', 'active')
                    ->where('(expires_at IS NULL OR expires_at > NOW())')
                    ->first();
    }

    /**
     * Expire old promotions
     */
    public function expireOldPromotions(): int
    {
        return $this->where('expires_at <=', date('Y-m-d H:i:s'))
                    ->where('status', 'active')
                    ->set(['status' => 'expired'])
                    ->update();
    }

    /**
     * Get promotion performance metrics
     */
    public function getPerformanceMetrics(int $promotionId): array
    {
        $promotion = $this->find($promotionId);
        
        if (!$promotion) {
            return [];
        }

        $clickRate = $promotion['click_count'] > 0 ? 
                    ($promotion['unique_click_count'] / $promotion['click_count']) * 100 : 0;
        
        $conversionRate = $promotion['unique_click_count'] > 0 ? 
                         ($promotion['conversion_count'] / $promotion['unique_click_count']) * 100 : 0;

        return [
            'total_clicks' => $promotion['click_count'],
            'unique_clicks' => $promotion['unique_click_count'],
            'conversions' => $promotion['conversion_count'],
            'click_rate' => round($clickRate, 2),
            'conversion_rate' => round($conversionRate, 2),
            'status' => $promotion['status'],
            'created_at' => $promotion['created_at'],
            'last_clicked_at' => $promotion['last_clicked_at'],
        ];
    }

    /**
     * Pause promotion
     */
    public function pausePromotion(int $promotionId): bool
    {
        return $this->update($promotionId, ['status' => 'paused']);
    }

    /**
     * Resume promotion
     */
    public function resumePromotion(int $promotionId): bool
    {
        return $this->update($promotionId, ['status' => 'active']);
    }

    /**
     * Ban promotion
     */
    public function banPromotion(int $promotionId): bool
    {
        return $this->update($promotionId, ['status' => 'banned']);
    }
}