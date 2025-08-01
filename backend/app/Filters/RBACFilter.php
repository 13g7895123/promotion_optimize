<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\JWTAuth;

class RBACFilter implements FilterInterface
{
    private JWTAuth $jwtAuth;

    // Role levels (lower number = higher privilege)
    const ROLE_LEVELS = [
        'super_admin' => 1,
        'admin' => 2,
        'server_owner' => 3,
        'reviewer' => 4,
        'user' => 5,
    ];

    public function __construct()
    {
        $this->jwtAuth = new JWTAuth();
    }

    /**
     * Check user permissions before allowing access
     *
     * @param RequestInterface $request
     * @param array|null       $arguments - Can contain: role, permission, or level
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $response = service('response');

        // Get user payload from JWT auth (should be set by JWTAuthFilter)
        $userPayload = $request->userPayload ?? null;

        if (!$userPayload) {
            return $this->forbiddenResponse($response, 'Authentication required');
        }

        // Parse arguments
        $requiredRole = $arguments[0] ?? null;
        $requiredPermission = $arguments[1] ?? null;
        $requiredLevel = $arguments[2] ?? null;

        // If no specific requirements, just check if user is authenticated
        if (!$requiredRole && !$requiredPermission && !$requiredLevel) {
            return $request;
        }

        try {
            // Check role-based access
            if ($requiredRole) {
                if (!$this->checkRoleAccess($userPayload, $requiredRole)) {
                    return $this->forbiddenResponse($response, 
                        "Access denied. Required role: {$requiredRole}");
                }
            }

            // Check permission-based access
            if ($requiredPermission) {
                if (!$this->jwtAuth->hasPermission($userPayload, $requiredPermission)) {
                    return $this->forbiddenResponse($response, 
                        "Access denied. Required permission: {$requiredPermission}");
                }
            }

            // Check level-based access
            if ($requiredLevel) {
                if (!$this->checkLevelAccess($userPayload, $requiredLevel)) {
                    return $this->forbiddenResponse($response, 
                        "Access denied. Insufficient privilege level");
                }
            }

            // Check resource ownership for certain routes
            if ($this->needsOwnershipCheck($request)) {
                if (!$this->checkResourceOwnership($request, $userPayload)) {
                    return $this->forbiddenResponse($response, 
                        "Access denied. You can only access your own resources");
                }
            }

        } catch (\Exception $e) {
            log_message('error', 'RBAC Filter Error: ' . $e->getMessage());
            return $this->forbiddenResponse($response, 'Authorization check failed');
        }

        return $request;
    }

    /**
     * After filter - log access attempts
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Log access attempts for audit purposes
        $userPayload = $request->userPayload ?? null;
        
        if ($userPayload) {
            $this->logAccessAttempt($request, $userPayload, $response->getStatusCode());
        }

        return $response;
    }

    /**
     * Check if user has required role or higher privilege
     */
    private function checkRoleAccess(array $userPayload, string $requiredRole): bool
    {
        $userRoles = $userPayload['roles'] ?? [];
        
        // Super admin has access to everything
        if ($this->jwtAuth->hasRole($userPayload, 'super_admin')) {
            return true;
        }

        // Check if user has the specific role
        if ($this->jwtAuth->hasRole($userPayload, $requiredRole)) {
            return true;
        }

        // Check if user has a higher privilege role
        $requiredLevel = self::ROLE_LEVELS[$requiredRole] ?? 999;
        return $this->jwtAuth->hasMinimumRoleLevel($userPayload, $requiredLevel);
    }

    /**
     * Check if user has minimum privilege level
     */
    private function checkLevelAccess(array $userPayload, int $requiredLevel): bool
    {
        return $this->jwtAuth->hasMinimumRoleLevel($userPayload, $requiredLevel);
    }

    /**
     * Check if route needs ownership verification
     */
    private function needsOwnershipCheck(RequestInterface $request): bool
    {
        $uri = $request->getUri()->getPath();
        
        // Routes that need ownership check
        $ownershipRoutes = [
            '/api/servers/',
            '/api/profile',
            '/api/promotions/',
        ];

        foreach ($ownershipRoutes as $route) {
            if (strpos($uri, $route) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user owns the resource being accessed
     */
    private function checkResourceOwnership(RequestInterface $request, array $userPayload): bool
    {
        $uri = $request->getUri()->getPath();
        $method = $request->getMethod();
        $userId = $userPayload['user_id'];

        // Skip ownership check for super admin and admin
        if ($this->jwtAuth->hasRole($userPayload, 'super_admin') || 
            $this->jwtAuth->hasRole($userPayload, 'admin')) {
            return true;
        }

        // Extract resource ID from URI
        if (preg_match('/\/api\/servers\/(\d+)/', $uri, $matches)) {
            $serverId = $matches[1];
            return $this->checkServerOwnership($serverId, $userId);
        }

        if (preg_match('/\/api\/promotions\/(\d+)/', $uri, $matches)) {
            $promotionId = $matches[1];
            return $this->checkPromotionOwnership($promotionId, $userId);
        }

        // Profile routes - users can only access their own profile
        if (strpos($uri, '/api/profile') !== false) {
            return true; // User can always access their own profile
        }

        return true; // Default allow if no specific ownership check
    }

    /**
     * Check if user owns the server
     */
    private function checkServerOwnership(int $serverId, int $userId): bool
    {
        $serverModel = new \App\Models\ServerModel();
        $server = $serverModel->find($serverId);
        
        return $server && $server['owner_id'] == $userId;
    }

    /**
     * Check if user owns the promotion
     */
    private function checkPromotionOwnership(int $promotionId, int $userId): bool
    {
        // This will be implemented when promotion model is created
        // For now, return true
        return true;
    }

    /**
     * Log access attempt for audit
     */
    private function logAccessAttempt(RequestInterface $request, array $userPayload, int $statusCode): void
    {
        try {
            $logData = [
                'user_id' => $userPayload['user_id'],
                'username' => $userPayload['username'],
                'method' => $request->getMethod(),
                'uri' => $request->getUri()->getPath(),
                'ip_address' => $request->getIPAddress(),
                'user_agent' => $request->getUserAgent()->getAgentString(),
                'status_code' => $statusCode,
                'timestamp' => date('Y-m-d H:i:s'),
            ];

            // Store in cache for later batch processing to database
            $cache = service('cache');
            $cacheKey = 'access_logs_' . date('Y-m-d-H');
            $existingLogs = $cache->get($cacheKey) ?? [];
            $existingLogs[] = $logData;
            $cache->save($cacheKey, $existingLogs, 3600); // Store for 1 hour

        } catch (\Exception $e) {
            log_message('error', 'Failed to log access attempt: ' . $e->getMessage());
        }
    }

    /**
     * Return forbidden response
     */
    private function forbiddenResponse(ResponseInterface $response, string $message): ResponseInterface
    {
        $data = [
            'status' => 'error',
            'message' => $message,
            'code' => 403,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        return $response->setStatusCode(403)
                       ->setJSON($data);
    }
}