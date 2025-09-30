<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\API\ResponseTrait;

/**
 * Simple Authentication Controller
 * Provides basic auth functionality without complex dependencies
 */
class SimpleAuthController extends BaseController
{
    use ResponseTrait;

    /**
     * Simple login endpoint
     */
    public function login(): ResponseInterface
    {
        try {
            // Get input
            $json = $this->request->getJSON(true);
            $login = $json['login'] ?? '';
            $password = $json['password'] ?? '';

            if (empty($login) || empty($password)) {
                return $this->fail('Login and password are required', 400);
            }

            // Connect to database
            $db = \Config\Database::connect();

            // Find user
            $user = $db->table('users')
                ->where('username', $login)
                ->orWhere('email', $login)
                ->where('deleted_at', null)
                ->get()
                ->getRowArray();

            if (!$user) {
                return $this->fail('Invalid credentials', 401);
            }

            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                return $this->fail('Invalid credentials', 401);
            }

            // Check status
            if ($user['status'] !== 'active') {
                return $this->fail('Account is not active', 403);
            }

            // Get roles
            $roles = $db->table('user_roles ur')
                ->join('roles r', 'r.id = ur.role_id')
                ->where('ur.user_id', $user['id'])
                ->where('ur.is_active', true)
                ->where('r.is_active', true)
                ->select('r.id, r.name, r.display_name, r.level')
                ->get()
                ->getResultArray();

            // Generate tokens
            $accessToken = bin2hex(random_bytes(32));
            $refreshToken = bin2hex(random_bytes(32));
            $sessionToken = bin2hex(random_bytes(32));

            // Save session
            $expiresAt = date('Y-m-d H:i:s', time() + 3600);
            $refreshExpiresAt = date('Y-m-d H:i:s', time() + 604800);

            $db->table('user_sessions')->insert([
                'user_id' => $user['id'],
                'session_token' => $sessionToken,
                'refresh_token' => $refreshToken,
                'ip_address' => $this->request->getIPAddress(),
                'user_agent' => $this->request->getUserAgent()->getAgentString(),
                'expires_at' => $expiresAt,
                'refresh_expires_at' => $refreshExpiresAt,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            // Update login info
            $db->table('users')
                ->where('id', $user['id'])
                ->update([
                    'last_login_at' => date('Y-m-d H:i:s'),
                    'last_login_ip' => $this->request->getIPAddress(),
                    'failed_login_attempts' => 0,
                ]);

            // Remove sensitive data
            unset($user['password_hash'], $user['password_reset_token'], $user['email_verification_token']);
            $user['roles'] = $roles;

            return $this->respond([
                'status' => 'success',
                'message' => 'Login successful',
                'data' => [
                    'user' => $user,
                    'tokens' => [
                        'access_token' => $sessionToken,
                        'refresh_token' => $refreshToken,
                        'expires_in' => 3600,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Simple login error: ' . $e->getMessage());
            return $this->fail('Login failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get current user info (requires valid session)
     */
    public function me(): ResponseInterface
    {
        try {
            $authHeader = $this->request->getHeaderLine('Authorization');
            if (empty($authHeader)) {
                return $this->fail('Authorization header required', 401);
            }

            // Extract token
            $token = str_replace('Bearer ', '', $authHeader);

            // Find session
            $db = \Config\Database::connect();
            $session = $db->table('user_sessions')
                ->where('session_token', $token)
                ->where('expires_at >', date('Y-m-d H:i:s'))
                ->get()
                ->getRowArray();

            if (!$session) {
                return $this->fail('Invalid or expired token', 401);
            }

            // Get user
            $user = $db->table('users')
                ->where('id', $session['user_id'])
                ->where('deleted_at', null)
                ->get()
                ->getRowArray();

            if (!$user) {
                return $this->fail('User not found', 404);
            }

            // Get roles
            $roles = $db->table('user_roles ur')
                ->join('roles r', 'r.id = ur.role_id')
                ->where('ur.user_id', $user['id'])
                ->where('ur.is_active', true)
                ->where('r.is_active', true)
                ->select('r.id, r.name, r.display_name, r.level')
                ->get()
                ->getResultArray();

            unset($user['password_hash'], $user['password_reset_token'], $user['email_verification_token']);
            $user['roles'] = $roles;

            return $this->respond([
                'status' => 'success',
                'data' => ['user' => $user],
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Get user error: ' . $e->getMessage());
            return $this->fail('Failed to get user info', 500);
        }
    }
}