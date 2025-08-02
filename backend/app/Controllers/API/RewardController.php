<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Models\RewardModel;
use App\Models\RewardSettingModel;
use App\Libraries\RewardCalculator;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\API\ResponseTrait;

class RewardController extends BaseController
{
    use ResponseTrait;

    private RewardModel $rewardModel;
    private RewardSettingModel $settingModel;
    private RewardCalculator $calculator;

    public function __construct()
    {
        $this->rewardModel = new RewardModel();
        $this->settingModel = new RewardSettingModel();
        $this->calculator = new RewardCalculator();
    }

    /**
     * Get rewards list with pagination and filters
     */
    public function index(): ResponseInterface
    {
        try {
            $page = (int) ($this->request->getGet('page') ?? 1);
            $perPage = min((int) ($this->request->getGet('per_page') ?? 20), 100);
            
            $filters = [
                'server_id' => $this->request->getGet('server_id'),
                'user_id' => $this->request->getGet('user_id'),
                'status' => $this->request->getGet('status'),
                'reward_type' => $this->request->getGet('reward_type'),
                'priority' => $this->request->getGet('priority'),
                'date_from' => $this->request->getGet('date_from'),
                'date_to' => $this->request->getGet('date_to'),
                'search' => $this->request->getGet('search'),
            ];

            // Remove empty filters
            $filters = array_filter($filters);

            $result = $this->rewardModel->getRewards($filters, $page, $perPage);

            $response = [
                'status' => 'success',
                'message' => 'Rewards retrieved successfully',
                'data' => $result,
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Rewards retrieval error: ' . $e->getMessage());
            return $this->fail('Failed to retrieve rewards', 500);
        }
    }

    /**
     * Get single reward by ID
     */
    public function show(int $id): ResponseInterface
    {
        try {
            $reward = $this->rewardModel->select('rewards.*, 
                                                servers.name as server_name, servers.code as server_code,
                                                users.username as user_username, users.email as user_email,
                                                promotions.promotion_code,
                                                approver.username as approver_username')
                                       ->join('servers', 'servers.id = rewards.server_id')
                                       ->join('users', 'users.id = rewards.user_id')
                                       ->join('promotions', 'promotions.id = rewards.promotion_id', 'left')
                                       ->join('users as approver', 'approver.id = rewards.approved_by', 'left')
                                       ->find($id);

            if (!$reward) {
                return $this->failNotFound('Reward not found');
            }

            // Parse metadata
            $reward['metadata_parsed'] = json_decode($reward['metadata'], true);
            $reward['distribution_config_parsed'] = json_decode($reward['distribution_config'], true);

            $response = [
                'status' => 'success',
                'message' => 'Reward retrieved successfully',
                'data' => [
                    'reward' => $reward,
                ],
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Reward retrieval error: ' . $e->getMessage());
            return $this->fail('Failed to retrieve reward', 500);
        }
    }

    /**
     * Create new reward manually
     */
    public function create(): ResponseInterface
    {
        try {
            $rules = [
                'server_id' => 'required|integer|is_not_unique[servers.id]',
                'user_id' => 'required|integer|is_not_unique[users.id]',
                'promotion_id' => 'permit_empty|integer|is_not_unique[promotions.id]',
                'reward_type' => 'required|in_list[promotion,activity,checkin,referral,bonus]',
                'reward_category' => 'required|max_length[50]',
                'reward_name' => 'required|max_length[100]',
                'reward_description' => 'permit_empty|max_length[1000]',
                'reward_amount' => 'required|integer|greater_than[0]',
                'reward_value' => 'permit_empty|decimal',
                'priority' => 'permit_empty|integer|greater_than[0]|less_than[11]',
                'game_character' => 'permit_empty|max_length[100]',
                'game_account' => 'permit_empty|max_length[100]',
                'distribution_method' => 'permit_empty|in_list[auto,manual,api,database]',
                'auto_approve' => 'permit_empty|boolean',
            ];

            if (!$this->validateData($this->request->getJSON(true), $rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }

            $data = $this->request->getJSON(true);
            $currentUserId = $this->request->userPayload['user_id'];

            // Prepare reward data
            $rewardData = [
                'server_id' => $data['server_id'],
                'user_id' => $data['user_id'],
                'promotion_id' => $data['promotion_id'] ?? null,
                'reward_type' => $data['reward_type'],
                'reward_category' => $data['reward_category'],
                'reward_name' => $data['reward_name'],
                'reward_description' => $data['reward_description'] ?? null,
                'reward_amount' => $data['reward_amount'],
                'reward_value' => $data['reward_value'] ?? null,
                'status' => ($data['auto_approve'] ?? false) ? 'approved' : 'pending',
                'priority' => $data['priority'] ?? 5,
                'game_character' => $data['game_character'] ?? null,
                'game_account' => $data['game_account'] ?? null,
                'distribution_method' => $data['distribution_method'] ?? 'manual',
                'distribution_config' => json_encode($data['distribution_config'] ?? []),
                'metadata' => json_encode([
                    'created_via' => 'manual_api',
                    'created_by' => $currentUserId,
                    'created_at' => date('Y-m-d H:i:s'),
                ]),
            ];

            // Auto-approve if requested and user has permission
            if (($data['auto_approve'] ?? false) && $this->hasPermission('reward.approve')) {
                $rewardData['approved_by'] = $currentUserId;
                $rewardData['approved_at'] = date('Y-m-d H:i:s');
            }

            $rewardId = $this->rewardModel->insert($rewardData);

            if (!$rewardId) {
                return $this->fail('Failed to create reward', 500);
            }

            // Get created reward with details
            $reward = $this->rewardModel->select('rewards.*, 
                                                servers.name as server_name,
                                                users.username as user_username')
                                       ->join('servers', 'servers.id = rewards.server_id')
                                       ->join('users', 'users.id = rewards.user_id')
                                       ->find($rewardId);

            $response = [
                'status' => 'success',
                'message' => 'Reward created successfully',
                'data' => [
                    'reward' => $reward,
                ],
            ];

            return $this->respondCreated($response);

        } catch (\Exception $e) {
            log_message('error', 'Reward creation error: ' . $e->getMessage());
            return $this->fail('Failed to create reward', 500);
        }
    }

    /**
     * Update reward
     */
    public function update(int $id): ResponseInterface
    {
        try {
            $reward = $this->rewardModel->find($id);
            if (!$reward) {
                return $this->failNotFound('Reward not found');
            }

            // Only allow updates for pending or failed rewards
            if (!in_array($reward['status'], ['pending', 'failed'])) {
                return $this->fail('Cannot update reward in current status', 400);
            }

            $rules = [
                'reward_category' => 'permit_empty|max_length[50]',
                'reward_name' => 'permit_empty|max_length[100]',
                'reward_description' => 'permit_empty|max_length[1000]',
                'reward_amount' => 'permit_empty|integer|greater_than[0]',
                'reward_value' => 'permit_empty|decimal',
                'priority' => 'permit_empty|integer|greater_than[0]|less_than[11]',
                'game_character' => 'permit_empty|max_length[100]',
                'game_account' => 'permit_empty|max_length[100]',
                'distribution_method' => 'permit_empty|in_list[auto,manual,api,database]',
            ];

            if (!$this->validateData($this->request->getJSON(true), $rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }

            $data = $this->request->getJSON(true);

            // Prepare update data
            $updateData = [];
            $allowedFields = [
                'reward_category', 'reward_name', 'reward_description', 
                'reward_amount', 'reward_value', 'priority', 
                'game_character', 'game_account', 'distribution_method'
            ];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (isset($data['distribution_config'])) {
                $updateData['distribution_config'] = json_encode($data['distribution_config']);
            }

            // Update metadata
            $currentMetadata = json_decode($reward['metadata'], true) ?? [];
            $currentMetadata['updated_via'] = 'api';
            $currentMetadata['updated_by'] = $this->request->userPayload['user_id'];
            $currentMetadata['updated_at'] = date('Y-m-d H:i:s');
            $updateData['metadata'] = json_encode($currentMetadata);

            if (!$this->rewardModel->update($id, $updateData)) {
                return $this->fail('Failed to update reward', 500);
            }

            // Get updated reward
            $updatedReward = $this->rewardModel->select('rewards.*, 
                                                       servers.name as server_name,
                                                       users.username as user_username')
                                              ->join('servers', 'servers.id = rewards.server_id')
                                              ->join('users', 'users.id = rewards.user_id')
                                              ->find($id);

            $response = [
                'status' => 'success',
                'message' => 'Reward updated successfully',
                'data' => [
                    'reward' => $updatedReward,
                ],
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Reward update error: ' . $e->getMessage());
            return $this->fail('Failed to update reward', 500);
        }
    }

    /**
     * Delete reward (cancel)
     */
    public function delete(int $id): ResponseInterface
    {
        try {
            $reward = $this->rewardModel->find($id);
            if (!$reward) {
                return $this->failNotFound('Reward not found');
            }

            // Only allow cancellation for pending rewards
            if ($reward['status'] !== 'pending') {
                return $this->fail('Cannot cancel reward in current status', 400);
            }

            $data = $this->request->getJSON(true) ?? [];
            $reason = $data['reason'] ?? 'Cancelled via API';

            if (!$this->rewardModel->cancelReward($id, $reason)) {
                return $this->fail('Failed to cancel reward', 500);
            }

            $response = [
                'status' => 'success',
                'message' => 'Reward cancelled successfully',
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Reward cancellation error: ' . $e->getMessage());
            return $this->fail('Failed to cancel reward', 500);
        }
    }

    /**
     * Approve reward
     */
    public function approve(int $id): ResponseInterface
    {
        try {
            if (!$this->hasPermission('reward.approve')) {
                return $this->failForbidden('Insufficient permissions to approve rewards');
            }

            $reward = $this->rewardModel->find($id);
            if (!$reward) {
                return $this->failNotFound('Reward not found');
            }

            if ($reward['status'] !== 'pending') {
                return $this->fail('Reward is not in pending status', 400);
            }

            $currentUserId = $this->request->userPayload['user_id'];

            if (!$this->rewardModel->approveReward($id, $currentUserId)) {
                return $this->fail('Failed to approve reward', 500);
            }

            $response = [
                'status' => 'success',
                'message' => 'Reward approved successfully',
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Reward approval error: ' . $e->getMessage());
            return $this->fail('Failed to approve reward', 500);
        }
    }

    /**
     * Mark reward as distributed
     */
    public function markDistributed(int $id): ResponseInterface
    {
        try {
            if (!$this->hasPermission('reward.distribute')) {
                return $this->failForbidden('Insufficient permissions to mark rewards as distributed');
            }

            $reward = $this->rewardModel->find($id);
            if (!$reward) {
                return $this->failNotFound('Reward not found');
            }

            if ($reward['status'] !== 'approved') {
                return $this->fail('Reward is not in approved status', 400);
            }

            $data = $this->request->getJSON(true) ?? [];
            $metadata = [
                'distributed_by' => $this->request->userPayload['user_id'],
                'distribution_notes' => $data['notes'] ?? null,
                'distribution_reference' => $data['reference'] ?? null,
            ];

            if (!$this->rewardModel->markAsDistributed($id, $metadata)) {
                return $this->fail('Failed to mark reward as distributed', 500);
            }

            $response = [
                'status' => 'success',
                'message' => 'Reward marked as distributed successfully',
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Reward distribution error: ' . $e->getMessage());
            return $this->fail('Failed to mark reward as distributed', 500);
        }
    }

    /**
     * Get user rewards
     */
    public function userRewards(int $userId): ResponseInterface
    {
        try {
            $page = (int) ($this->request->getGet('page') ?? 1);
            $perPage = min((int) ($this->request->getGet('per_page') ?? 20), 100);
            
            $filters = [
                'server_id' => $this->request->getGet('server_id'),
                'status' => $this->request->getGet('status'),
                'reward_type' => $this->request->getGet('reward_type'),
                'date_from' => $this->request->getGet('date_from'),
                'date_to' => $this->request->getGet('date_to'),
            ];

            // Remove empty filters
            $filters = array_filter($filters);

            $result = $this->rewardModel->getUserRewards($userId, $filters, $page, $perPage);

            $response = [
                'status' => 'success',
                'message' => 'User rewards retrieved successfully',
                'data' => $result,
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'User rewards retrieval error: ' . $e->getMessage());
            return $this->fail('Failed to retrieve user rewards', 500);
        }
    }

    /**
     * Get reward statistics
     */
    public function statistics(): ResponseInterface
    {
        try {
            $serverId = $this->request->getGet('server_id');
            $period = $this->request->getGet('period') ?? '30 days';

            $stats = $this->calculator->calculateRewardStats($serverId, $period);

            $response = [
                'status' => 'success',
                'message' => 'Reward statistics retrieved successfully',
                'data' => [
                    'period' => $period,
                    'server_id' => $serverId,
                    'statistics' => $stats,
                ],
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Reward statistics error: ' . $e->getMessage());
            return $this->fail('Failed to retrieve reward statistics', 500);
        }
    }

    /**
     * Get reward leaderboard
     */
    public function leaderboard(): ResponseInterface
    {
        try {
            $serverId = $this->request->getGet('server_id');
            $limit = min((int) ($this->request->getGet('limit') ?? 10), 50);
            $period = $this->request->getGet('period') ?? '30 days';

            $leaderboard = $this->calculator->getRewardLeaderboard($serverId, $limit, $period);

            $response = [
                'status' => 'success',
                'message' => 'Reward leaderboard retrieved successfully',
                'data' => [
                    'period' => $period,
                    'server_id' => $serverId,
                    'leaderboard' => $leaderboard,
                ],
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Reward leaderboard error: ' . $e->getMessage());
            return $this->fail('Failed to retrieve reward leaderboard', 500);
        }
    }

    /**
     * Preview reward calculation
     */
    public function preview(): ResponseInterface
    {
        try {
            $rules = [
                'setting_id' => 'required|integer|is_not_unique[reward_settings.id]',
                'user_id' => 'required|integer|is_not_unique[users.id]',
                'context' => 'permit_empty|valid_json_string',
            ];

            if (!$this->validateData($this->request->getJSON(true), $rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }

            $data = $this->request->getJSON(true);
            $context = $data['context'] ? json_decode($data['context'], true) : [];

            $preview = $this->calculator->previewReward($data['setting_id'], $data['user_id'], $context);

            $response = [
                'status' => 'success',
                'message' => 'Reward preview calculated successfully',
                'data' => $preview,
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Reward preview error: ' . $e->getMessage());
            return $this->fail('Failed to calculate reward preview', 500);
        }
    }

    /**
     * Process bulk rewards
     */
    public function bulkProcess(): ResponseInterface
    {
        try {
            if (!$this->hasPermission('reward.bulk_process')) {
                return $this->failForbidden('Insufficient permissions for bulk processing');
            }

            $rules = [
                'action' => 'required|in_list[approve,distribute,cancel]',
                'reward_ids' => 'required|is_array',
                'reason' => 'permit_empty|max_length[255]',
            ];

            if (!$this->validateData($this->request->getJSON(true), $rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }

            $data = $this->request->getJSON(true);
            $currentUserId = $this->request->userPayload['user_id'];
            
            $results = [
                'success' => [],
                'failed' => [],
            ];

            foreach ($data['reward_ids'] as $rewardId) {
                try {
                    $reward = $this->rewardModel->find($rewardId);
                    if (!$reward) {
                        $results['failed'][] = [
                            'id' => $rewardId,
                            'error' => 'Reward not found',
                        ];
                        continue;
                    }

                    $success = false;

                    switch ($data['action']) {
                        case 'approve':
                            if ($reward['status'] === 'pending') {
                                $success = $this->rewardModel->approveReward($rewardId, $currentUserId);
                            }
                            break;

                        case 'distribute':
                            if ($reward['status'] === 'approved') {
                                $success = $this->rewardModel->markAsDistributed($rewardId, [
                                    'distributed_by' => $currentUserId,
                                    'bulk_process' => true,
                                ]);
                            }
                            break;

                        case 'cancel':
                            if ($reward['status'] === 'pending') {
                                $success = $this->rewardModel->cancelReward($rewardId, $data['reason'] ?? 'Bulk cancellation');
                            }
                            break;
                    }

                    if ($success) {
                        $results['success'][] = $rewardId;
                    } else {
                        $results['failed'][] = [
                            'id' => $rewardId,
                            'error' => 'Action failed or invalid status',
                        ];
                    }

                } catch (\Exception $e) {
                    $results['failed'][] = [
                        'id' => $rewardId,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            $response = [
                'status' => 'success',
                'message' => 'Bulk processing completed',
                'data' => [
                    'action' => $data['action'],
                    'processed' => count($data['reward_ids']),
                    'successful' => count($results['success']),
                    'failed' => count($results['failed']),
                    'results' => $results,
                ],
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Bulk reward processing error: ' . $e->getMessage());
            return $this->fail('Failed to process bulk rewards', 500);
        }
    }

    /**
     * Get reward history
     */
    public function history(): ResponseInterface
    {
        try {
            $userId = $this->request->getGet('user_id');
            $serverId = $this->request->getGet('server_id');
            $limit = min((int) ($this->request->getGet('limit') ?? 50), 100);

            if (!$userId) {
                $userId = $this->request->userPayload['user_id'];
            }

            $rewards = $this->calculator->getUserRewardHistory($userId, $serverId, $limit);

            $response = [
                'status' => 'success',
                'message' => 'Reward history retrieved successfully',
                'data' => [
                    'user_id' => $userId,
                    'server_id' => $serverId,
                    'rewards' => $rewards,
                ],
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Reward history error: ' . $e->getMessage());
            return $this->fail('Failed to retrieve reward history', 500);
        }
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