<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Models\ServerModel;
use App\Models\UserModel;
use App\Libraries\FileUploadService;
use App\Libraries\DatabaseTestService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\API\ResponseTrait;

class ServerController extends BaseController
{
    use ResponseTrait;

    private ServerModel $serverModel;
    private UserModel $userModel;
    private FileUploadService $fileUploadService;
    private DatabaseTestService $databaseTestService;

    public function __construct()
    {
        $this->serverModel = new ServerModel();
        $this->userModel = new UserModel();
        $this->fileUploadService = new FileUploadService();
        $this->databaseTestService = new DatabaseTestService();
    }

    /**
     * Get servers list with pagination and filters
     */
    public function index(): ResponseInterface
    {
        try {
            $page = (int) ($this->request->getGet('page') ?? 1);
            $perPage = min((int) ($this->request->getGet('per_page') ?? 20), 100);
            
            $filters = [
                'status' => $this->request->getGet('status'),
                'game_type' => $this->request->getGet('game_type'),
                'owner_id' => $this->request->getGet('owner_id'),
                'search' => $this->request->getGet('search'),
                'is_featured' => $this->request->getGet('is_featured'),
                'ping_status' => $this->request->getGet('ping_status'),
            ];

            // Filter by current user if they are not admin
            $userPayload = $this->request->userPayload;
            if (!$this->userModel->hasRole($userPayload['user_id'], 'admin') && 
                !$this->userModel->hasRole($userPayload['user_id'], 'super_admin')) {
                $filters['owner_id'] = $userPayload['user_id'];
            }

            $result = $this->serverModel->getServers($filters, $page, $perPage);

            $response = [
                'status' => 'success',
                'message' => 'Servers retrieved successfully',
                'data' => $result,
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Servers retrieval error: ' . $e->getMessage());
            return $this->fail('Failed to retrieve servers', 500);
        }
    }

    /**
     * Get single server by ID
     */
    public function show(int $id): ResponseInterface
    {
        try {
            $server = $this->serverModel->getServerWithOwner($id);

            if (!$server) {
                return $this->failNotFound('Server not found');
            }

            // Check if user can view this server
            $userPayload = $this->request->userPayload;
            if (!$this->serverModel->canUserManage($id, $userPayload['user_id']) && 
                $server['status'] !== 'approved') {
                return $this->failForbidden('Access denied');
            }

            $response = [
                'status' => 'success',
                'message' => 'Server retrieved successfully',
                'data' => [
                    'server' => $server,
                ],
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Server retrieval error: ' . $e->getMessage());
            return $this->fail('Failed to retrieve server', 500);
        }
    }

    /**
     * Create new server
     */
    public function create(): ResponseInterface
    {
        try {
            $rules = [
                'server_name' => 'required|min_length[3]|max_length[100]',
                'game_type' => 'required|alpha_numeric_punct|max_length[50]',
                'version' => 'permit_empty|max_length[50]',
                'description' => 'permit_empty|max_length[1000]',
                'website_url' => 'permit_empty|valid_url|max_length[255]',
                'discord_url' => 'permit_empty|valid_url|max_length[255]',
                'server_ip' => 'permit_empty|max_length[100]',
                'server_port' => 'permit_empty|integer|greater_than[0]|less_than[65536]',
                'max_players' => 'permit_empty|integer|greater_than[0]',
                'tags' => 'permit_empty|is_array',
                'features' => 'permit_empty|is_array',
                'social_links' => 'permit_empty|is_array',
            ];

            if (!$this->validateData($this->request->getJSON(true), $rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }

            $data = $this->request->getJSON(true);
            $userPayload = $this->request->userPayload;

            // Prepare server data
            $serverData = [
                'owner_id' => $userPayload['user_id'],
                'server_name' => $data['server_name'],
                'game_type' => $data['game_type'],
                'version' => $data['version'] ?? null,
                'description' => $data['description'] ?? null,
                'website_url' => $data['website_url'] ?? null,
                'discord_url' => $data['discord_url'] ?? null,
                'server_ip' => $data['server_ip'] ?? null,
                'server_port' => $data['server_port'] ?? null,
                'max_players' => $data['max_players'] ?? null,
                'tags' => !empty($data['tags']) ? json_encode($data['tags']) : null,
                'features' => !empty($data['features']) ? json_encode($data['features']) : null,
                'social_links' => !empty($data['social_links']) ? json_encode($data['social_links']) : null,
                'status' => 'pending',
                'sort_order' => 0,
            ];

            // Create server
            if (!$this->serverModel->insert($serverData)) {
                return $this->fail('Failed to create server', 500);
            }

            $serverId = $this->serverModel->getInsertID();
            $server = $this->serverModel->getServerWithOwner($serverId);

            $response = [
                'status' => 'success',
                'message' => 'Server created successfully',
                'data' => [
                    'server' => $server,
                ],
            ];

            return $this->respondCreated($response);

        } catch (\Exception $e) {
            log_message('error', 'Server creation error: ' . $e->getMessage());
            return $this->fail('Failed to create server', 500);
        }
    }

    /**
     * Update server
     */
    public function update(int $id): ResponseInterface
    {
        try {
            $server = $this->serverModel->find($id);
            if (!$server) {
                return $this->failNotFound('Server not found');
            }

            // Check if user can manage this server
            $userPayload = $this->request->userPayload;
            if (!$this->serverModel->canUserManage($id, $userPayload['user_id'])) {
                return $this->failForbidden('Access denied');
            }

            $rules = [
                'server_name' => 'permit_empty|min_length[3]|max_length[100]',
                'game_type' => 'permit_empty|alpha_numeric_punct|max_length[50]',
                'version' => 'permit_empty|max_length[50]',
                'description' => 'permit_empty|max_length[1000]',
                'website_url' => 'permit_empty|valid_url|max_length[255]',
                'discord_url' => 'permit_empty|valid_url|max_length[255]',
                'server_ip' => 'permit_empty|max_length[100]',
                'server_port' => 'permit_empty|integer|greater_than[0]|less_than[65536]',
                'max_players' => 'permit_empty|integer|greater_than[0]',
                'tags' => 'permit_empty|is_array',
                'features' => 'permit_empty|is_array',
                'social_links' => 'permit_empty|is_array',
            ];

            if (!$this->validateData($this->request->getJSON(true), $rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }

            $data = $this->request->getJSON(true);

            // Prepare update data
            $updateData = [];
            $allowedFields = [
                'server_name', 'game_type', 'version', 'description', 'website_url',
                'discord_url', 'server_ip', 'server_port', 'max_players'
            ];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            // Handle JSON fields
            if (isset($data['tags'])) {
                $updateData['tags'] = json_encode($data['tags']);
            }

            if (isset($data['features'])) {
                $updateData['features'] = json_encode($data['features']);
            }

            if (isset($data['social_links'])) {
                $updateData['social_links'] = json_encode($data['social_links']);
            }

            // Reset to pending status if server was rejected and being updated
            if ($server['status'] === 'rejected' && !empty($updateData)) {
                $updateData['status'] = 'pending';
                $updateData['rejection_reason'] = null;
            }

            // Update server
            if (!$this->serverModel->update($id, $updateData)) {
                return $this->fail('Failed to update server', 500);
            }

            // Get updated server
            $server = $this->serverModel->getServerWithOwner($id);

            $response = [
                'status' => 'success',
                'message' => 'Server updated successfully',
                'data' => [
                    'server' => $server,
                ],
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Server update error: ' . $e->getMessage());
            return $this->fail('Failed to update server', 500);
        }
    }

    /**
     * Delete server (soft delete)
     */
    public function delete(int $id): ResponseInterface
    {
        try {
            $server = $this->serverModel->find($id);
            if (!$server) {
                return $this->failNotFound('Server not found');
            }

            // Check if user can manage this server
            $userPayload = $this->request->userPayload;
            if (!$this->serverModel->canUserManage($id, $userPayload['user_id'])) {
                return $this->failForbidden('Access denied');
            }

            // Soft delete server
            if (!$this->serverModel->delete($id)) {
                return $this->fail('Failed to delete server', 500);
            }

            $response = [
                'status' => 'success',
                'message' => 'Server deleted successfully',
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Server deletion error: ' . $e->getMessage());
            return $this->fail('Failed to delete server', 500);
        }
    }

    /**
     * Approve server (admin/reviewer only)
     */
    public function approve(int $id): ResponseInterface
    {
        try {
            $server = $this->serverModel->find($id);
            if (!$server) {
                return $this->failNotFound('Server not found');
            }

            if ($server['status'] !== 'pending') {
                return $this->fail('Server is not pending approval', 400);
            }

            $data = $this->request->getJSON(true);
            $note = $data['note'] ?? null;
            $userPayload = $this->request->userPayload;

            // Approve server
            if (!$this->serverModel->approveServer($id, $userPayload['user_id'], $note)) {
                return $this->fail('Failed to approve server', 500);
            }

            // Get updated server
            $server = $this->serverModel->getServerWithOwner($id);

            $response = [
                'status' => 'success',
                'message' => 'Server approved successfully',
                'data' => [
                    'server' => $server,
                ],
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Server approval error: ' . $e->getMessage());
            return $this->fail('Failed to approve server', 500);
        }
    }

    /**
     * Reject server (admin/reviewer only)
     */
    public function reject(int $id): ResponseInterface
    {
        try {
            $server = $this->serverModel->find($id);
            if (!$server) {
                return $this->failNotFound('Server not found');
            }

            if ($server['status'] !== 'pending') {
                return $this->fail('Server is not pending approval', 400);
            }

            $rules = [
                'reason' => 'required|min_length[10]|max_length[500]',
            ];

            if (!$this->validateData($this->request->getJSON(true), $rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }

            $data = $this->request->getJSON(true);
            $userPayload = $this->request->userPayload;

            // Reject server
            if (!$this->serverModel->rejectServer($id, $userPayload['user_id'], $data['reason'])) {
                return $this->fail('Failed to reject server', 500);
            }

            // Get updated server
            $server = $this->serverModel->getServerWithOwner($id);

            $response = [
                'status' => 'success',
                'message' => 'Server rejected successfully',
                'data' => [
                    'server' => $server,
                ],
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Server rejection error: ' . $e->getMessage());
            return $this->fail('Failed to reject server', 500);
        }
    }

    /**
     * Get server settings
     */
    public function getSettings(int $id): ResponseInterface
    {
        try {
            $server = $this->serverModel->find($id);
            if (!$server) {
                return $this->failNotFound('Server not found');
            }

            // Check if user can manage this server
            $userPayload = $this->request->userPayload;
            if (!$this->serverModel->canUserManage($id, $userPayload['user_id'])) {
                return $this->failForbidden('Access denied');
            }

            // This would get server settings from server_settings table
            // For now, return empty settings
            $settings = [
                'database_config' => null,
                'reward_table_mapping' => null,
                'promotion_settings' => null,
                'notification_settings' => null,
                'api_settings' => null,
                'security_settings' => null,
                'display_settings' => null,
                'integration_settings' => null,
                'backup_settings' => null,
            ];

            $response = [
                'status' => 'success',
                'message' => 'Server settings retrieved successfully',
                'data' => [
                    'settings' => $settings,
                ],
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Server settings retrieval error: ' . $e->getMessage());
            return $this->fail('Failed to retrieve server settings', 500);
        }
    }

    /**
     * Update server settings
     */
    public function updateSettings(int $id): ResponseInterface
    {
        try {
            $server = $this->serverModel->find($id);
            if (!$server) {
                return $this->failNotFound('Server not found');
            }

            // Check if user can manage this server
            $userPayload = $this->request->userPayload;
            if (!$this->serverModel->canUserManage($id, $userPayload['user_id'])) {
                return $this->failForbidden('Access denied');
            }

            // This would update server settings in server_settings table
            // For now, just return success
            $response = [
                'status' => 'success',
                'message' => 'Server settings updated successfully',
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Server settings update error: ' . $e->getMessage());
            return $this->fail('Failed to update server settings', 500);
        }
    }

    /**
     * Test database connection
     */
    public function testConnection(int $id): ResponseInterface
    {
        try {
            $server = $this->serverModel->find($id);
            if (!$server) {
                return $this->failNotFound('Server not found');
            }

            // Check if user can manage this server
            $userPayload = $this->request->userPayload;
            if (!$this->serverModel->canUserManage($id, $userPayload['user_id'])) {
                return $this->failForbidden('Access denied');
            }

            $rules = [
                'hostname' => 'required|max_length[255]',
                'database' => 'required|max_length[100]',
                'username' => 'permit_empty|max_length[100]',
                'password' => 'permit_empty|max_length[100]',
                'DBDriver' => 'required|in_list[MySQLi,Postgre,SQLite3]',
                'port' => 'permit_empty|integer|greater_than[0]|less_than[65536]',
                'charset' => 'permit_empty|max_length[50]',
                'DBPrefix' => 'permit_empty|max_length[20]',
            ];

            if (!$this->validateData($this->request->getJSON(true), $rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }

            $connectionSettings = $this->request->getJSON(true);
            
            // Test database connection
            $testResult = $this->databaseTestService->testConnection($connectionSettings);
            
            if (!$testResult['success']) {
                return $this->fail($testResult['error'], 400);
            }

            $response = [
                'status' => 'success',
                'message' => 'Database connection test successful',
                'data' => $testResult,
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Database connection test error: ' . $e->getMessage());
            return $this->fail('Database connection test failed', 500);
        }
    }

    /**
     * Upload server logo
     */
    public function uploadLogo(int $id): ResponseInterface
    {
        try {
            $server = $this->serverModel->find($id);
            if (!$server) {
                return $this->failNotFound('Server not found');
            }

            // Check if user can manage this server
            $userPayload = $this->request->userPayload;
            if (!$this->serverModel->canUserManage($id, $userPayload['user_id'])) {
                return $this->failForbidden('Access denied');
            }

            $file = $this->request->getFile('logo');
            if (!$file || !$file->isValid()) {
                return $this->fail('No valid file uploaded', 400);
            }

            // Upload logo
            $result = $this->fileUploadService->uploadServerImage($file, $id, 'logo');
            
            if (!$result['success']) {
                return $this->fail($result['error'], 400);
            }

            // Update server logo in database
            $this->serverModel->update($id, ['logo_image' => $result['file_path']]);

            $response = [
                'status' => 'success',
                'message' => 'Logo uploaded successfully',
                'data' => $result,
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Logo upload error: ' . $e->getMessage());
            return $this->fail('Failed to upload logo', 500);
        }
    }

    /**
     * Upload server background image
     */
    public function uploadBackground(int $id): ResponseInterface
    {
        try {
            $server = $this->serverModel->find($id);
            if (!$server) {
                return $this->failNotFound('Server not found');
            }

            // Check if user can manage this server
            $userPayload = $this->request->userPayload;
            if (!$this->serverModel->canUserManage($id, $userPayload['user_id'])) {
                return $this->failForbidden('Access denied');
            }

            $file = $this->request->getFile('background');
            if (!$file || !$file->isValid()) {
                return $this->fail('No valid file uploaded', 400);
            }

            // Upload background
            $result = $this->fileUploadService->uploadServerImage($file, $id, 'background');
            
            if (!$result['success']) {
                return $this->fail($result['error'], 400);
            }

            // Update server background in database
            $this->serverModel->update($id, ['background_image' => $result['file_path']]);

            $response = [
                'status' => 'success',
                'message' => 'Background uploaded successfully',
                'data' => $result,
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Background upload error: ' . $e->getMessage());
            return $this->fail('Failed to upload background', 500);
        }
    }

    /**
     * Upload server banner images
     */
    public function uploadBanners(int $id): ResponseInterface
    {
        try {
            $server = $this->serverModel->find($id);
            if (!$server) {
                return $this->failNotFound('Server not found');
            }

            // Check if user can manage this server
            $userPayload = $this->request->userPayload;
            if (!$this->serverModel->canUserManage($id, $userPayload['user_id'])) {
                return $this->failForbidden('Access denied');
            }

            $files = $this->request->getFiles();
            if (empty($files['banners'])) {
                return $this->fail('No banner files uploaded', 400);
            }

            // Upload banners
            $result = $this->fileUploadService->uploadBannerImages($files['banners'], $id);
            
            if (!$result['success']) {
                return $this->fail('Failed to upload banners: ' . implode(', ', $result['errors']), 400);
            }

            // Update server banners in database
            $bannerPaths = array_column($result['results'], 'file_path');
            $existingBanners = json_decode($server['banner_images'] ?? '[]', true);
            $updatedBanners = array_merge($existingBanners, $bannerPaths);
            
            $this->serverModel->update($id, ['banner_images' => json_encode($updatedBanners)]);

            $response = [
                'status' => 'success',
                'message' => "Successfully uploaded {$result['uploaded_count']} banner(s)",
                'data' => $result,
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Banner upload error: ' . $e->getMessage());
            return $this->fail('Failed to upload banners', 500);
        }
    }

    /**
     * Delete server image
     */
    public function deleteImage(int $id): ResponseInterface
    {
        try {
            $server = $this->serverModel->find($id);
            if (!$server) {
                return $this->failNotFound('Server not found');
            }

            // Check if user can manage this server
            $userPayload = $this->request->userPayload;
            if (!$this->serverModel->canUserManage($id, $userPayload['user_id'])) {
                return $this->failForbidden('Access denied');
            }

            $data = $this->request->getJSON(true);
            $imageType = $data['image_type'] ?? '';
            $fileName = $data['file_name'] ?? '';

            if (!in_array($imageType, ['logo', 'background', 'banner'])) {
                return $this->fail('Invalid image type', 400);
            }

            // Delete file
            if ($imageType === 'banner') {
                // Remove from banner list
                $banners = json_decode($server['banner_images'] ?? '[]', true);
                $banners = array_filter($banners, function($banner) use ($fileName) {
                    return $banner !== $fileName;
                });
                
                $this->serverModel->update($id, ['banner_images' => json_encode(array_values($banners))]);
            } else {
                // Clear logo or background
                $field = $imageType === 'logo' ? 'logo_image' : 'background_image';
                $this->serverModel->update($id, [$field => null]);
            }

            // Delete physical files
            $this->fileUploadService->deleteServerFiles($id, $imageType);

            $response = [
                'status' => 'success',
                'message' => ucfirst($imageType) . ' deleted successfully',
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Image deletion error: ' . $e->getMessage());
            return $this->fail('Failed to delete image', 500);
        }
    }

    /**
     * Test reward table mapping
     */
    public function testRewardMapping(int $id): ResponseInterface
    {
        try {
            $server = $this->serverModel->find($id);
            if (!$server) {
                return $this->failNotFound('Server not found');
            }

            // Check if user can manage this server
            $userPayload = $this->request->userPayload;
            if (!$this->serverModel->canUserManage($id, $userPayload['user_id'])) {
                return $this->failForbidden('Access denied');
            }

            $rules = [
                'database_config' => 'required',
                'table_mapping' => 'required',
            ];

            if (!$this->validateData($this->request->getJSON(true), $rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }

            $data = $this->request->getJSON(true);
            
            // Test table mapping
            $result = $this->databaseTestService->testRewardTableMapping(
                $data['database_config'],
                $data['table_mapping']
            );

            if (!$result['success']) {
                return $this->fail($result['error'], 400);
            }

            $response = [
                'status' => 'success',
                'message' => 'Reward table mapping test completed',
                'data' => $result,
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Reward mapping test error: ' . $e->getMessage());
            return $this->fail('Failed to test reward mapping', 500);
        }
    }

    /**
     * Get server files information
     */
    public function getFiles(int $id): ResponseInterface
    {
        try {
            $server = $this->serverModel->find($id);
            if (!$server) {
                return $this->failNotFound('Server not found');
            }

            // Check if user can manage this server
            $userPayload = $this->request->userPayload;
            if (!$this->serverModel->canUserManage($id, $userPayload['user_id'])) {
                return $this->failForbidden('Access denied');
            }

            $files = [];

            // Get logo info
            if ($server['logo_image']) {
                $logoInfo = $this->fileUploadService->getFileInfo($server['logo_image']);
                if ($logoInfo) {
                    $files['logo'] = $logoInfo;
                }
            }

            // Get background info
            if ($server['background_image']) {
                $backgroundInfo = $this->fileUploadService->getFileInfo($server['background_image']);
                if ($backgroundInfo) {
                    $files['background'] = $backgroundInfo;
                }
            }

            // Get banner info
            if ($server['banner_images']) {
                $banners = json_decode($server['banner_images'], true);
                $files['banners'] = [];
                
                foreach ($banners as $banner) {
                    $bannerInfo = $this->fileUploadService->getFileInfo($banner);
                    if ($bannerInfo) {
                        $files['banners'][] = $bannerInfo;
                    }
                }
            }

            $response = [
                'status' => 'success',
                'message' => 'Server files retrieved successfully',
                'data' => [
                    'server_id' => $id,
                    'files' => $files,
                ],
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Get files error: ' . $e->getMessage());
            return $this->fail('Failed to retrieve files', 500);
        }
    }
}