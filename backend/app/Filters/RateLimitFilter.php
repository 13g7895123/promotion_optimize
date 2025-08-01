<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RateLimitFilter implements FilterInterface
{
    private $cache;
    private array $rateLimits;

    public function __construct()
    {
        $this->cache = service('cache');
        $config = config('App');
        $this->rateLimits = $config->rateLimiting;
    }

    /**
     * Apply rate limiting before request processing
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!$this->rateLimits['enabled']) {
            return $request;
        }

        $response = service('response');
        $identifier = $this->getIdentifier($request);
        $uri = $request->getUri()->getPath();

        // Different rate limits for different endpoints
        $limit = $this->getRateLimitForEndpoint($uri);
        $window = $this->rateLimits['window'];

        $cacheKey = "rate_limit:{$identifier}:" . floor(time() / $window);
        $attempts = $this->cache->get($cacheKey) ?? 0;

        if ($attempts >= $limit) {
            return $this->rateLimitExceededResponse($response, $limit, $window);
        }

        // Increment attempt count
        $this->cache->save($cacheKey, $attempts + 1, $window);

        // Add rate limit headers to request for later use in after()
        $request->rateLimitInfo = [
            'limit' => $limit,
            'remaining' => max(0, $limit - $attempts - 1),
            'reset' => (floor(time() / $window) + 1) * $window,
        ];

        return $request;
    }

    /**
     * Add rate limit headers to response
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if (isset($request->rateLimitInfo)) {
            $info = $request->rateLimitInfo;
            $response->setHeader('X-RateLimit-Limit', $info['limit']);
            $response->setHeader('X-RateLimit-Remaining', $info['remaining']);
            $response->setHeader('X-RateLimit-Reset', $info['reset']);
        }

        return $response;
    }

    /**
     * Get rate limit identifier (IP or user ID)
     */
    private function getIdentifier(RequestInterface $request): string
    {
        // Use user ID if authenticated, otherwise use IP
        $userPayload = $request->userPayload ?? null;
        
        if ($userPayload) {
            return 'user:' . $userPayload['user_id'];
        }

        return 'ip:' . $request->getIPAddress();
    }

    /**
     * Get rate limit for specific endpoint
     */
    private function getRateLimitForEndpoint(string $uri): int
    {
        // Authentication endpoints - stricter limits
        if (strpos($uri, '/api/auth/login') !== false) {
            return 5; // 5 login attempts per window
        }

        if (strpos($uri, '/api/auth/register') !== false) {
            return 3; // 3 registration attempts per window
        }

        // API endpoints - standard limits
        if (strpos($uri, '/api/') !== false) {
            return $this->rateLimits['requests'];
        }

        // Default limit for other endpoints
        return $this->rateLimits['requests'] * 2;
    }

    /**
     * Return rate limit exceeded response
     */
    private function rateLimitExceededResponse(ResponseInterface $response, int $limit, int $window): ResponseInterface
    {
        $data = [
            'status' => 'error',
            'message' => 'Rate limit exceeded',
            'details' => [
                'limit' => $limit,
                'window' => $window,
                'retry_after' => $window,
            ],
            'code' => 429,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        return $response->setStatusCode(429)
                       ->setHeader('Retry-After', $window)
                       ->setHeader('X-RateLimit-Limit', $limit)
                       ->setHeader('X-RateLimit-Remaining', 0)
                       ->setHeader('X-RateLimit-Reset', (floor(time() / $window) + 1) * $window)
                       ->setJSON($data);
    }
}