<?php

namespace App\Models;

use CodeIgniter\Model;

class RewardSettingModel extends Model
{
    protected $table = 'reward_settings';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;

    protected $allowedFields = [
        'server_id', 'setting_name', 'setting_key', 'setting_type', 'reward_type',
        'trigger_condition', 'reward_config', 'limits_config', 'distribution_config',
        'auto_approve', 'auto_distribute', 'is_active', 'priority', 'valid_from',
        'valid_until', 'usage_count', 'success_count', 'error_count', 'last_used_at',
        'description', 'metadata', 'created_by', 'updated_by'
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
        'setting_name' => 'required|max_length[100]',
        'setting_key' => 'required|max_length[100]',
        'setting_type' => 'required|in_list[promotion,activity,checkin,general]',
        'reward_type' => 'required|in_list[promotion,activity,checkin,referral,bonus]',
        'trigger_condition' => 'required|valid_json',
        'reward_config' => 'required|valid_json',
        'priority' => 'permit_empty|integer|greater_than[0]|less_than[11]',
        'valid_from' => 'permit_empty|valid_date',
        'valid_until' => 'permit_empty|valid_date',
    ];

    protected $validationMessages = [
        'server_id' => [
            'required' => 'Server ID is required',
            'is_not_unique' => 'Server does not exist',
        ],
        'setting_name' => [
            'required' => 'Setting name is required',
            'max_length' => 'Setting name cannot exceed 100 characters',
        ],
        'setting_key' => [
            'required' => 'Setting key is required',
            'max_length' => 'Setting key cannot exceed 100 characters',
        ],
        'trigger_condition' => [
            'required' => 'Trigger condition is required',
            'valid_json' => 'Trigger condition must be valid JSON',
        ],
        'reward_config' => [
            'required' => 'Reward config is required',
            'valid_json' => 'Reward config must be valid JSON',
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = ['setDefaults', 'validateDates'];
    protected $beforeUpdate = ['validateDates'];

    /**
     * Set default values before insert
     */
    protected function setDefaults(array $data)
    {
        if (!isset($data['data']['priority']) || empty($data['data']['priority'])) {
            $data['data']['priority'] = 5;
        }

        if (!isset($data['data']['is_active'])) {
            $data['data']['is_active'] = true;
        }

        if (!isset($data['data']['auto_approve'])) {
            $data['data']['auto_approve'] = false;
        }

        if (!isset($data['data']['auto_distribute'])) {
            $data['data']['auto_distribute'] = false;
        }

        return $data;
    }

    /**
     * Validate date ranges
     */
    protected function validateDates(array $data)
    {
        if (isset($data['data']['valid_from']) && isset($data['data']['valid_until'])) {
            $validFrom = strtotime($data['data']['valid_from']);
            $validUntil = strtotime($data['data']['valid_until']);
            
            if ($validFrom && $validUntil && $validFrom >= $validUntil) {
                throw new \InvalidArgumentException('Valid from date must be before valid until date');
            }
        }

        return $data;
    }

    /**
     * Get active settings for server
     */
    public function getActiveSettings(int $serverId, string $settingType = null, string $rewardType = null): array
    {
        $builder = $this->where('server_id', $serverId)
                        ->where('is_active', true)
                        ->where('(valid_from IS NULL OR valid_from <= NOW())')
                        ->where('(valid_until IS NULL OR valid_until > NOW())');

        if ($settingType) {
            $builder->where('setting_type', $settingType);
        }

        if ($rewardType) {
            $builder->where('reward_type', $rewardType);
        }

        return $builder->orderBy('priority', 'ASC')
                       ->findAll();
    }

    /**
     * Get setting by key
     */
    public function getByKey(int $serverId, string $settingKey): ?array
    {
        return $this->where('server_id', $serverId)
                    ->where('setting_key', $settingKey)
                    ->where('is_active', true)
                    ->where('(valid_from IS NULL OR valid_from <= NOW())')
                    ->where('(valid_until IS NULL OR valid_until > NOW())')
                    ->first();
    }

    /**
     * Get promotion settings
     */
    public function getPromotionSettings(int $serverId): array
    {
        return $this->getActiveSettings($serverId, 'promotion');
    }

    /**
     * Get activity settings
     */
    public function getActivitySettings(int $serverId): array
    {
        return $this->getActiveSettings($serverId, 'activity');
    }

    /**
     * Get checkin settings
     */
    public function getCheckinSettings(int $serverId): array
    {
        return $this->getActiveSettings($serverId, 'checkin');
    }

    /**
     * Create default promotion settings
     */
    public function createDefaultPromotionSettings(int $serverId, int $createdBy): array
    {
        $defaultSettings = [
            [
                'server_id' => $serverId,
                'setting_name' => 'New User Registration Reward',
                'setting_key' => 'promotion_new_user',
                'setting_type' => 'promotion',
                'reward_type' => 'promotion',
                'trigger_condition' => json_encode([
                    'event' => 'user_registration',
                    'conditions' => [
                        'is_first_registration' => true,
                        'referral_valid' => true,
                    ],
                ]),
                'reward_config' => json_encode([
                    'category' => 'coins',
                    'name' => 'Welcome Bonus',
                    'amount' => 1000,
                    'description' => 'Welcome bonus for new users',
                ]),
                'limits_config' => json_encode([
                    'per_user' => 1,
                    'per_referrer_daily' => 10,
                    'per_referrer_monthly' => 100,
                ]),
                'distribution_config' => json_encode([
                    'method' => 'auto',
                    'table' => 'user_coins',
                    'sql' => 'UPDATE user_coins SET coins = coins + {amount} WHERE user_id = {user_id}',
                ]),
                'auto_approve' => true,
                'auto_distribute' => true,
                'priority' => 3,
                'created_by' => $createdBy,
            ],
            [
                'server_id' => $serverId,
                'setting_name' => 'Referrer Reward',
                'setting_key' => 'promotion_referrer',
                'setting_type' => 'promotion',
                'reward_type' => 'referral',
                'trigger_condition' => json_encode([
                    'event' => 'successful_referral',
                    'conditions' => [
                        'referred_user_active' => true,
                        'referral_count_limit' => 10,
                    ],
                ]),
                'reward_config' => json_encode([
                    'category' => 'coins',
                    'name' => 'Referral Bonus',
                    'amount' => 500,
                    'description' => 'Bonus for successful referral',
                ]),
                'limits_config' => json_encode([
                    'per_referrer_daily' => 10,
                    'per_referrer_weekly' => 50,
                    'per_referrer_monthly' => 200,
                ]),
                'distribution_config' => json_encode([
                    'method' => 'auto',
                    'table' => 'user_coins',
                    'sql' => 'UPDATE user_coins SET coins = coins + {amount} WHERE user_id = {user_id}',
                ]),
                'auto_approve' => true,
                'auto_distribute' => true,
                'priority' => 3,
                'created_by' => $createdBy,
            ],
        ];

        $inserted = [];
        foreach ($defaultSettings as $setting) {
            $id = $this->insert($setting);
            if ($id) {
                $inserted[] = $this->find($id);
            }
        }

        return $inserted;
    }

    /**
     * Update usage statistics
     */
    public function updateUsageStats(int $settingId, bool $success = true): bool
    {
        $setting = $this->find($settingId);
        if (!$setting) {
            return false;
        }

        $data = [
            'usage_count' => $setting['usage_count'] + 1,
            'last_used_at' => date('Y-m-d H:i:s'),
        ];

        if ($success) {
            $data['success_count'] = $setting['success_count'] + 1;
        } else {
            $data['error_count'] = $setting['error_count'] + 1;
        }

        return $this->update($settingId, $data);
    }

    /**
     * Check if condition is met
     */
    public function checkTriggerCondition(array $setting, array $context): bool
    {
        $condition = json_decode($setting['trigger_condition'], true);
        
        if (!$condition) {
            return false;
        }

        // Check basic event match
        if (isset($condition['event']) && $condition['event'] !== ($context['event'] ?? '')) {
            return false;
        }

        // Check conditions
        if (isset($condition['conditions'])) {
            foreach ($condition['conditions'] as $key => $expectedValue) {
                $actualValue = $context[$key] ?? null;
                
                // Handle different condition types
                if (is_bool($expectedValue)) {
                    if ($actualValue !== $expectedValue) {
                        return false;
                    }
                } elseif (is_numeric($expectedValue)) {
                    if ($actualValue < $expectedValue) {
                        return false;
                    }
                } elseif (is_string($expectedValue)) {
                    if ($actualValue !== $expectedValue) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Check limits
     */
    public function checkLimits(array $setting, int $userId, int $serverId): bool
    {
        $limits = json_decode($setting['limits_config'], true);
        
        if (!$limits) {
            return true; // No limits defined
        }

        $rewardModel = new RewardModel();

        // Check per user limit
        if (isset($limits['per_user'])) {
            $userCount = $rewardModel->where('user_id', $userId)
                                   ->where('server_id', $serverId)
                                   ->where('reward_type', $setting['reward_type'])
                                   ->where('status !=', 'cancelled')
                                   ->countAllResults();
            
            if ($userCount >= $limits['per_user']) {
                return false;
            }
        }

        // Check daily limits
        if (isset($limits['per_referrer_daily'])) {
            $dailyCount = $rewardModel->where('user_id', $userId)
                                    ->where('server_id', $serverId)
                                    ->where('reward_type', $setting['reward_type'])
                                    ->where('status !=', 'cancelled')
                                    ->where('created_at >=', date('Y-m-d 00:00:00'))
                                    ->countAllResults();
            
            if ($dailyCount >= $limits['per_referrer_daily']) {
                return false;
            }
        }

        // Check weekly limits
        if (isset($limits['per_referrer_weekly'])) {
            $weeklyCount = $rewardModel->where('user_id', $userId)
                                     ->where('server_id', $serverId)
                                     ->where('reward_type', $setting['reward_type'])
                                     ->where('status !=', 'cancelled')
                                     ->where('created_at >=', date('Y-m-d 00:00:00', strtotime('monday this week')))
                                     ->countAllResults();
            
            if ($weeklyCount >= $limits['per_referrer_weekly']) {
                return false;
            }
        }

        // Check monthly limits
        if (isset($limits['per_referrer_monthly'])) {
            $monthlyCount = $rewardModel->where('user_id', $userId)
                                      ->where('server_id', $serverId)
                                      ->where('reward_type', $setting['reward_type'])
                                      ->where('status !=', 'cancelled')
                                      ->where('created_at >=', date('Y-m-01 00:00:00'))
                                      ->countAllResults();
            
            if ($monthlyCount >= $limits['per_referrer_monthly']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get settings with statistics
     */
    public function getSettingsWithStats(int $serverId, array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $builder = $this->select('reward_settings.*, 
                                 creator.username as creator_username,
                                 updater.username as updater_username')
                        ->join('users as creator', 'creator.id = reward_settings.created_by', 'left')
                        ->join('users as updater', 'updater.id = reward_settings.updated_by', 'left')
                        ->where('reward_settings.server_id', $serverId);

        // Apply filters
        if (!empty($filters['setting_type'])) {
            $builder->where('reward_settings.setting_type', $filters['setting_type']);
        }

        if (!empty($filters['reward_type'])) {
            $builder->where('reward_settings.reward_type', $filters['reward_type']);
        }

        if (!empty($filters['is_active'])) {
            $builder->where('reward_settings.is_active', $filters['is_active']);
        }

        if (!empty($filters['search'])) {
            $builder->groupStart()
                    ->like('reward_settings.setting_name', $filters['search'])
                    ->orLike('reward_settings.setting_key', $filters['search'])
                    ->groupEnd();
        }

        $total = $builder->countAllResults(false);
        $settings = $builder->orderBy('reward_settings.priority', 'ASC')
                           ->orderBy('reward_settings.created_at', 'DESC')
                           ->limit($perPage, ($page - 1) * $perPage)
                           ->get()
                           ->getResultArray();

        // Calculate success rates
        foreach ($settings as &$setting) {
            $setting['success_rate'] = $setting['usage_count'] > 0 ? 
                round(($setting['success_count'] / $setting['usage_count']) * 100, 2) : 0;
            
            $setting['error_rate'] = $setting['usage_count'] > 0 ? 
                round(($setting['error_count'] / $setting['usage_count']) * 100, 2) : 0;
        }

        return [
            'settings' => $settings,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage),
        ];
    }

    /**
     * Activate setting
     */
    public function activateSetting(int $settingId): bool
    {
        return $this->update($settingId, ['is_active' => true]);
    }

    /**
     * Deactivate setting
     */
    public function deactivateSetting(int $settingId): bool
    {
        return $this->update($settingId, ['is_active' => false]);
    }

    /**
     * Clone setting to another server
     */
    public function cloneSetting(int $settingId, int $targetServerId, int $createdBy): ?int
    {
        $setting = $this->find($settingId);
        if (!$setting) {
            return null;
        }

        // Remove fields that shouldn't be cloned
        unset($setting['id'], $setting['created_at'], $setting['updated_at'], $setting['deleted_at']);
        unset($setting['usage_count'], $setting['success_count'], $setting['error_count'], $setting['last_used_at']);

        // Update for new server
        $setting['server_id'] = $targetServerId;
        $setting['created_by'] = $createdBy;
        $setting['updated_by'] = null;

        return $this->insert($setting);
    }
}