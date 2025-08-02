<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Models\PromotionModel;
use App\Models\PromotionClickModel;
use App\Models\ServerModel;
use App\Libraries\PromotionLinkGenerator;
use App\Libraries\PromotionTracker;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\API\ResponseTrait;

class PromotionController extends BaseController
{
    use ResponseTrait;

    private PromotionModel $promotionModel;
    private PromotionClickModel $clickModel;
    private ServerModel $serverModel;
    private PromotionLinkGenerator $linkGenerator;
    private PromotionTracker $tracker;

    public function __construct()
    {
        $this->promotionModel = new PromotionModel();
        $this->clickModel = new PromotionClickModel();
        $this->serverModel = new ServerModel();
        $this->linkGenerator = new PromotionLinkGenerator();
        $this->tracker = new PromotionTracker();
    }

    /**
     * Get promotions list with pagination and filters
     */
    public function index(): ResponseInterface
    {
        try {
            $page = (int) ($this->request->getGet('page') ?? 1);
            $perPage = min((int) ($this->request->getGet('per_page') ?? 20), 100);
            $userId = $this->request->userPayload['user_id'];
            
            $filters = [
                'server_id' => $this->request->getGet('server_id'),
                'status' => $this->request->getGet('status'),
                'date_from' => $this->request->getGet('date_from'),
                'date_to' => $this->request->getGet('date_to'),
            ];

            $result = $this->promotionModel->getUserPromotions($userId, $filters, $page, $perPage);

            $response = [
                'status' => 'success',
                'message' => 'Promotions retrieved successfully',
                'data' => $result,
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Promotions retrieval error: ' . $e->getMessage());
            return $this->fail('Failed to retrieve promotions', 500);
        }
    }

    /**
     * Get single promotion by ID
     */
    public function show(int $id): ResponseInterface
    {
        try {
            $userId = $this->request->userPayload['user_id'];
            
            $promotion = $this->promotionModel->getPromotionWithDetails($id);

            if (!$promotion) {
                return $this->failNotFound('Promotion not found');
            }

            // Check if user owns this promotion or has permission
            if ($promotion['promoter_id'] !== $userId && !$this->hasPermission('promotion.view_all')) {
                return $this->failForbidden('Access denied');
            }

            // Get performance metrics
            $promotion['metrics'] = $this->promotionModel->getPerformanceMetrics($id);
            
            // Get click analytics
            $promotion['analytics'] = $this->tracker->getTrackingAnalytics($id, '30 days');

            $response = [
                'status' => 'success',
                'message' => 'Promotion retrieved successfully',
                'data' => [
                    'promotion' => $promotion,
                ],
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Promotion retrieval error: ' . $e->getMessage());
            return $this->fail('Failed to retrieve promotion', 500);
        }
    }

    /**
     * Create new promotion
     */
    public function create(): ResponseInterface
    {
        try {
            $rules = [
                'server_id' => 'required|integer|is_not_unique[servers.id]',
                'base_link' => 'permit_empty|valid_url',
                'expires_at' => 'permit_empty|valid_date',
                'utm_source' => 'permit_empty|max_length[100]',
                'utm_medium' => 'permit_empty|max_length[100]',
                'utm_campaign' => 'permit_empty|max_length[100]',
                'generate_variants' => 'permit_empty|boolean',
            ];

            if (!$this->validateData($this->request->getJSON(true), $rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }

            $data = $this->request->getJSON(true);
            $userId = $this->request->userPayload['user_id'];

            // Check if user can promote this server
            if (!$this->promotionModel->canUserPromoteServer($userId, $data['server_id'])) {
                return $this->fail('You already have an active promotion for this server', 400);
            }

            // Check server permissions
            $server = $this->serverModel->find($data['server_id']);
            if (!$server || $server['status'] !== 'active') {
                return $this->fail('Server is not available for promotion', 400);
            }

            // Generate promotion link
            $options = [
                'base_link' => $data['base_link'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
                'utm_source' => $data['utm_source'] ?? null,
                'utm_medium' => $data['utm_medium'] ?? null,
                'utm_campaign' => $data['utm_campaign'] ?? null,
                'utm_term' => $data['utm_term'] ?? null,
                'utm_content' => $data['utm_content'] ?? null,
                'generate_variants' => $data['generate_variants'] ?? false,
                'custom_params' => $data['custom_params'] ?? [],
                'metadata' => [
                    'created_via' => 'api',
                    'ip_address' => $this->request->getIPAddress(),
                    'user_agent' => $this->request->getUserAgent(),
                ],
            ];

            $result = $this->linkGenerator->generatePromotionLink($data['server_id'], $userId, $options);

            // Get the created promotion with details
            $promotion = $this->promotionModel->getPromotionWithDetails($result['promotion_id']);

            $response = [
                'status' => 'success',
                'message' => 'Promotion created successfully',
                'data' => [
                    'promotion' => $promotion,
                    'links' => [
                        'primary' => $result['promotion_link'],
                        'short' => $result['short_link'],
                        'qr_code' => $result['qr_code_url'],
                        'additional' => $result['additional_links'],
                    ],
                    'tracking' => [
                        'code' => $result['promotion_code'],
                        'pixel' => $result['tracking_pixel'],
                    ],
                ],
            ];

            return $this->respondCreated($response);

        } catch (\Exception $e) {
            log_message('error', 'Promotion creation error: ' . $e->getMessage());
            return $this->fail('Failed to create promotion: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update promotion
     */
    public function update(int $id): ResponseInterface
    {
        try {
            $userId = $this->request->userPayload['user_id'];
            
            $promotion = $this->promotionModel->find($id);
            if (!$promotion) {
                return $this->failNotFound('Promotion not found');
            }

            // Check ownership
            if ($promotion['promoter_id'] !== $userId && !$this->hasPermission('promotion.edit_all')) {
                return $this->failForbidden('Access denied');
            }

            $rules = [
                'base_link' => 'permit_empty|valid_url',
                'expires_at' => 'permit_empty|valid_date',
                'utm_source' => 'permit_empty|max_length[100]',
                'utm_medium' => 'permit_empty|max_length[100]',
                'utm_campaign' => 'permit_empty|max_length[100]',
                'status' => 'permit_empty|in_list[active,paused]',
            ];

            if (!$this->validateData($this->request->getJSON(true), $rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }

            $data = $this->request->getJSON(true);

            // Update basic promotion data
            $updateData = [];
            if (isset($data['expires_at'])) {
                $updateData['expires_at'] = $data['expires_at'];
            }
            if (isset($data['status'])) {
                $updateData['status'] = $data['status'];
            }

            if (!empty($updateData)) {
                $this->promotionModel->update($id, $updateData);
            }

            // Update promotion link if link-related data changed
            if (isset($data['base_link']) || isset($data['utm_source']) || isset($data['utm_medium']) || isset($data['utm_campaign'])) {
                $linkOptions = [
                    'base_link' => $data['base_link'] ?? null,
                    'expires_at' => $data['expires_at'] ?? null,
                    'utm_source' => $data['utm_source'] ?? null,
                    'utm_medium' => $data['utm_medium'] ?? null,
                    'utm_campaign' => $data['utm_campaign'] ?? null,
                    'utm_term' => $data['utm_term'] ?? null,
                    'utm_content' => $data['utm_content'] ?? null,
                    'metadata' => [
                        'updated_via' => 'api',
                        'updated_at' => date('Y-m-d H:i:s'),
                    ],
                ];

                $this->linkGenerator->updatePromotionLink($id, $linkOptions);
            }

            // Get updated promotion
            $promotion = $this->promotionModel->getPromotionWithDetails($id);

            $response = [
                'status' => 'success',
                'message' => 'Promotion updated successfully',
                'data' => [
                    'promotion' => $promotion,
                ],
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Promotion update error: ' . $e->getMessage());
            return $this->fail('Failed to update promotion', 500);
        }
    }

    /**
     * Delete promotion (pause)
     */
    public function delete(int $id): ResponseInterface
    {
        try {
            $userId = $this->request->userPayload['user_id'];
            
            $promotion = $this->promotionModel->find($id);
            if (!$promotion) {
                return $this->failNotFound('Promotion not found');
            }

            // Check ownership
            if ($promotion['promoter_id'] !== $userId && !$this->hasPermission('promotion.delete_all')) {
                return $this->failForbidden('Access denied');
            }

            // Pause promotion instead of deleting
            if (!$this->promotionModel->pausePromotion($id)) {
                return $this->fail('Failed to pause promotion', 500);
            }

            $response = [
                'status' => 'success',
                'message' => 'Promotion paused successfully',
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Promotion deletion error: ' . $e->getMessage());
            return $this->fail('Failed to pause promotion', 500);
        }
    }

    /**
     * Track promotion click
     */
    public function track(string $code): ResponseInterface
    {
        try {
            $context = [
                'ip' => $this->request->getIPAddress(),
                'user_agent' => $this->request->getUserAgent(),
                'referrer' => $this->request->getHeaderLine('Referer'),
                'landing_page' => $this->request->getGet('landing_page'),
                'utm_source' => $this->request->getGet('utm_source'),
                'utm_medium' => $this->request->getGet('utm_medium'),
                'utm_campaign' => $this->request->getGet('utm_campaign'),
                'utm_term' => $this->request->getGet('utm_term'),
                'utm_content' => $this->request->getGet('utm_content'),
                'fingerprint' => $this->request->getGet('fp'), // Client-side fingerprint
            ];

            $result = $this->tracker->trackClick($code, $context);

            if (!$result['success']) {
                return $this->fail($result['reason'], 400);
            }

            $response = [
                'status' => 'success',
                'message' => 'Click tracked successfully',
                'data' => [
                    'click_id' => $result['click_id'],
                    'redirect_url' => $result['redirect_url'],
                ],
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Click tracking error: ' . $e->getMessage());
            return $this->fail('Failed to track click', 500);
        }
    }

    /**
     * Track conversion
     */
    public function trackConversion(): ResponseInterface
    {
        try {
            $rules = [
                'user_id' => 'required|integer|is_not_unique[users.id]',
                'fingerprint' => 'permit_empty|max_length[64]',
            ];

            if (!$this->validateData($this->request->getJSON(true), $rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }

            $data = $this->request->getJSON(true);
            
            $context = [
                'ip' => $this->request->getIPAddress(),
                'user_agent' => $this->request->getUserAgent(),
                'fingerprint' => $data['fingerprint'] ?? null,
            ];

            $result = $this->tracker->trackConversion($data['user_id'], $context);

            $response = [
                'status' => 'success',
                'message' => 'Conversion tracked successfully',
                'data' => $result,
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Conversion tracking error: ' . $e->getMessage());
            return $this->fail('Failed to track conversion', 500);
        }
    }

    /**
     * Generate tracking pixel
     */
    public function trackingPixel(int $promotionId): ResponseInterface
    {
        try {
            $context = [
                'ip' => $this->request->getIPAddress(),
                'user_agent' => $this->request->getUserAgent(),
                'referrer' => $this->request->getHeaderLine('Referer'),
            ];

            $pixelData = $this->tracker->generateTrackingPixel($promotionId, $context);

            return $this->response
                        ->setHeader('Content-Type', 'image/gif')
                        ->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
                        ->setHeader('Pragma', 'no-cache')
                        ->setHeader('Expires', '0')
                        ->setBody($pixelData);

        } catch (\Exception $e) {
            log_message('error', 'Tracking pixel error: ' . $e->getMessage());
            
            // Return empty pixel even on error
            $emptyPixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
            return $this->response
                        ->setHeader('Content-Type', 'image/gif')
                        ->setBody($emptyPixel);
        }
    }

    /**
     * Get promotion analytics
     */
    public function analytics(int $id): ResponseInterface
    {
        try {
            $userId = $this->request->userPayload['user_id'];
            $period = $this->request->getGet('period') ?? '30 days';
            
            $promotion = $this->promotionModel->find($id);
            if (!$promotion) {
                return $this->failNotFound('Promotion not found');
            }

            // Check ownership
            if ($promotion['promoter_id'] !== $userId && !$this->hasPermission('promotion.view_all')) {
                return $this->failForbidden('Access denied');
            }

            $analytics = [
                'basic_stats' => $this->promotionModel->getPerformanceMetrics($id),
                'click_analytics' => $this->tracker->getTrackingAnalytics($id, $period),
                'conversion_funnel' => $this->tracker->getConversionFunnel($id, $period),
                'referrer_analysis' => $this->tracker->getReferrerAnalysis($id, $period),
                'click_heatmap' => $this->tracker->getClickHeatmap($id, $period),
                'realtime_stats' => $this->tracker->getRealTimeStats($id),
            ];

            $response = [
                'status' => 'success',
                'message' => 'Analytics retrieved successfully',
                'data' => [
                    'promotion_id' => $id,
                    'period' => $period,
                    'analytics' => $analytics,
                ],
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Analytics retrieval error: ' . $e->getMessage());
            return $this->fail('Failed to retrieve analytics', 500);
        }
    }

    /**
     * Generate promotion materials
     */
    public function materials(int $id): ResponseInterface
    {
        try {
            $userId = $this->request->userPayload['user_id'];
            
            $promotion = $this->promotionModel->find($id);
            if (!$promotion) {
                return $this->failNotFound('Promotion not found');
            }

            // Check ownership
            if ($promotion['promoter_id'] !== $userId && !$this->hasPermission('promotion.view_all')) {
                return $this->failForbidden('Access denied');
            }

            $data = $this->request->getJSON(true) ?? [];
            
            // Generate images
            $images = $this->linkGenerator->generatePromotionImages($id, $data);
            
            // Generate sharing content
            $sharingContent = [];
            $platforms = ['general', 'facebook', 'twitter', 'discord', 'line'];
            
            foreach ($platforms as $platform) {
                $sharingContent[$platform] = $this->linkGenerator->generateSharingContent($id, $platform);
            }

            $response = [
                'status' => 'success',
                'message' => 'Promotion materials generated successfully',
                'data' => [
                    'promotion_id' => $id,
                    'images' => $images,
                    'sharing_content' => $sharingContent,
                    'links' => [
                        'primary' => $promotion['promotion_link'],
                        'short' => $this->linkGenerator->generateShortLink($promotion['promotion_code']),
                        'qr_code' => $this->linkGenerator->generateQRCodeUrl($promotion['promotion_link']),
                    ],
                ],
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Materials generation error: ' . $e->getMessage());
            return $this->fail('Failed to generate materials', 500);
        }
    }

    /**
     * Pause promotion
     */
    public function pause(int $id): ResponseInterface
    {
        try {
            $userId = $this->request->userPayload['user_id'];
            
            $promotion = $this->promotionModel->find($id);
            if (!$promotion) {
                return $this->failNotFound('Promotion not found');
            }

            // Check ownership
            if ($promotion['promoter_id'] !== $userId && !$this->hasPermission('promotion.edit_all')) {
                return $this->failForbidden('Access denied');
            }

            if (!$this->promotionModel->pausePromotion($id)) {
                return $this->fail('Failed to pause promotion', 500);
            }

            $response = [
                'status' => 'success',
                'message' => 'Promotion paused successfully',
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Promotion pause error: ' . $e->getMessage());
            return $this->fail('Failed to pause promotion', 500);
        }
    }

    /**
     * Resume promotion
     */
    public function resume(int $id): ResponseInterface
    {
        try {
            $userId = $this->request->userPayload['user_id'];
            
            $promotion = $this->promotionModel->find($id);
            if (!$promotion) {
                return $this->failNotFound('Promotion not found');
            }

            // Check ownership
            if ($promotion['promoter_id'] !== $userId && !$this->hasPermission('promotion.edit_all')) {
                return $this->failForbidden('Access denied');
            }

            if (!$this->promotionModel->resumePromotion($id)) {
                return $this->fail('Failed to resume promotion', 500);
            }

            $response = [
                'status' => 'success',
                'message' => 'Promotion resumed successfully',
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Promotion resume error: ' . $e->getMessage());
            return $this->fail('Failed to resume promotion', 500);
        }
    }

    /**
     * Validate promotion code
     */
    public function validate(string $code): ResponseInterface
    {
        try {
            $validation = $this->linkGenerator->validatePromotionLink($code);

            $response = [
                'status' => 'success',
                'message' => 'Promotion code validated',
                'data' => $validation,
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', 'Promotion validation error: ' . $e->getMessage());
            return $this->fail('Failed to validate promotion code', 500);
        }
    }

    /**
     * Export promotion data
     */
    public function export(int $id): ResponseInterface
    {
        try {
            $userId = $this->request->userPayload['user_id'];
            $format = $this->request->getGet('format') ?? 'csv';
            
            $promotion = $this->promotionModel->find($id);
            if (!$promotion) {
                return $this->failNotFound('Promotion not found');
            }

            // Check ownership
            if ($promotion['promoter_id'] !== $userId && !$this->hasPermission('promotion.view_all')) {
                return $this->failForbidden('Access denied');
            }

            $options = [
                'date_from' => $this->request->getGet('date_from'),
                'date_to' => $this->request->getGet('date_to'),
            ];

            $exportData = $this->tracker->exportTrackingData($id, $format, $options);

            $filename = "promotion_{$id}_" . date('Y-m-d') . ".{$format}";
            
            $mimeTypes = [
                'csv' => 'text/csv',
                'json' => 'application/json',
            ];

            return $this->response
                        ->setHeader('Content-Type', $mimeTypes[$format] ?? 'text/plain')
                        ->setHeader('Content-Disposition', "attachment; filename=\"{$filename}\"")
                        ->setBody($exportData);

        } catch (\Exception $e) {
            log_message('error', 'Export error: ' . $e->getMessage());
            return $this->fail('Failed to export data', 500);
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