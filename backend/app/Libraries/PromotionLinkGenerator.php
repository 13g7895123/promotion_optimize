<?php

namespace App\Libraries;

use App\Models\PromotionModel;
use App\Models\ServerModel;
use CodeIgniter\HTTP\URI;

class PromotionLinkGenerator
{
    private PromotionModel $promotionModel;
    private ServerModel $serverModel;
    private string $baseUrl;

    public function __construct()
    {
        $this->promotionModel = new PromotionModel();
        $this->serverModel = new ServerModel();
        $this->baseUrl = rtrim(base_url(), '/');
    }

    /**
     * Generate promotion link
     */
    public function generatePromotionLink(int $serverId, int $promoterId, array $options = []): array
    {
        // Check if user can promote this server
        if (!$this->promotionModel->canUserPromoteServer($promoterId, $serverId)) {
            throw new \InvalidArgumentException('User already has an active promotion for this server');
        }

        // Get server information
        $server = $this->serverModel->find($serverId);
        if (!$server) {
            throw new \InvalidArgumentException('Server not found');
        }

        // Generate unique promotion code
        $promotionCode = $this->generateUniqueCode();
        
        // Create promotion record
        $promotionData = [
            'server_id' => $serverId,
            'promoter_id' => $promoterId,
            'promotion_code' => $promotionCode,
            'promotion_link' => '', // Will be set after generation
            'status' => 'active',
            'source_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'referrer_url' => $options['referrer'] ?? null,
            'expires_at' => $options['expires_at'] ?? null,
            'metadata' => json_encode($options['metadata'] ?? []),
        ];

        $promotionId = $this->promotionModel->insert($promotionData);
        if (!$promotionId) {
            throw new \RuntimeException('Failed to create promotion record');
        }

        // Generate the actual promotion link
        $baseLink = $options['base_link'] ?? $this->getDefaultBaseLink($server);
        $promotionLink = $this->buildPromotionLink($baseLink, $promotionCode, $options);

        // Update promotion record with the generated link
        $this->promotionModel->update($promotionId, ['promotion_link' => $promotionLink]);

        // Generate additional links if requested
        $additionalLinks = [];
        if (!empty($options['generate_variants'])) {
            $additionalLinks = $this->generateLinkVariants($baseLink, $promotionCode, $options);
        }

        return [
            'promotion_id' => $promotionId,
            'promotion_code' => $promotionCode,
            'promotion_link' => $promotionLink,
            'short_link' => $this->generateShortLink($promotionCode),
            'qr_code_url' => $this->generateQRCodeUrl($promotionLink),
            'additional_links' => $additionalLinks,
            'tracking_pixel' => $this->generateTrackingPixel($promotionId),
            'expires_at' => $promotionData['expires_at'],
            'server_info' => [
                'id' => $server['id'],
                'name' => $server['name'],
                'code' => $server['code'],
            ],
        ];
    }

    /**
     * Generate unique promotion code
     */
    private function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(bin2hex(random_bytes(8)));
        } while ($this->promotionModel->where('promotion_code', $code)->first());
        
        return $code;
    }

    /**
     * Get default base link for server
     */
    private function getDefaultBaseLink(array $server): string
    {
        // Priority: server website > registration page > default
        if (!empty($server['website_url'])) {
            return $server['website_url'];
        }
        
        if (!empty($server['registration_url'])) {
            return $server['registration_url'];
        }
        
        return $this->baseUrl . '/register/' . $server['code'];
    }

    /**
     * Build promotion link with parameters
     */
    private function buildPromotionLink(string $baseLink, string $promotionCode, array $options = []): string
    {
        $uri = new URI($baseLink);
        $params = [];

        // Add promotion code
        $params['ref'] = $promotionCode;

        // Add UTM parameters if provided
        if (!empty($options['utm_source'])) {
            $params['utm_source'] = $options['utm_source'];
        }
        if (!empty($options['utm_medium'])) {
            $params['utm_medium'] = $options['utm_medium'];
        }
        if (!empty($options['utm_campaign'])) {
            $params['utm_campaign'] = $options['utm_campaign'];
        }
        if (!empty($options['utm_term'])) {
            $params['utm_term'] = $options['utm_term'];
        }
        if (!empty($options['utm_content'])) {
            $params['utm_content'] = $options['utm_content'];
        }

        // Add custom parameters
        if (!empty($options['custom_params'])) {
            $params = array_merge($params, $options['custom_params']);
        }

        // Build query string
        $existingQuery = $uri->getQuery();
        if ($existingQuery) {
            parse_str($existingQuery, $existingParams);
            $params = array_merge($existingParams, $params);
        }

        $uri->setQuery(http_build_query($params));
        
        return $uri->__toString();
    }

    /**
     * Generate link variants for different platforms
     */
    private function generateLinkVariants(string $baseLink, string $promotionCode, array $options = []): array
    {
        $variants = [];

        // Social media variants
        $socialPlatforms = [
            'facebook' => [
                'utm_source' => 'facebook',
                'utm_medium' => 'social',
            ],
            'twitter' => [
                'utm_source' => 'twitter',
                'utm_medium' => 'social',
            ],
            'discord' => [
                'utm_source' => 'discord',
                'utm_medium' => 'social',
            ],
            'line' => [
                'utm_source' => 'line',
                'utm_medium' => 'social',
            ],
        ];

        foreach ($socialPlatforms as $platform => $utmParams) {
            $platformOptions = array_merge($options, $utmParams);
            $variants[$platform] = $this->buildPromotionLink($baseLink, $promotionCode, $platformOptions);
        }

        // Email variant
        $variants['email'] = $this->buildPromotionLink($baseLink, $promotionCode, 
            array_merge($options, ['utm_source' => 'email', 'utm_medium' => 'email']));

        // Direct variant (no UTM)
        $variants['direct'] = $this->buildPromotionLink($baseLink, $promotionCode, 
            ['custom_params' => ['ref' => $promotionCode]]);

        return $variants;
    }

    /**
     * Generate short link
     */
    private function generateShortLink(string $promotionCode): string
    {
        return $this->baseUrl . '/r/' . $promotionCode;
    }

    /**
     * Generate QR code URL
     */
    private function generateQRCodeUrl(string $promotionLink): string
    {
        // Using a simple QR code service - you might want to implement your own
        $encodedLink = urlencode($promotionLink);
        return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={$encodedLink}";
    }

    /**
     * Generate tracking pixel
     */
    private function generateTrackingPixel(int $promotionId): string
    {
        return $this->baseUrl . "/api/promotion/track/pixel/{$promotionId}";
    }

    /**
     * Update promotion link
     */
    public function updatePromotionLink(int $promotionId, array $options = []): array
    {
        $promotion = $this->promotionModel->find($promotionId);
        if (!$promotion) {
            throw new \InvalidArgumentException('Promotion not found');
        }

        $server = $this->serverModel->find($promotion['server_id']);
        if (!$server) {
            throw new \InvalidArgumentException('Server not found');
        }

        // Generate new link with updated options
        $baseLink = $options['base_link'] ?? $this->getDefaultBaseLink($server);
        $newLink = $this->buildPromotionLink($baseLink, $promotion['promotion_code'], $options);

        // Update promotion record
        $updateData = [
            'promotion_link' => $newLink,
            'metadata' => json_encode(array_merge(
                json_decode($promotion['metadata'] ?? '{}', true),
                $options['metadata'] ?? []
            )),
        ];

        if (isset($options['expires_at'])) {
            $updateData['expires_at'] = $options['expires_at'];
        }

        $this->promotionModel->update($promotionId, $updateData);

        return [
            'promotion_id' => $promotionId,
            'promotion_code' => $promotion['promotion_code'],
            'promotion_link' => $newLink,
            'short_link' => $this->generateShortLink($promotion['promotion_code']),
            'qr_code_url' => $this->generateQRCodeUrl($newLink),
        ];
    }

    /**
     * Generate image promotion materials
     */
    public function generatePromotionImages(int $promotionId, array $options = []): array
    {
        $promotion = $this->promotionModel->find($promotionId);
        if (!$promotion) {
            throw new \InvalidArgumentException('Promotion not found');
        }

        $server = $this->serverModel->find($promotion['server_id']);
        if (!$server) {
            throw new \InvalidArgumentException('Server not found');
        }

        $images = [];

        // Banner sizes
        $bannerSizes = [
            'large' => ['width' => 728, 'height' => 90],
            'medium' => ['width' => 468, 'height' => 60],
            'small' => ['width' => 320, 'height' => 50],
            'square' => ['width' => 300, 'height' => 300],
            'story' => ['width' => 1080, 'height' => 1920],
        ];

        foreach ($bannerSizes as $size => $dimensions) {
            $images[$size] = $this->generatePromotionBanner($promotion, $server, $dimensions, $options);
        }

        return $images;
    }

    /**
     * Generate promotion banner
     */
    private function generatePromotionBanner(array $promotion, array $server, array $dimensions, array $options = []): string
    {
        // This is a placeholder - you would implement actual image generation here
        // You might use libraries like GD, Imagick, or external services

        $params = [
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],
            'server_name' => urlencode($server['name']),
            'promotion_code' => $promotion['promotion_code'],
            'background_color' => $options['background_color'] ?? '2196F3',
            'text_color' => $options['text_color'] ?? 'FFFFFF',
            'template' => $options['template'] ?? 'default',
        ];

        return $this->baseUrl . '/api/promotion/banner?' . http_build_query($params);
    }

    /**
     * Generate social media sharing content
     */
    public function generateSharingContent(int $promotionId, string $platform = 'general'): array
    {
        $promotion = $this->promotionModel->find($promotionId);
        if (!$promotion) {
            throw new \InvalidArgumentException('Promotion not found');
        }

        $server = $this->serverModel->find($promotion['server_id']);
        if (!$server) {
            throw new \InvalidArgumentException('Server not found');
        }

        $content = [
            'general' => [
                'title' => "Join {$server['name']} - Amazing Gaming Experience!",
                'description' => "Come and join us on {$server['name']}! Use my referral link to get special rewards when you register.",
                'hashtags' => ['#gaming', '#server', '#join', '#{$server["code"]}'],
            ],
            'facebook' => [
                'title' => "🎮 Join me on {$server['name']}!",
                'description' => "Hey friends! I'm playing on this amazing server and you should join too! Click my link to get bonus rewards when you sign up. Let's game together! 🚀",
            ],
            'twitter' => [
                'text' => "🎮 Join me on {$server['name']}! Amazing community and great gameplay. Use my link for bonus rewards! {$promotion['promotion_link']} #gaming #{$server['code']}",
            ],
            'discord' => [
                'title' => "🎮 {$server['name']} Invitation",
                'description' => "Hey @everyone! Come join us on {$server['name']}. Great community, active players, and tons of fun!\n\nUse my referral link for bonus rewards: {$promotion['promotion_link']}",
            ],
            'line' => [
                'text' => "🎮 一起來玩 {$server['name']} 吧！\n使用我的推薦連結註冊可以獲得獎勵喔～\n{$promotion['promotion_link']}",
            ],
        ];

        return $content[$platform] ?? $content['general'];
    }

    /**
     * Get promotion link analytics
     */
    public function getLinkAnalytics(int $promotionId): array
    {
        $promotion = $this->promotionModel->find($promotionId);
        if (!$promotion) {
            throw new \InvalidArgumentException('Promotion not found');
        }

        return $this->promotionModel->getPerformanceMetrics($promotionId);
    }

    /**
     * Generate bulk promotion links
     */
    public function generateBulkLinks(int $serverId, array $promoterIds, array $options = []): array
    {
        $results = [];
        $errors = [];

        foreach ($promoterIds as $promoterId) {
            try {
                $result = $this->generatePromotionLink($serverId, $promoterId, $options);
                $results[] = $result;
            } catch (\Exception $e) {
                $errors[] = [
                    'promoter_id' => $promoterId,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'success' => $results,
            'errors' => $errors,
            'total_generated' => count($results),
            'total_errors' => count($errors),
        ];
    }

    /**
     * Validate promotion link
     */
    public function validatePromotionLink(string $promotionCode): array
    {
        $promotion = $this->promotionModel->getByCode($promotionCode);
        
        if (!$promotion) {
            return [
                'valid' => false,
                'reason' => 'Promotion code not found',
            ];
        }

        if ($promotion['status'] !== 'active') {
            return [
                'valid' => false,
                'reason' => 'Promotion is not active',
                'status' => $promotion['status'],
            ];
        }

        if ($promotion['expires_at'] && strtotime($promotion['expires_at']) < time()) {
            return [
                'valid' => false,
                'reason' => 'Promotion has expired',
                'expires_at' => $promotion['expires_at'],
            ];
        }

        return [
            'valid' => true,
            'promotion' => $promotion,
        ];
    }

    /**
     * Deactivate promotion link
     */
    public function deactivatePromotionLink(int $promotionId): bool
    {
        return $this->promotionModel->pausePromotion($promotionId);
    }

    /**
     * Reactivate promotion link
     */
    public function reactivatePromotionLink(int $promotionId): bool
    {
        return $this->promotionModel->resumePromotion($promotionId);
    }
}