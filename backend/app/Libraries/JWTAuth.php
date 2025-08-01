<?php

namespace App\Libraries;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\InvalidTokenException;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\UserModel;
use App\Models\UserSessionModel;

class JWTAuth
{
    private string $secretKey;
    private string $algorithm;
    private int $expiration;
    private int $refreshExpiration;
    private UserModel $userModel;
    private UserSessionModel $sessionModel;

    public function __construct()
    {
        $config = config('App');
        $this->secretKey = $config->jwtConfig['secretKey'];
        $this->algorithm = $config->jwtConfig['algorithm'];
        $this->expiration = $config->jwtConfig['expiration'];
        $this->refreshExpiration = $config->jwtConfig['refreshExpiration'];
        
        $this->userModel = new UserModel();
        $this->sessionModel = new UserSessionModel();
    }

    /**
     * Generate JWT token for user
     */
    public function generateToken(array $user): array
    {
        $now = time();
        $sessionToken = bin2hex(random_bytes(32));
        $refreshToken = bin2hex(random_bytes(32));

        // Access token payload
        $accessPayload = [
            'iss' => base_url(), // Issuer
            'aud' => base_url(), // Audience
            'iat' => $now, // Issued at
            'nbf' => $now, // Not before
            'exp' => $now + $this->expiration, // Expiration
            'jti' => $sessionToken, // JWT ID
            'sub' => $user['id'], // Subject (user ID)
            'user_id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'roles' => $this->getUserRoles($user['id']),
            'permissions' => $this->getUserPermissions($user['id']),
        ];

        // Refresh token payload
        $refreshPayload = [
            'iss' => base_url(),
            'aud' => base_url(),
            'iat' => $now,
            'exp' => $now + $this->refreshExpiration,
            'jti' => $refreshToken,
            'sub' => $user['id'],
            'type' => 'refresh',
        ];

        $accessToken = JWT::encode($accessPayload, $this->secretKey, $this->algorithm);
        $refreshTokenJWT = JWT::encode($refreshPayload, $this->secretKey, $this->algorithm);

        // Store session in database
        $request = service('request');
        $sessionData = [
            'user_id' => $user['id'],
            'session_token' => $sessionToken,
            'refresh_token' => $refreshToken,
            'device_info' => [
                'user_agent' => $request->getUserAgent()->getAgentString(),
                'platform' => $request->getUserAgent()->getPlatform(),
                'browser' => $request->getUserAgent()->getBrowser(),
                'version' => $request->getUserAgent()->getVersion(),
            ],
            'ip_address' => $request->getIPAddress(),
            'expires_at' => date('Y-m-d H:i:s', $now + $this->expiration),
            'refresh_expires_at' => date('Y-m-d H:i:s', $now + $this->refreshExpiration),
            'last_activity_at' => date('Y-m-d H:i:s', $now),
        ];

        $this->sessionModel->insert($sessionData);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshTokenJWT,
            'token_type' => 'Bearer',
            'expires_in' => $this->expiration,
            'expires_at' => $now + $this->expiration,
        ];
    }

    /**
     * Validate JWT token
     */
    public function validateToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            $payload = (array) $decoded;

            // Check if session exists and is active
            $session = $this->sessionModel->where('session_token', $payload['jti'])
                                         ->where('is_active', true)
                                         ->where('expires_at >', date('Y-m-d H:i:s'))
                                         ->first();

            if (!$session) {
                throw new InvalidTokenException('Session not found or expired');
            }

            // Update last activity
            $this->sessionModel->update($session['id'], [
                'last_activity_at' => date('Y-m-d H:i:s')
            ]);

            return $payload;

        } catch (ExpiredException $e) {
            throw new InvalidTokenException('Token has expired');
        } catch (\Exception $e) {
            throw new InvalidTokenException('Invalid token: ' . $e->getMessage());
        }
    }

    /**
     * Refresh JWT token
     */
    public function refreshToken(string $refreshToken): array
    {
        try {
            $decoded = JWT::decode($refreshToken, new Key($this->secretKey, $this->algorithm));
            $payload = (array) $decoded;

            if (!isset($payload['type']) || $payload['type'] !== 'refresh') {
                throw new InvalidTokenException('Invalid refresh token');
            }

            // Check if refresh session exists and is active
            $session = $this->sessionModel->where('refresh_token', $payload['jti'])
                                         ->where('is_active', true)
                                         ->where('refresh_expires_at >', date('Y-m-d H:i:s'))
                                         ->first();

            if (!$session) {
                throw new InvalidTokenException('Refresh session not found or expired');
            }

            // Get user data
            $user = $this->userModel->find($payload['sub']);
            if (!$user || $user['status'] !== 'active') {
                throw new InvalidTokenException('User not found or inactive');
            }

            // Invalidate old session
            $this->sessionModel->update($session['id'], ['is_active' => false]);

            // Generate new tokens
            return $this->generateToken($user);

        } catch (ExpiredException $e) {
            throw new InvalidTokenException('Refresh token has expired');
        } catch (\Exception $e) {
            throw new InvalidTokenException('Invalid refresh token: ' . $e->getMessage());
        }
    }

    /**
     * Revoke token (logout)
     */
    public function revokeToken(string $token): bool
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            $payload = (array) $decoded;

            // Deactivate session
            $this->sessionModel->where('session_token', $payload['jti'])
                               ->set(['is_active' => false])
                               ->update();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Revoke all user sessions
     */
    public function revokeAllUserTokens(int $userId): bool
    {
        return $this->sessionModel->where('user_id', $userId)
                                  ->set(['is_active' => false])
                                  ->update();
    }

    /**
     * Get user roles
     */
    private function getUserRoles(int $userId): array
    {
        return $this->userModel->getUserRoles($userId);
    }

    /**
     * Get user permissions
     */
    private function getUserPermissions(int $userId): array
    {
        return $this->userModel->getUserPermissions($userId);
    }

    /**
     * Extract token from request
     */
    public function getTokenFromRequest(RequestInterface $request): ?string
    {
        $header = $request->getHeaderLine('Authorization');
        
        if (empty($header)) {
            return null;
        }

        if (!preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return null;
        }

        return $matches[1];
    }

    /**
     * Check if user has specific permission
     */
    public function hasPermission(array $userPayload, string $permission): bool
    {
        return in_array($permission, $userPayload['permissions'] ?? []);
    }

    /**
     * Check if user has specific role
     */
    public function hasRole(array $userPayload, string $role): bool
    {
        $userRoles = array_column($userPayload['roles'] ?? [], 'name');
        return in_array($role, $userRoles);
    }

    /**
     * Check if user has minimum role level
     */
    public function hasMinimumRoleLevel(array $userPayload, int $level): bool
    {
        $userRoles = $userPayload['roles'] ?? [];
        $minLevel = min(array_column($userRoles, 'level'));
        return $minLevel <= $level;
    }

    /**
     * Clean expired sessions
     */
    public function cleanExpiredSessions(): int
    {
        return $this->sessionModel->where('expires_at <', date('Y-m-d H:i:s'))
                                  ->orWhere('refresh_expires_at <', date('Y-m-d H:i:s'))
                                  ->delete();
    }

    /**
     * Get active sessions for user
     */
    public function getUserActiveSessions(int $userId): array
    {
        return $this->sessionModel->where('user_id', $userId)
                                  ->where('is_active', true)
                                  ->where('expires_at >', date('Y-m-d H:i:s'))
                                  ->orderBy('last_activity_at', 'DESC')
                                  ->findAll();
    }
}