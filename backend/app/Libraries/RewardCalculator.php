<?php

namespace App\Libraries;

use App\Models\RewardModel;
use App\Models\RewardSettingModel;
use App\Models\PromotionModel;
use App\Models\UserModel;
use App\Models\ServerModel;
use CodeIgniter\Cache\CacheInterface;

class RewardCalculator
{
    private RewardModel $rewardModel;
    private RewardSettingModel $settingModel;
    private PromotionModel $promotionModel;
    private UserModel $userModel;
    private ServerModel $serverModel;
    private CacheInterface $cache;
    private array $config;

    public function __construct()
    {
        $this->rewardModel = new RewardModel();
        $this->settingModel = new RewardSettingModel();
        $this->promotionModel = new PromotionModel();
        $this->userModel = new UserModel();
        $this->serverModel = new ServerModel();
        $this->cache = \Config\Services::cache();
        
        // Load configuration
        $this->config = [
            'max_daily_rewards' => 10,
            'max_weekly_rewards' => 50,
            'max_monthly_rewards' => 200,
            'reward_cooldown' => 300, // 5 minutes
            'enable_auto_approval' => true,
            'enable_auto_distribution' => true,
        ];
    }

    /**
     * Process promotion reward
     */
    public function processPromotionReward(int $promotionId, int $userId, array $context = []): array
    {
        $promotion = $this->promotionModel->find($promotionId);
        if (!$promotion) {
            throw new \InvalidArgumentException('Promotion not found');
        }

        $user = $this->userModel->find($userId);
        if (!$user) {
            throw new \InvalidArgumentException('User not found');
        }

        $server = $this->serverModel->find($promotion['server_id']);
        if (!$server) {
            throw new \InvalidArgumentException('Server not found');
        }

        // Get applicable reward settings
        $rewardSettings = $this->getApplicableRewardSettings($promotion['server_id'], 'promotion', $context);
        
        $processedRewards = [];
        $errors = [];

        foreach ($rewardSettings as $setting) {
            try {
                // Check if conditions are met
                if (!$this->checkRewardConditions($setting, $promotion, $user, $context)) {
                    continue;
                }

                // Check limits
                if (!$this->checkRewardLimits($setting, $userId, $promotion['server_id'])) {
                    continue;
                }

                // Calculate reward
                $reward = $this->calculateReward($setting, $promotion, $user, $context);
                
                if ($reward) {
                    $processedRewards[] = $reward;
                    
                    // Update setting usage statistics
                    $this->settingModel->updateUsageStats($setting['id'], true);
                }

            } catch (\Exception $e) {
                $errors[] = [
                    'setting_id' => $setting['id'],
                    'error' => $e->getMessage(),
                ];
                
                // Update error statistics
                $this->settingModel->updateUsageStats($setting['id'], false);
                log_message('error', 'Reward calculation failed: ' . $e->getMessage());
            }
        }

        return [
            'success' => count($processedRewards) > 0,
            'rewards' => $processedRewards,
            'errors' => $errors,
            'promotion_id' => $promotionId,
            'user_id' => $userId,
        ];
    }

    /**
     * Process referral reward
     */
    public function processReferralReward(int $referrerId, int $referredUserId, int $serverId, array $context = []): array
    {
        // Process reward for the referrer
        $referrerRewards = $this->processUserReward($referrerId, $serverId, 'referral', array_merge($context, [
            'event' => 'successful_referral',
            'referred_user_id' => $referredUserId,
        ]));

        // Process welcome reward for the referred user
        $referredRewards = $this->processUserReward($referredUserId, $serverId, 'promotion', array_merge($context, [
            'event' => 'user_registration',
            'referrer_id' => $referrerId,
        ]));

        return [
            'referrer_rewards' => $referrerRewards,
            'referred_rewards' => $referredRewards,
        ];
    }

    /**
     * Process user reward
     */
    public function processUserReward(int $userId, int $serverId, string $rewardType, array $context = []): array
    {
        $user = $this->userModel->find($userId);
        if (!$user) {
            throw new \InvalidArgumentException('User not found');
        }

        $server = $this->serverModel->find($serverId);
        if (!$server) {
            throw new \InvalidArgumentException('Server not found');
        }

        // Get applicable reward settings
        $rewardSettings = $this->getApplicableRewardSettings($serverId, null, $context, $rewardType);
        
        $processedRewards = [];
        $errors = [];

        foreach ($rewardSettings as $setting) {
            try {
                // Check if conditions are met
                if (!$this->checkRewardConditions($setting, null, $user, $context)) {
                    continue;
                }

                // Check limits
                if (!$this->checkRewardLimits($setting, $userId, $serverId)) {
                    continue;
                }

                // Calculate reward
                $reward = $this->calculateReward($setting, null, $user, $context);
                
                if ($reward) {
                    $processedRewards[] = $reward;
                    
                    // Update setting usage statistics
                    $this->settingModel->updateUsageStats($setting['id'], true);
                }

            } catch (\Exception $e) {
                $errors[] = [
                    'setting_id' => $setting['id'],
                    'error' => $e->getMessage(),
                ];
                
                // Update error statistics
                $this->settingModel->updateUsageStats($setting['id'], false);
                log_message('error', 'Reward calculation failed: ' . $e->getMessage());
            }
        }

        return [
            'success' => count($processedRewards) > 0,
            'rewards' => $processedRewards,
            'errors' => $errors,
            'user_id' => $userId,
            'server_id' => $serverId,
        ];
    }

    /**
     * Get applicable reward settings
     */
    private function getApplicableRewardSettings(int $serverId, ?string $settingType = null, array $context = [], ?string $rewardType = null): array
    {
        $cacheKey = "reward_settings_{$serverId}_{$settingType}_{$rewardType}";
        
        $settings = $this->cache->get($cacheKey);
        if (!$settings) {
            $settings = $this->settingModel->getActiveSettings($serverId, $settingType, $rewardType);
            
            // Cache for 5 minutes
            $this->cache->save($cacheKey, $settings, 300);
        }

        // Filter settings based on context
        $applicableSettings = [];
        
        foreach ($settings as $setting) {
            if ($this->settingModel->checkTriggerCondition($setting, $context)) {
                $applicableSettings[] = $setting;
            }
        }

        return $applicableSettings;
    }

    /**
     * Check reward conditions
     */
    private function checkRewardConditions(array $setting, ?array $promotion, array $user, array $context): bool
    {
        $triggerCondition = json_decode($setting['trigger_condition'], true);
        
        if (!$triggerCondition) {
            return false;
        }

        // Check basic event
        $expectedEvent = $triggerCondition['event'] ?? null;
        $actualEvent = $context['event'] ?? null;
        
        if ($expectedEvent && $expectedEvent !== $actualEvent) {
            return false;
        }

        // Check specific conditions
        $conditions = $triggerCondition['conditions'] ?? [];
        
        foreach ($conditions as $conditionKey => $expectedValue) {
            switch ($conditionKey) {
                case 'is_first_registration':
                    if ($expectedValue && $this->isReturningUser($user['id'])) {
                        return false;
                    }
                    break;
                    
                case 'referral_valid':
                    if ($expectedValue && !$this->isValidReferral($context)) {
                        return false;
                    }
                    break;
                    
                case 'user_level_min':
                    $userLevel = $this->getUserLevel($user['id'], $setting['server_id']);
                    if ($userLevel < $expectedValue) {
                        return false;
                    }
                    break;
                    
                case 'account_age_min':
                    $accountAge = time() - strtotime($user['created_at']);
                    if ($accountAge < $expectedValue * 24 * 3600) { // Convert days to seconds
                        return false;
                    }
                    break;
                    
                case 'referred_user_active':
                    if ($expectedValue && !$this->isUserActive($context['referred_user_id'] ?? 0)) {
                        return false;
                    }
                    break;
                    
                default:
                    // Custom condition check
                    if (!$this->checkCustomCondition($conditionKey, $expectedValue, $context)) {
                        return false;
                    }
                    break;
            }
        }

        return true;
    }

    /**
     * Check reward limits
     */
    private function checkRewardLimits(array $setting, int $userId, int $serverId): bool
    {
        // Check cooldown
        if ($this->isUserOnCooldown($userId, $setting['id'])) {
            return false;
        }

        // Check setting-specific limits
        if (!$this->settingModel->checkLimits($setting, $userId, $serverId)) {
            return false;
        }

        // Check global daily limits
        $dailyRewards = $this->rewardModel->where('user_id', $userId)
                                         ->where('server_id', $serverId)
                                         ->where('created_at >=', date('Y-m-d 00:00:00'))
                                         ->where('status !=', 'cancelled')
                                         ->countAllResults();
        
        if ($dailyRewards >= $this->config['max_daily_rewards']) {
            return false;
        }

        return true;
    }

    /**
     * Calculate reward
     */
    private function calculateReward(array $setting, ?array $promotion, array $user, array $context): ?array
    {
        $rewardConfig = json_decode($setting['reward_config'], true);
        
        if (!$rewardConfig) {
            throw new \InvalidArgumentException('Invalid reward configuration');
        }

        // Base reward data
        $rewardData = [
            'server_id' => $setting['server_id'],
            'user_id' => $user['id'],
            'promotion_id' => $promotion['id'] ?? null,
            'reward_type' => $setting['reward_type'],
            'reward_category' => $rewardConfig['category'] ?? 'unknown',
            'reward_name' => $rewardConfig['name'] ?? 'Reward',
            'reward_description' => $rewardConfig['description'] ?? null,
            'reward_amount' => $this->calculateRewardAmount($rewardConfig, $context),
            'reward_value' => $rewardConfig['value'] ?? null,
            'status' => $setting['auto_approve'] ? 'approved' : 'pending',
            'priority' => $setting['priority'],
            'game_character' => $context['game_character'] ?? null,
            'game_account' => $context['game_account'] ?? null,
            'distribution_method' => $rewardConfig['distribution_method'] ?? $setting['distribution_config']['method'] ?? 'auto',
            'distribution_config' => json_encode($setting['distribution_config'] ?? []),
            'metadata' => json_encode([
                'setting_id' => $setting['id'],
                'context' => $context,
                'calculated_at' => date('Y-m-d H:i:s'),
            ]),
        ];

        // Insert reward
        $rewardId = $this->rewardModel->insert($rewardData);
        
        if (!$rewardId) {
            throw new \RuntimeException('Failed to create reward record');
        }

        // Auto-approve if configured
        if ($setting['auto_approve']) {
            $this->rewardModel->approveReward($rewardId, 1); // System approval
        }

        // Auto-distribute if configured and approved
        if ($setting['auto_distribute'] && $setting['auto_approve']) {
            $this->scheduleRewardDistribution($rewardId);
        }

        // Set cooldown
        $this->setUserCooldown($user['id'], $setting['id']);

        return $this->rewardModel->find($rewardId);
    }

    /**
     * Calculate reward amount
     */
    private function calculateRewardAmount(array $rewardConfig, array $context): int
    {
        $baseAmount = $rewardConfig['amount'] ?? 0;
        
        // Apply multipliers
        $multiplier = 1.0;
        
        // First-time user bonus
        if ($context['is_first_registration'] ?? false) {
            $multiplier *= $rewardConfig['first_time_multiplier'] ?? 1.0;
        }
        
        // Referral chain bonus
        if ($context['referral_chain_length'] ?? 0 > 0) {
            $chainMultiplier = $rewardConfig['chain_multiplier'] ?? 1.1;
            $chainLength = min($context['referral_chain_length'], 5); // Cap at 5
            $multiplier *= pow($chainMultiplier, $chainLength);
        }
        
        // Special event bonus
        if ($context['special_event'] ?? false) {
            $multiplier *= $rewardConfig['event_multiplier'] ?? 1.5;
        }
        
        // Random bonus (if configured)
        if ($rewardConfig['random_bonus'] ?? false) {
            $randomMultiplier = rand(100, 150) / 100; // 1.0x to 1.5x
            $multiplier *= $randomMultiplier;
        }

        return (int) round($baseAmount * $multiplier);
    }

    /**
     * Schedule reward distribution  
     */
    private function scheduleRewardDistribution(int $rewardId): void
    {
        // This would typically queue the reward for distribution
        // For now, we'll just log it
        log_message('info', "Reward {$rewardId} scheduled for distribution");
        
        // You could integrate with a job queue system here
        // Or trigger immediate distribution for auto rewards
        // $this->distributeReward($rewardId);
    }

    /**
     * Check if user is returning user
     */
    private function isReturningUser(int $userId): bool
    {
        return $this->userModel->where('id', $userId)
                              ->where('created_at <', date('Y-m-d H:i:s', strtotime('-1 day')))
                              ->first() !== null;
    }

    /**
     * Check if referral is valid
     */
    private function isValidReferral(array $context): bool
    {
        // Check if referrer exists and is active
        $referrerId = $context['referrer_id'] ?? null;
        if (!$referrerId) {
            return false;
        }

        $referrer = $this->userModel->find($referrerId);
        return $referrer && $referrer['status'] === 'active';
    }

    /**
     * Get user level (mock implementation)
     */
    private function getUserLevel(int $userId, int $serverId): int
    {
        // This would typically query the game database
        // For now, return a default level
        return 1;
    }

    /**
     * Check if user is active
     */
    private function isUserActive(int $userId): bool
    {
        if (!$userId) {
            return false;
        }

        $user = $this->userModel->find($userId);
        return $user && $user['status'] === 'active';
    }

    /**
     * Check custom condition
     */
    private function checkCustomCondition(string $conditionKey, $expectedValue, array $context): bool
    {
        // Implement custom condition logic here
        // This is extensible for future requirements
        
        switch ($conditionKey) {
            case 'minimum_friends':
                return ($context['friend_count'] ?? 0) >= $expectedValue;
                
            case 'has_purchased':
                return $context['has_purchased'] ?? false;
                
            case 'server_member':
                return in_array($context['server_id'] ?? 0, $context['member_servers'] ?? []);
                
            default:
                return true; // Unknown conditions pass by default
        }
    }

    /**
     * Check if user is on cooldown
     */
    private function isUserOnCooldown(int $userId, int $settingId): bool
    {
        $cacheKey = "reward_cooldown_{$userId}_{$settingId}";
        return $this->cache->get($cacheKey) !== null;
    }

    /**
     * Set user cooldown
     */
    private function setUserCooldown(int $userId, int $settingId): void
    {
        $cacheKey = "reward_cooldown_{$userId}_{$settingId}";
        $this->cache->save($cacheKey, true, $this->config['reward_cooldown']);
    }

    /**
     * Get reward calculation preview
     */
    public function previewReward(int $settingId, int $userId, array $context = []): array
    {
        $setting = $this->settingModel->find($settingId);
        if (!$setting) {
            throw new \InvalidArgumentException('Reward setting not found');
        }

        $user = $this->userModel->find($userId);
        if (!$user) {
            throw new \InvalidArgumentException('User not found');
        }

        // Check conditions
        $conditionsMet = $this->checkRewardConditions($setting, null, $user, $context);
        $limitsOk = $this->checkRewardLimits($setting, $userId, $setting['server_id']);

        // Calculate potential reward
        $rewardConfig = json_decode($setting['reward_config'], true);
        $calculatedAmount = $this->calculateRewardAmount($rewardConfig, $context);

        return [
            'setting_id' => $settingId,
            'setting_name' => $setting['setting_name'],
            'user_id' => $userId,
            'conditions_met' => $conditionsMet,
            'limits_ok' => $limitsOk,
            'eligible' => $conditionsMet && $limitsOk,
            'reward_preview' => [
                'category' => $rewardConfig['category'] ?? 'unknown',
                'name' => $rewardConfig['name'] ?? 'Reward',
                'amount' => $calculatedAmount,
                'description' => $rewardConfig['description'] ?? null,
            ],
            'context' => $context,
        ];
    }

    /**
     * Get user reward history
     */
    public function getUserRewardHistory(int $userId, int $serverId = null, int $limit = 50): array
    {
        $filters = ['status' => ['approved', 'distributed']];
        
        if ($serverId) {
            $filters['server_id'] = $serverId;
        }

        return $this->rewardModel->getUserRewards($userId, $filters, 1, $limit);
    }

    /**
     * Calculate reward statistics
     */
    public function calculateRewardStats(int $serverId = null, string $period = '30 days'): array
    {
        return $this->rewardModel->getRewardStats($serverId, $period);
    }

    /**
     * Get reward leaderboard
     */
    public function getRewardLeaderboard(int $serverId = null, int $limit = 10, string $period = '30 days'): array
    {
        return $this->rewardModel->getTopEarners($serverId, $limit, $period);
    }

    /**
     * Recalculate reward
     */
    public function recalculateReward(int $rewardId, array $newContext = []): array
    {
        $reward = $this->rewardModel->find($rewardId);
        if (!$reward || $reward['status'] !== 'pending') {
            throw new \InvalidArgumentException('Reward not found or already processed');
        }

        $setting = $this->settingModel->find(
            json_decode($reward['metadata'], true)['setting_id'] ?? 0
        );
        
        if (!$setting) {
            throw new \InvalidArgumentException('Reward setting not found');
        }

        $user = $this->userModel->find($reward['user_id']);
        if (!$user) {
            throw new \InvalidArgumentException('User not found');
        }

        // Merge original context with new context
        $originalContext = json_decode($reward['metadata'], true)['context'] ?? [];
        $context = array_merge($originalContext, $newContext);

        // Recalculate
        $rewardConfig = json_decode($setting['reward_config'], true);
        $newAmount = $this->calculateRewardAmount($rewardConfig, $context);

        // Update reward
        $updateData = [
            'reward_amount' => $newAmount,
            'metadata' => json_encode([
                'setting_id' => $setting['id'],
                'context' => $context,
                'recalculated_at' => date('Y-m-d H:i:s'),
                'original_amount' => $reward['reward_amount'],
            ]),
        ];

        $this->rewardModel->update($rewardId, $updateData);

        return $this->rewardModel->find($rewardId);
    }
}